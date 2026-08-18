<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Contracts\Students\StudentIdCardPrinter;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\IdCardRequestReasonEnum;
use App\Enums\Students\IdCardRequestStatusEnum;
use App\Exceptions\Students\InvalidIdCardRequestTransitionException;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentIdCardRequest;
use App\Models\Users\User;
use App\Support\Students\StudentIdCardFace;
use App\Support\Students\StudentIdCardPrintResult;
use App\Support\Students\StudentIdCardSerialGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentIdCardRequestService
{
    public function __construct(
        private readonly StudentIdCardSerialGenerator $serialGenerator,
        private readonly StudentIdCardPrinter $printer,
        private readonly StudentIdCardPhotoService $photoService,
    ) {}

    public function uploadPhoto(Student $student, UploadedFile $photo): Media
    {
        return $this->photoService->uploadIdPhoto($student, $photo);
    }

    public function submit(Student $student, IdCardRequestReasonEnum $reason, ?string $notes = null): StudentIdCardRequest
    {
        $this->assertHasStudentNumber($student);
        $this->assertNoActiveRequest($student);

        $photo = $this->photoService->ensureMediaFromPrintFolder($student);
        if (! $photo instanceof Media) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_photo_required');
        }

        return DB::transaction(function () use ($student, $reason, $notes, $photo): StudentIdCardRequest {
            $status = $reason->requiresFee()
                ? IdCardRequestStatusEnum::AWAITING_PAYMENT
                : IdCardRequestStatusEnum::PENDING;

            $request = $student->idCardRequests()->create([
                'tenant_id' => $student->tenant_id,
                'status' => $status,
                'reason' => $reason,
                'notes' => $notes,
            ]);

            $this->snapshotPhoto($photo, $request);

            if ($reason->requiresFee()) {
                $this->createReissueInvoice($request);
            }

            return $request->fresh(['photo', 'feeLedger']) ?? $request;
        });
    }

    public function attachLatestPhoto(Student $student, StudentIdCardRequest $request): StudentIdCardRequest
    {
        if ($request->status !== IdCardRequestStatusEnum::PENDING
            && $request->status !== IdCardRequestStatusEnum::AWAITING_PAYMENT) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_photo_frozen');
        }

        $photo = $this->photoService->ensureMediaFromPrintFolder($student);
        if (! $photo instanceof Media) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_photo_required');
        }

        return $this->snapshotPhoto($photo, $request);
    }

    public function markPaid(StudentIdCardRequest $request): StudentIdCardRequest
    {
        $this->assertStatus($request, IdCardRequestStatusEnum::AWAITING_PAYMENT, IdCardRequestStatusEnum::PENDING);

        $request->update([
            'status' => IdCardRequestStatusEnum::PENDING,
        ]);

        return $request->fresh() ?? $request;
    }

    public function markPaidFromLedger(Ledger $ledger): void
    {
        if ($ledger->type !== 'receipt' || strtolower((string) $ledger->payment_status) !== 'paid') {
            return;
        }

        $ledger->loadMissing('ledgerable');

        if (! $ledger->ledgerable instanceof StudentIdCardRequest) {
            return;
        }

        $request = $ledger->ledgerable;
        if ($request->status !== IdCardRequestStatusEnum::AWAITING_PAYMENT) {
            return;
        }

        $this->markPaid($request);
    }

    public function approve(StudentIdCardRequest $request, User $admin): StudentIdCardRequest
    {
        $this->assertStatus($request, IdCardRequestStatusEnum::PENDING, IdCardRequestStatusEnum::APPROVED);

        $request->update([
            'status' => IdCardRequestStatusEnum::APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        return $request->fresh() ?? $request;
    }

    public function reject(StudentIdCardRequest $request, User $admin, string $reason): StudentIdCardRequest
    {
        $this->assertStatus($request, IdCardRequestStatusEnum::PENDING, IdCardRequestStatusEnum::REJECTED);

        $request->update([
            'status' => IdCardRequestStatusEnum::REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return $request->fresh() ?? $request;
    }

    public function print(StudentIdCardRequest $request, User $admin): StudentIdCardPrintResult
    {
        if ($request->status === IdCardRequestStatusEnum::PRINTED && filled($request->serial_number)) {
            return $this->printer->print($request->loadMissing(['student.user', 'photo']));
        }

        $this->assertStatus($request, IdCardRequestStatusEnum::APPROVED, IdCardRequestStatusEnum::PRINTED);
        $this->assertPrintablePhoto($request);

        return DB::transaction(function () use ($request, $admin): StudentIdCardPrintResult {
            $request->loadMissing('student');
            $serial = $request->serial_number
                ?: $this->serialGenerator->generate($request->student, $request);

            $request->update([
                'status' => IdCardRequestStatusEnum::PRINTED,
                'printed_by' => $admin->id,
                'printed_at' => now(),
                'serial_number' => $serial,
            ]);

            return $this->printer->print($request->fresh(['student.user', 'photo', 'student.latestEnrolment.departmentCourse.course']) ?? $request);
        });
    }

    public function hasApprovedReadyToPrint(): bool
    {
        return $this->approvedPrintableQuery()->exists();
    }

    public function exportApproved(): ?StudentIdCardPrintResult
    {
        $requests = $this->approvedPrintableQuery()
            ->with([
                ...StudentIdCardFace::requestRelations(),
                'photo',
            ])
            ->orderBy('id')
            ->get();

        if ($requests->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($requests): StudentIdCardPrintResult {
            $printable = $requests->map(function (StudentIdCardRequest $request): StudentIdCardRequest {
                if (filled($request->serial_number)) {
                    return $request;
                }

                $request->loadMissing('student');
                $request->update([
                    'serial_number' => $this->serialGenerator->generate($request->student, $request),
                ]);

                return $request->fresh([
                    ...StudentIdCardFace::requestRelations(),
                    'photo',
                ]) ?? $request;
            });

            return $this->printer->printMany($printable);
        });
    }

    public function issue(StudentIdCardRequest $request, User $admin): StudentIdCardRequest
    {
        $this->assertStatus($request, IdCardRequestStatusEnum::PRINTED, IdCardRequestStatusEnum::ISSUED);

        return DB::transaction(function () use ($request, $admin): StudentIdCardRequest {
            $previous = $this->activeIssuedRequest($request->student_id, $request->id);

            $request->update([
                'status' => IdCardRequestStatusEnum::ISSUED,
                'issued_by' => $admin->id,
                'issued_at' => now(),
                'supersedes_request_id' => $previous?->id,
            ]);

            return $request->fresh() ?? $request;
        });
    }

    public function importApproved(Student $student, User $admin): StudentIdCardRequest
    {
        $this->assertHasStudentNumber($student);
        $this->assertNoActiveRequest($student);

        $photo = $this->photoService->ensureMediaFromPrintFolder($student);
        if (! $photo instanceof Media) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_photo_required');
        }

        return DB::transaction(function () use ($student, $admin, $photo): StudentIdCardRequest {
            $request = $student->idCardRequests()->create([
                'tenant_id' => $student->tenant_id,
                'status' => IdCardRequestStatusEnum::APPROVED,
                'reason' => IdCardRequestReasonEnum::NEW,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $this->snapshotPhoto($photo, $request);

            return $request->fresh(['photo']) ?? $request;
        });
    }

    public function feeAmount(): float
    {
        return (float) config('id_cards.reissue_fee', config('custom.system.autoCardFee', 45));
    }

    private function snapshotPhoto(Media $photo, StudentIdCardRequest $request): StudentIdCardRequest
    {
        $copy = $photo->copy($request, StudentIdCardRequest::MEDIA_COLLECTION);

        $request->update([
            'photo_media_id' => $copy->id,
        ]);

        return $request->fresh(['photo']) ?? $request;
    }

    private function createReissueInvoice(StudentIdCardRequest $request): void
    {
        $feeType = FeeType::query()->firstOrCreate(
            ['slug' => FeeTypeEnum::STUDENT_ID_FEE->slug()],
            [
                'name' => FeeTypeEnum::STUDENT_ID_FEE->name(),
                'description' => FeeTypeEnum::STUDENT_ID_FEE->description(),
                'position' => FeeTypeEnum::STUDENT_ID_FEE->position(),
            ],
        );

        $invoice = $request->ledgerTransactions()->create([
            'tenant_id' => $request->tenant_id,
            'fee_type_id' => $feeType->id,
            'type' => 'invoice',
            'payment_status' => 'pending',
            'amount' => $this->feeAmount(),
            'currency' => 'USD',
            'system_reference' => 'IDCARD-'.$request->id,
        ]);

        $request->update(['fee_ledger_id' => $invoice->id]);
    }

    private function assertHasStudentNumber(Student $student): void
    {
        if (trim((string) $student->student_number) === '') {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_student_number_required');
        }
    }

    private function assertNoActiveRequest(Student $student): void
    {
        $exists = $student->idCardRequests()
            ->whereIn('status', array_map(
                fn (IdCardRequestStatusEnum $status): string => $status->value,
                IdCardRequestStatusEnum::activeStatuses(),
            ))
            ->exists();

        if ($exists) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_active_request_exists');
        }
    }

    private function assertStatus(
        StudentIdCardRequest $request,
        IdCardRequestStatusEnum $expected,
        IdCardRequestStatusEnum $target,
    ): void {
        if ($request->status !== $expected) {
            throw InvalidIdCardRequestTransitionException::cannotTransition(
                $request->status ?? IdCardRequestStatusEnum::PENDING,
                $target,
            );
        }
    }

    private function assertPrintablePhoto(StudentIdCardRequest $request): void
    {
        if (! $this->hasPrintablePhoto($request)) {
            throw InvalidIdCardRequestTransitionException::because('students.id_card_photo_required');
        }
    }

    private function hasPrintablePhoto(StudentIdCardRequest $request): bool
    {
        return $request->photo_media_id !== null
            || $request->getFirstMedia(StudentIdCardRequest::MEDIA_COLLECTION) !== null;
    }

    /**
     * @return Builder<StudentIdCardRequest>
     */
    private function approvedPrintableQuery(): Builder
    {
        return StudentIdCardRequest::query()
            ->where('status', IdCardRequestStatusEnum::APPROVED)
            ->where(function (Builder $query): void {
                $query->whereNotNull('photo_media_id')
                    ->orWhereHas('media', function (Builder $media): void {
                        $media->where('collection_name', StudentIdCardRequest::MEDIA_COLLECTION);
                    });
            });
    }

    private function activeIssuedRequest(int $studentId, int $exceptId): ?StudentIdCardRequest
    {
        $issued = StudentIdCardRequest::query()
            ->where('student_id', $studentId)
            ->where('status', IdCardRequestStatusEnum::ISSUED)
            ->whereKeyNot($exceptId)
            ->get();

        $supersededIds = StudentIdCardRequest::query()
            ->whereIn('supersedes_request_id', $issued->modelKeys())
            ->pluck('supersedes_request_id');

        return $issued->first(fn (StudentIdCardRequest $card): bool => ! $supersededIds->contains($card->id));
    }
}

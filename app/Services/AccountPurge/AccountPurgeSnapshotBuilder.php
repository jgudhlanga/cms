<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AccountPurgeSnapshotBuilder
{
    public const PAYLOAD_VERSION = 1;

    /**
     * @return array<string, mixed>
     */
    public function buildForUser(User $user): array
    {
        $user->loadMissing(['roles', 'permissions']);

        return [
            'version' => self::PAYLOAD_VERSION,
            'user' => $this->sanitizeUser($user->toArray()),
            'preferences' => $user->preference?->toArray(),
            'application_fees' => $user->applicationFees()->get()->map->toArray()->all(),
            'ledgers' => $this->snapshotLedgers($user->ledgerTransactions()->withTrashed()->get()),
            'roles' => $user->roles->pluck('name')->all(),
            'permissions' => $user->permissions->pluck('name')->all(),
            'tokens' => $user->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])->map->toArray()->all(),
            'notifications_count' => $user->notifications()->count(),
            'sessions_count' => (int) DB::table('sessions')->where('user_id', $user->id)->count(),
            'media' => $this->snapshotUserMedia($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForStudent(Student $student): array
    {
        $student->loadMissing(['user.roles', 'user.permissions']);
        $user = $student->user;

        $applicationIds = $student->applications()->withTrashed()->pluck('id');
        $enrolmentIds = $student->enrolments()->withTrashed()->pluck('id');

        $payload = [
            'version' => self::PAYLOAD_VERSION,
            'student' => $student->toArray(),
            'user' => $user ? $this->sanitizeUser($user->toArray()) : null,
            'applications' => $this->snapshotApplications($applicationIds),
            'enrolments' => DB::table('student_enrolments')
                ->whereIn('id', $enrolmentIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'course_work_marks' => DB::table('course_work_marks')
                ->whereIn('student_enrolment_id', $enrolmentIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'course_work_audit_logs' => DB::table('course_work_audit_logs')
                ->whereIn('student_enrolment_id', $enrolmentIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'academic_calendar_student_enrolments' => DB::table('academic_calendar_student_enrolments')
                ->whereIn('student_enrolment_id', $enrolmentIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'class_lists' => DB::table('class_lists')
                ->whereIn('student_application_id', $applicationIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'sponsors' => $student->sponsors()->withTrashed()->get()->map->toArray()->all(),
            'academic_records' => $student->academicRecord()->withTrashed()->get()->map->toArray()->all(),
            'student_academic_results' => $student->oLevelResults()->withTrashed()->get()->map->toArray()->all(),
            'student_apprentices' => DB::table('student_apprentices')
                ->where('student_id', $student->id)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'hostel_applications' => $student->hostelApplications()->withTrashed()->get()->map->toArray()->all(),
            'hostel_room_allocations' => $student->hostelRoomAllocations()->withTrashed()->get()->map->toArray()->all(),
            'hostel_queries' => $student->hostelQueries()->withTrashed()->get()->map->toArray()->all(),
            'hostel_leaves' => $student->hostelLeaves()->withTrashed()->get()->map->toArray()->all(),
            'hostel_notice_student' => DB::table('hostel_notice_student')
                ->where('student_id', $student->id)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all(),
            'finance_transaction_queries' => $student->financeTransactionQueries()->get()->map->toArray()->all(),
            'contacts' => $student->contacts()->withTrashed()->get()->map->toArray()->all(),
            'addresses' => $student->addresses()->withTrashed()->get()->map->toArray()->all(),
            'next_of_kin' => $student->nextOfKins()->withTrashed()->get()->map->toArray()->all(),
            'student_notes' => $student->notes()->withTrashed()->get()->map->toArray()->all(),
        ];

        if ($user) {
            $payload['preferences'] = $user->preference?->toArray();
            $payload['application_fees'] = $user->applicationFees()->get()->map->toArray()->all();
            $payload['ledgers'] = $this->snapshotLedgers($user->ledgerTransactions()->withTrashed()->get());
            $payload['roles'] = $user->roles->pluck('name')->all();
            $payload['permissions'] = $user->permissions->pluck('name')->all();
            $payload['tokens'] = $user->tokens()->get(['id', 'name', 'abilities', 'last_used_at', 'expires_at', 'created_at'])->map->toArray()->all();
            $payload['notifications_count'] = $user->notifications()->count();
            $payload['sessions_count'] = (int) DB::table('sessions')->where('user_id', $user->id)->count();
            $payload['media'] = $this->snapshotUserMedia($user);
        }

        return $payload;
    }

    /**
     * @param  Collection<int, Ledger>  $ledgers
     * @return list<array<string, mixed>>
     */
    private function snapshotLedgers(Collection $ledgers): array
    {
        return $ledgers->map(function (Ledger $ledger): array {
            $data = $ledger->toArray();
            $data['receipt_media'] = $ledger->getMedia('receipts')->map(fn (Media $media) => $this->snapshotMedia($media))->all();

            if ($ledger->proof_of_payment_id) {
                $proof = Media::query()->find($ledger->proof_of_payment_id);
                $data['proof_of_payment_media'] = $proof ? $this->snapshotMedia($proof) : null;
            }

            return $data;
        })->all();
    }

    /**
     * @param  Collection<int, mixed>  $applicationIds
     * @return list<array<string, mixed>>
     */
    private function snapshotApplications(Collection $applicationIds): array
    {
        return StudentApplication::query()
            ->withTrashed()
            ->whereIn('id', $applicationIds)
            ->get()
            ->map(function (StudentApplication $application): array {
                $data = $application->toArray();
                $data['notes'] = $application->notes()->withTrashed()->get()->map->toArray()->all();
                $data['ledgers'] = $this->snapshotLedgers($application->ledgerTransactions()->withTrashed()->get());
                $data['offer_letter_media'] = $application->getMedia('offer-letter')->map(fn (Media $media) => $this->snapshotMedia($media))->all();

                return $data;
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function snapshotUserMedia(User $user): array
    {
        $media = $user->getMedia('user-avatar')
            ->merge($user->media)
            ->unique('id');

        if ($user->avatar_id) {
            $avatar = Media::query()->find($user->avatar_id);
            if ($avatar) {
                $media = $media->push($avatar)->unique('id');
            }
        }

        return $media->map(fn (Media $item) => $this->snapshotMedia($item))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotMedia(Media $media): array
    {
        return [
            'id' => $media->id,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
            'collection_name' => $media->collection_name,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'disk' => $media->disk,
            'size' => $media->size,
            'path' => $media->getPath(),
            'custom_properties' => $media->custom_properties,
        ];
    }

    /**
     * @param  array<string, mixed>  $userData
     * @return array<string, mixed>
     */
    private function sanitizeUser(array $userData): array
    {
        unset($userData['password'], $userData['remember_token']);

        return $userData;
    }
}

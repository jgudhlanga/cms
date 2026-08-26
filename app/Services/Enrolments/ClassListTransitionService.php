<?php

namespace App\Services\Enrolments;

use App\DTO\Enrolments\ClassListDto;
use App\Enums\Shared\ClassListTypeEnum;
use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Users\User;
use App\Repositories\Institution\interface\IClassListRepository;
use App\Services\DepartmentEnrolmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClassListTransitionService
{
    /** @var array<string, list<string>> */
    private const ALLOWED = [
        ClassListTypeEnum::PROVISIONAL->value => [
            ClassListTypeEnum::WAITING->value,
            ClassListTypeEnum::VERIFIED->value,
            ClassListTypeEnum::FAILED->value,
        ],
        ClassListTypeEnum::WAITING->value => [
            ClassListTypeEnum::PROVISIONAL->value,
            ClassListTypeEnum::VERIFIED->value,
            ClassListTypeEnum::FAILED->value,
        ],
        ClassListTypeEnum::VERIFIED->value => [
            ClassListTypeEnum::FINAL->value,
            ClassListTypeEnum::PROVISIONAL->value,
            ClassListTypeEnum::FAILED->value,
        ],
        ClassListTypeEnum::FINAL->value => [
            // Final entries are locked — no further status edits.
        ],
        ClassListTypeEnum::FAILED->value => [
            ClassListTypeEnum::PROVISIONAL->value,
        ],
    ];

    /** Rank for demote detection (higher = more advanced). */
    private const RANK = [
        ClassListTypeEnum::FAILED->value => 0,
        ClassListTypeEnum::WAITING->value => 1,
        ClassListTypeEnum::PROVISIONAL->value => 2,
        ClassListTypeEnum::VERIFIED->value => 3,
        ClassListTypeEnum::FINAL->value => 4,
    ];

    public function __construct(
        protected IClassListRepository $repository,
        protected DepartmentEnrolmentService $departmentEnrolmentService,
    ) {}

    /**
     * @param  list<int>  $applicationIds
     * @param  array{institution_department_id?: int|null, department_level_id?: int|null, department_course_id?: int|null, intake_period_id?: int|null, mode_of_study_id?: int|null}  $context
     * @return array{added: int, skipped: int}
     *
     * @throws Throwable
     */
    public function add(
        array $applicationIds,
        string $type,
        ?User $actor,
        ?string $note,
        bool $bypassRanking,
        array $context = [],
    ): array {
        $type = ClassListTypeEnum::from($type)->value;
        $ids = collect($applicationIds)->map(fn ($id) => (int) $id)->unique()->values();

        $alreadyListed = ClassList::query()
            ->whereIn('student_application_id', $ids)
            ->pluck('student_application_id');

        $toAdd = $ids->diff($alreadyListed)->values();

        if ($toAdd->isEmpty()) {
            return ['added' => 0, 'skipped' => $ids->count()];
        }

        $intakeMeta = $this->resolveIntakeMeta($context);
        $occupiesSeat = in_array($type, [
            ClassListTypeEnum::PROVISIONAL->value,
            ClassListTypeEnum::VERIFIED->value,
            ClassListTypeEnum::FINAL->value,
        ], true);
        $wouldExceedLimit = $occupiesSeat
            && $intakeMeta['intake_limit'] > 0
            && ($intakeMeta['listed_count'] + $toAdd->count()) > $intakeMeta['intake_limit'];

        $this->assertNoteWhenRequired(
            note: $note,
            requiresNote: $bypassRanking || $wouldExceedLimit,
            message: 'A note of at least 10 characters is required when bypassing ranking or exceeding the intake limit.',
        );

        DB::transaction(function () use ($toAdd, $type, $actor, $note, $bypassRanking, $context, $intakeMeta): void {
            foreach ($toAdd as $applicationId) {
                $dto = $this->makeDto((int) $applicationId, $type);
                $entry = $this->repository->create($dto);
                $this->dispatchProgress($entry, $type);
                $this->audit(
                    entry: $entry,
                    actor: $actor,
                    event: 'added',
                    message: 'Application added to class list',
                    properties: array_merge($context, $intakeMeta, [
                        'from_type' => null,
                        'to_type' => $type,
                        'note' => $note,
                        'bypass_ranking' => $bypassRanking,
                        'student_application_id' => (int) $applicationId,
                        'class_list_id' => $entry->id,
                        'application_ids' => [(int) $applicationId],
                    ]),
                );
            }
        });

        return ['added' => $toAdd->count(), 'skipped' => $alreadyListed->count()];
    }

    /**
     * @param  list<int>  $applicationIds
     * @param  array{institution_department_id?: int|null, department_level_id?: int|null, department_course_id?: int|null, intake_period_id?: int|null, mode_of_study_id?: int|null}  $context
     *
     * @throws Throwable
     */
    public function transition(
        array $applicationIds,
        string $toType,
        ?User $actor,
        ?string $note,
        bool $bypassRanking,
        array $context = [],
    ): int {
        $toType = ClassListTypeEnum::from($toType)->value;
        $ids = collect($applicationIds)->map(fn ($id) => (int) $id)->unique()->values();

        $entries = ClassList::query()
            ->whereIn('student_application_id', $ids)
            ->get();

        if ($entries->isEmpty()) {
            throw ValidationException::withMessages([
                'application_ids' => 'No class list entries found for the selected applications.',
            ]);
        }

        foreach ($entries as $entry) {
            $fromType = $entry->type instanceof ClassListTypeEnum
                ? $entry->type->value
                : (string) $entry->type;

            if (! $this->isAllowed($fromType, $toType)) {
                throw ValidationException::withMessages([
                    'to_type' => "Transition from {$fromType} to {$toType} is not allowed.",
                ]);
            }

            $requiresNote = $bypassRanking
                || $toType === ClassListTypeEnum::FAILED->value
                || $this->isDemote($fromType, $toType);

            $this->assertNoteWhenRequired(
                note: $note,
                requiresNote: $requiresNote,
                message: 'A note of at least 10 characters is required for this class list transition.',
            );
        }

        $intakeMeta = $this->resolveIntakeMeta($context);
        $changed = 0;

        DB::transaction(function () use ($entries, $toType, $actor, $note, $bypassRanking, $context, $intakeMeta, &$changed): void {
            foreach ($entries as $entry) {
                $fromType = $entry->type instanceof ClassListTypeEnum
                    ? $entry->type->value
                    : (string) $entry->type;

                $entry->type = $toType;
                $entry->save();

                $this->audit(
                    entry: $entry,
                    actor: $actor,
                    event: 'transitioned',
                    message: "Class list status changed from {$fromType} to {$toType}",
                    properties: array_merge($context, $intakeMeta, [
                        'from_type' => $fromType,
                        'to_type' => $toType,
                        'note' => $note,
                        'bypass_ranking' => $bypassRanking,
                        'student_application_id' => $entry->student_application_id,
                        'class_list_id' => $entry->id,
                        'application_ids' => [$entry->student_application_id],
                    ]),
                );
                $changed++;
            }
        });

        return $changed;
    }

    /**
     * @param  list<int>  $applicationIds
     * @param  array<string, mixed>  $context
     *
     * @throws Throwable
     */
    public function purge(array $applicationIds, User $actor, string $note, array $context = []): int
    {
        $ids = collect($applicationIds)->map(fn ($id) => (int) $id)->unique()->values();
        $purged = 0;
        $intakeMeta = $this->resolveIntakeMeta($context);

        $entries = ClassList::query()
            ->whereIn('student_application_id', $ids)
            ->get();

        foreach ($entries as $entry) {
            $fromType = $entry->type instanceof ClassListTypeEnum
                ? $entry->type->value
                : (string) $entry->type;

            if ($fromType === ClassListTypeEnum::FINAL->value) {
                throw ValidationException::withMessages([
                    'application_ids' => 'Final class list entries are locked and cannot be edited or removed from this page.',
                ]);
            }
        }

        DB::transaction(function () use ($entries, $actor, $note, $context, $intakeMeta, &$purged): void {
            foreach ($entries as $entry) {
                $fromType = $entry->type instanceof ClassListTypeEnum
                    ? $entry->type->value
                    : (string) $entry->type;

                $this->audit(
                    entry: $entry,
                    actor: $actor,
                    event: 'purged',
                    message: 'Class list entry permanently purged',
                    properties: array_merge($context, $intakeMeta, [
                        'from_type' => $fromType,
                        'to_type' => null,
                        'note' => $note,
                        'bypass_ranking' => false,
                        'type' => $fromType,
                        'student_application_id' => $entry->student_application_id,
                        'class_list_id' => $entry->id,
                        'application_ids' => [$entry->student_application_id],
                    ]),
                );

                $entry->forceDelete();
                $purged++;
            }
        });

        return $purged;
    }

    public function permissionForTargetType(string $toType): string
    {
        return match ($toType) {
            ClassListTypeEnum::VERIFIED->value => 'verify:class-lists',
            ClassListTypeEnum::FINAL->value => 'manage-final:class-lists',
            default => 'create:class-lists',
        };
    }

    public function permissionForTransition(string $fromType, string $toType): string
    {
        if ($fromType === ClassListTypeEnum::FINAL->value || $toType === ClassListTypeEnum::FINAL->value) {
            return 'manage-final:class-lists';
        }

        if ($fromType === ClassListTypeEnum::VERIFIED->value || $toType === ClassListTypeEnum::VERIFIED->value) {
            return 'verify:class-lists';
        }

        return 'create:class-lists';
    }

    public function isAllowed(string $fromType, string $toType): bool
    {
        return in_array($toType, self::ALLOWED[$fromType] ?? [], true);
    }

    public function isDemote(string $fromType, string $toType): bool
    {
        if ($toType === ClassListTypeEnum::FAILED->value) {
            return false;
        }

        return (self::RANK[$toType] ?? -1) < (self::RANK[$fromType] ?? -1);
    }

    /**
     * @return list<string>
     */
    public function allowedTargets(string $fromType): array
    {
        return self::ALLOWED[$fromType] ?? [];
    }

    private function makeDto(int $applicationId, string $type): ClassListDto
    {
        return new ClassListDto(
            student_application_id: $applicationId,
            type: $type,
            attributes: [
                'identity_confirmed' => false,
                'disability_confirmed' => false,
                'names_confirmed' => false,
                'o_level_confirmed' => false,
                'previous_level_confirmed' => false,
                'read_write_confirmed' => false,
                'application_fee_confirmed' => false,
                'proof_of_payment_confirmed' => false,
                'passport_photos_confirmed' => false,
                'original_birth_certificate_confirmed' => false,
                'original_national_identity_confirmed' => false,
                'original_education_certificates_confirmed' => false,
            ],
        );
    }

    private function dispatchProgress(ClassList $entry, string $type): void
    {
        $details = DB::table('class_lists as cl')
            ->join('student_applications as sp', 'sp.id', '=', 'cl.student_application_id')
            ->join('institution_departments as idp', 'idp.id', '=', 'sp.institution_department_id')
            ->join('departments as dp', 'dp.id', '=', 'idp.department_id')
            ->join('department_levels as dl', 'dl.id', '=', 'sp.department_level_id')
            ->join('levels as lv', 'lv.id', '=', 'dl.level_id')
            ->join('department_courses as dc', 'dc.id', '=', 'sp.department_course_id')
            ->join('courses as cs', 'cs.id', '=', 'dc.course_id')
            ->where('cl.id', $entry->id)
            ->select([
                'sp.institution_department_id',
                'dp.name as department',
                'lv.name as level',
                'cs.name as course',
            ])
            ->first();

        if ($details === null) {
            return;
        }

        SendEnrolmentProgressJob::dispatch(
            $entry->id,
            $type,
            $details->institution_department_id,
            $details->department,
            $details->level,
            $details->course,
        )->withoutDelay();
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function audit(ClassList $entry, ?User $actor, string $event, string $message, array $properties): void
    {
        $logger = activity('ClassList')
            ->performedOn($entry)
            ->event($event)
            ->withProperties($properties);

        if ($actor !== null) {
            $logger->causedBy($actor);
        }

        $logger->log($message);
    }

    private function assertNoteWhenRequired(?string $note, bool $requiresNote, string $message): void
    {
        if (! $requiresNote) {
            return;
        }

        if ($note === null || strlen(trim($note)) < 10) {
            throw ValidationException::withMessages([
                'note' => $message,
            ]);
        }
    }

    /**
     * @param  array{institution_department_id?: int|null, department_level_id?: int|null, department_course_id?: int|null, intake_period_id?: int|null, mode_of_study_id?: int|null}  $context
     * @return array{intake_limit: int, listed_count: int}
     */
    private function resolveIntakeMeta(array $context): array
    {
        $departmentId = (int) ($context['institution_department_id'] ?? 0);
        $levelId = (int) ($context['department_level_id'] ?? 0);
        $courseId = (int) ($context['department_course_id'] ?? 0);
        $intakeId = (int) ($context['intake_period_id'] ?? 0);
        $modeId = (int) ($context['mode_of_study_id'] ?? 0);

        if ($departmentId === 0 || $levelId === 0 || $courseId === 0 || $intakeId === 0 || $modeId === 0) {
            return ['intake_limit' => 0, 'listed_count' => 0];
        }

        $department = InstitutionDepartment::query()->find($departmentId);
        if ($department === null) {
            return ['intake_limit' => 0, 'listed_count' => 0];
        }

        $intakeLimit = $this->departmentEnrolmentService->getClassSize(
            $department,
            $levelId,
            $courseId,
            $intakeId,
            $modeId,
        );

        $listedCount = ClassList::query()
            ->whereIn('type', [
                ClassListTypeEnum::PROVISIONAL->value,
                ClassListTypeEnum::VERIFIED->value,
                ClassListTypeEnum::FINAL->value,
            ])
            ->whereHas('studentApplication', function ($query) use ($departmentId, $levelId, $courseId, $intakeId, $modeId): void {
                $query->where('institution_department_id', $departmentId)
                    ->where('department_level_id', $levelId)
                    ->where('department_course_id', $courseId)
                    ->where('intake_period_id', $intakeId)
                    ->where('mode_of_study_id', $modeId);
            })
            ->count();

        return [
            'intake_limit' => $intakeLimit,
            'listed_count' => $listedCount,
        ];
    }
}

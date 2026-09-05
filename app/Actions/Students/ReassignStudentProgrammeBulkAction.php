<?php

declare(strict_types=1);

namespace App\Actions\Students;

use App\DTO\Students\ReassignStudentProgrammeDto;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentEnrolment;
use App\Models\Users\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ReassignStudentProgrammeBulkAction
{
    public function __construct(
        protected ReassignStudentProgrammeAction $reassign,
    ) {}

    /**
     * @param  list<int>  $applicationIds
     * @param  list<int>  $studentEnrolmentIds
     * @return array{moved: int, skipped: list<array{id: int, reason: string}>, class_unassigned: int}
     */
    public function execute(User $actor, array $applicationIds, array $studentEnrolmentIds, ReassignStudentProgrammeDto $target): array
    {
        $applications = $this->resolveApplications($applicationIds, $studentEnrolmentIds);
        $moved = 0;
        $classUnassigned = 0;
        $skipped = [];

        foreach ($applications as $application) {
            try {
                if (! $actor->can('update', $application)) {
                    throw new AuthorizationException;
                }

                $result = $this->reassign->execute($application, $target);

                if ($result['changed']) {
                    $moved++;
                }

                if ($result['class_unassigned']) {
                    $classUnassigned++;
                }
            } catch (AuthorizationException) {
                $skipped[] = [
                    'id' => (int) $application->id,
                    'reason' => __('students.reassign_programme_forbidden'),
                ];
            } catch (ValidationException $exception) {
                $skipped[] = [
                    'id' => (int) $application->id,
                    'reason' => collect($exception->errors())->flatten()->first() ?: __('students.reassign_programme_failed'),
                ];
            }
        }

        return [
            'moved' => $moved,
            'skipped' => $skipped,
            'class_unassigned' => $classUnassigned,
        ];
    }

    /**
     * @param  list<int>  $applicationIds
     * @param  list<int>  $studentEnrolmentIds
     * @return Collection<int, StudentApplication>
     */
    private function resolveApplications(array $applicationIds, array $studentEnrolmentIds): Collection
    {
        $fromEnrolments = $studentEnrolmentIds === []
            ? []
            : StudentEnrolment::query()
                ->whereIn('id', $studentEnrolmentIds)
                ->pluck('student_application_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();

        $ids = array_values(array_unique(array_filter(
            [...$applicationIds, ...$fromEnrolments],
            fn (int $id): bool => $id > 0,
        )));

        return StudentApplication::query()
            ->whereIn('id', $ids === [] ? [0] : $ids)
            ->get()
            ->keyBy('id');
    }
}

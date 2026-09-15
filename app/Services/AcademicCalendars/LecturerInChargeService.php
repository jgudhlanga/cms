<?php

namespace App\Services\AcademicCalendars;

use App\Models\AcademicCalendars\ClassConfig;
use App\Models\AcademicCalendars\ClassConfigLecturerInCharge;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Staff;
use App\Models\Users\User;
use Illuminate\Validation\ValidationException;

/**
 * The HOD assigns one Lecturer in Charge per class config. They follow capture progress for every class
 * and module in it and report to the HOD; they can view marks there but only capture where assigned.
 */
class LecturerInChargeService
{
    public function __construct(
        private readonly ClassStaffingService $classStaffingService,
    ) {}

    public function assign(ClassConfig $classConfig, ?int $staffId, User $assignedBy): ?ClassConfigLecturerInCharge
    {
        if ($staffId === null) {
            ClassConfigLecturerInCharge::query()->where('class_config_id', $classConfig->id)->get()->each->delete();

            return null;
        }

        $classConfig->loadMissing('institutionDepartment');
        $department = $classConfig->institutionDepartment;

        if (
            ! $department instanceof InstitutionDepartment
            || ! $this->classStaffingService->assertAcademicStaffInDepartment($department, [$staffId])
        ) {
            throw ValidationException::withMessages([
                'staff_id' => [__('academic_calendar.lecturer_in_charge_invalid_staff')],
            ]);
        }

        return ClassConfigLecturerInCharge::query()->updateOrCreate(
            ['class_config_id' => $classConfig->id],
            [
                'tenant_id' => $department->tenant_id,
                'staff_id' => $staffId,
                'assigned_by' => $assignedBy->id,
            ],
        );
    }

    /**
     * @return array{staffId: int, userId: int|null, name: string}|null
     */
    public function lecturerInChargeFor(ClassConfig $classConfig): ?array
    {
        $record = ClassConfigLecturerInCharge::query()
            ->with('staff')
            ->where('class_config_id', $classConfig->id)
            ->first();

        if (! $record instanceof ClassConfigLecturerInCharge || ! $record->staff instanceof Staff) {
            return null;
        }

        return [
            'staffId' => (int) $record->staff_id,
            'userId' => $record->staff->user_id !== null ? (int) $record->staff->user_id : null,
            'name' => $this->classStaffingService->orderedStaffNames([(int) $record->staff_id])[0] ?? '',
        ];
    }

    /**
     * @return list<int>
     */
    public function classConfigIdsForUser(User $user): array
    {
        $staff = $user->staffProfile;

        if (! $staff instanceof Staff) {
            return [];
        }

        return ClassConfigLecturerInCharge::query()
            ->where('staff_id', $staff->id)
            ->pluck('class_config_id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function isLecturerInCharge(User $user, int $classConfigId): bool
    {
        return in_array($classConfigId, $this->classConfigIdsForUser($user), true);
    }
}

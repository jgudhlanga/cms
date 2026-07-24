<?php

namespace App\Services\Dashboard;

use App\Enums\Rbac\RoleEnum;
use App\Enums\Rbac\RoleGroupEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Helpers\Helper;
use App\Helpers\RolePriorityHelper;
use App\Models\Institution\Staff;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StaffDashboardMetricsService
{
    /** @var list<string> */
    private const LECTURER_ROLE_SLUGS = [
        RoleEnum::LECTURER->value,
        RoleEnum::SENIOR_LECTURER->value,
        RoleEnum::LECTURER_IN_CHARGE->value,
        RoleEnum::HEAD_OF_DEPARTMENT->value,
    ];

    protected bool $isDepartmentUser = false;

    /** @var list<int> */
    protected array $userDepartments = [];

    /** @var Collection<int, Staff>|null */
    private ?Collection $cachedStaffMembers = null;

    public function __construct()
    {
        $this->isDepartmentUser = Helper::isDepartmentUser();
        $this->userDepartments = Helper::resolveUserDepartments() ?? [];
    }

    /**
     * @return array{
     *     summary: array<string, int|null>,
     *     lecturerRatios: list<array<string, mixed>>,
     *     categoryBreakdown: array<string, mixed>,
     *     academicGenderSplit: array{male: int, female: int, other: int},
     *     overCapacityRooms: list<array<string, mixed>>,
     *     attendanceTrend: null
     * }
     */
    public function build(): array
    {
        return [
            'summary' => $this->summary(),
            'lecturerRatios' => $this->lecturerRatiosByDepartment(),
            'categoryBreakdown' => $this->categoryBreakdown(),
            'academicGenderSplit' => $this->academicGenderSplit(),
            'overCapacityRooms' => [],
            'attendanceTrend' => null,
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function summary(): array
    {
        $staffMembers = $this->staffMembers();
        $academicCount = 0;
        $adminCount = 0;

        foreach ($staffMembers as $staff) {
            $category = $this->resolveCategoryKey($staff);

            if ($category === 'academic') {
                $academicCount++;
            } elseif (in_array($category, ['admin', 'support'], true)) {
                $adminCount++;
            }
        }

        return [
            'totalStaff' => $staffMembers->count(),
            'academicCount' => $academicCount,
            'adminCount' => $adminCount,
            'presentToday' => null,
            'onLeaveToday' => null,
            'unfilledSessions' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lecturerRatiosByDepartment(): array
    {
        $intakePeriod = Helper::resolveIntakePeriod();

        if (! $intakePeriod || ($this->isDepartmentUser && empty($this->userDepartments))) {
            return [];
        }

        $studentCounts = $this->confirmedStudentsByDepartment($intakePeriod->id);
        $lecturerCounts = $this->lecturersByDepartment();

        $departmentIds = $studentCounts->keys()
            ->merge($lecturerCounts->keys())
            ->unique()
            ->values();

        $rows = $departmentIds->map(function (int $departmentId) use ($studentCounts, $lecturerCounts): array {
            $studentRow = $studentCounts->get($departmentId);
            $lecturerRow = $lecturerCounts->get($departmentId);
            $studentCount = (int) ($studentRow->student_count ?? 0);
            $lecturerCount = (int) ($lecturerRow->lecturer_count ?? 0);
            $departmentName = (string) ($studentRow->department_name ?? $lecturerRow->department_name ?? '');

            $ratio = $lecturerCount > 0 ? (int) round($studentCount / $lecturerCount) : null;

            return [
                'departmentId' => $departmentId,
                'departmentName' => $departmentName,
                'studentCount' => $studentCount,
                'lecturerCount' => $lecturerCount,
                'ratio' => $ratio,
                'ratioLabel' => $ratio !== null ? '1:'.$ratio : 'N/A',
                'barPercent' => 0,
            ];
        })->values()->all();

        $maxRatio = collect($rows)
            ->pluck('ratio')
            ->filter()
            ->max() ?? 0;

        if ($maxRatio > 0) {
            $rows = array_map(function (array $row) use ($maxRatio): array {
                if ($row['ratio'] !== null) {
                    $row['barPercent'] = (int) round(($row['ratio'] / $maxRatio) * 100);
                }

                return $row;
            }, $rows);
        }

        usort($rows, fn (array $left, array $right): int => ($right['ratio'] ?? 0) <=> ($left['ratio'] ?? 0));

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryBreakdown(): array
    {
        $staffMembers = $this->staffMembers();
        $categoryCounts = [
            'academic' => 0,
            'admin' => 0,
            'support' => 0,
        ];

        $fullTimeLecturers = 0;
        $partTimeLecturers = 0;

        foreach ($staffMembers as $staff) {
            $category = $this->resolveCategoryKey($staff);
            if (array_key_exists($category, $categoryCounts)) {
                $categoryCounts[$category]++;
            }

            if (! $this->isLecturer($staff)) {
                continue;
            }

            $employmentType = $staff->employmentType?->name;

            if ($employmentType === EmploymentTypeEnum::FULL_TIME->value) {
                $fullTimeLecturers++;
            } elseif ($employmentType === EmploymentTypeEnum::PART_TIME->value) {
                $partTimeLecturers++;
            }
        }

        $total = array_sum($categoryCounts);
        $segments = collect([
            ['key' => 'academic', 'label' => __('dashboard.staff_category_academic'), 'color' => 'bg-blue-500'],
            ['key' => 'admin', 'label' => __('dashboard.staff_category_admin'), 'color' => 'bg-indigo-500'],
            ['key' => 'support', 'label' => __('dashboard.staff_category_support'), 'color' => 'bg-gray-400'],
        ])->map(function (array $segment) use ($categoryCounts, $total): array {
            $count = $categoryCounts[$segment['key']];

            return [
                'key' => $segment['key'],
                'label' => $segment['label'],
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
                'color' => $segment['color'],
            ];
        })->filter(fn (array $segment): bool => $segment['count'] > 0)->values()->all();

        return [
            'segments' => $segments,
            'fullTimeLecturers' => $fullTimeLecturers,
            'partTimeLecturers' => $partTimeLecturers,
            'postgradQualified' => null,
            'onStudyLeave' => null,
        ];
    }

    /**
     * @return array{male: int, female: int, other: int}
     */
    private function academicGenderSplit(): array
    {
        $split = ['male' => 0, 'female' => 0, 'other' => 0];

        foreach ($this->staffMembers() as $staff) {
            if ($this->resolveCategoryKey($staff) !== 'academic') {
                continue;
            }

            $genderTitle = $staff->gender?->title;

            if ($genderTitle === 'Male') {
                $split['male']++;
            } elseif ($genderTitle === 'Female') {
                $split['female']++;
            } else {
                $split['other']++;
            }
        }

        return $split;
    }

    /**
     * @return Collection<int, Staff>
     */
    private function staffMembers(): Collection
    {
        return $this->cachedStaffMembers ??= $this->staffBaseQuery()
            ->with(['user.roles', 'employmentType', 'gender'])
            ->get();
    }

    private function staffBaseQuery(): Builder
    {
        $query = Staff::query();

        if ($this->isDepartmentUser) {
            $query->whereHas('institutionDepartments', function (Builder $builder): void {
                $builder->whereIn('institution_departments.id', $this->userDepartments);
            });
        }

        return $query;
    }

    private function resolveCategoryKey(Staff $staff): string
    {
        $user = $staff->user;

        if (! $user instanceof User) {
            return 'admin';
        }

        $primaryRoleName = RolePriorityHelper::resolvePrimaryRoleName($user);
        $primaryRole = $user->roles->firstWhere('name', $primaryRoleName);
        $slug = $primaryRole?->slug;

        if (! is_string($slug)) {
            return 'admin';
        }

        foreach (RoleEnum::cases() as $roleEnum) {
            if ($roleEnum->value !== $slug) {
                continue;
            }

            return match ($roleEnum->group()) {
                RoleGroupEnum::ACADEMIC->value => 'academic',
                RoleGroupEnum::SERVICE_AND_SUPPORT->value => 'support',
                default => 'admin',
            };
        }

        return 'admin';
    }

    private function isLecturer(Staff $staff): bool
    {
        $user = $staff->user;

        if (! $user instanceof User) {
            return false;
        }

        return $user->roles->contains(
            fn ($role): bool => in_array($role->slug, self::LECTURER_ROLE_SLUGS, true)
        );
    }

    /**
     * @return Collection<int, array{department_name: string, student_count: int}>
     */
    private function confirmedStudentsByDepartment(int $intakePeriodId): Collection
    {
        $query = DB::table('departments')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(DISTINCT student_applications.id) as student_count'),
            )
            ->join('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->join('student_applications', 'student_applications.institution_department_id', '=', 'institution_departments.id')
            ->where('departments.is_academic', true)
            ->where('student_applications.intake_period_id', $intakePeriodId)
            ->whereNull('student_applications.deleted_at')
            ->whereExists(function (QueryBuilder $exists): void {
                $exists->select(DB::raw(1))
                    ->from('class_lists')
                    ->whereColumn('class_lists.student_application_id', 'student_applications.id')
                    ->whereNull('class_lists.deleted_at')
                    ->where('class_lists.attributes->identity_confirmed', true)
                    ->where('class_lists.attributes->disability_confirmed', true)
                    ->where('class_lists.attributes->names_confirmed', true);
            });

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        return $query
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->keyBy('department_id');
    }

    /**
     * @return Collection<int, array{department_name: string, lecturer_count: int}>
     */
    private function lecturersByDepartment(): Collection
    {
        $query = DB::table('departments')
            ->select(
                'departments.id as department_id',
                'departments.name as department_name',
                DB::raw('COUNT(DISTINCT staff.id) as lecturer_count'),
            )
            ->join('institution_departments', 'institution_departments.department_id', '=', 'departments.id')
            ->join('institution_department_staff', 'institution_department_staff.institution_department_id', '=', 'institution_departments.id')
            ->join('staff', 'staff.id', '=', 'institution_department_staff.staff_id')
            ->join('users', 'users.id', '=', 'staff.user_id')
            ->join('model_has_roles', function ($join): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('departments.is_academic', true)
            ->whereNull('staff.deleted_at')
            ->whereIn('roles.slug', self::LECTURER_ROLE_SLUGS);

        if ($this->isDepartmentUser) {
            $query->whereIn('institution_departments.id', $this->userDepartments);
        }

        return $query
            ->groupBy('departments.id', 'departments.name')
            ->get()
            ->keyBy('department_id');
    }
}

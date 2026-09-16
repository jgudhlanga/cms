<?php

use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\Institution\Course;
use App\Models\Institution\Department;
use App\Models\Institution\DepartmentCourse;
use App\Models\Institution\DepartmentLevel;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\Staff;
use App\Models\Rbac\Role;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Services\Setup\SetupGapScanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (! function_exists('makeSetupGapClassContext')) {
    /**
     * An open class for the current calendar year: the subject of the class-level setup checks.
     *
     * @param  array{course_syllabus_ids?: list<int>}  $overrides
     * @return array{tenantId: int, institutionDepartment: InstitutionDepartment, classConfigId: int, calendarYear: string}
     */
    function makeSetupGapClassContext(array $overrides = []): array
    {
        $tenant = Tenant::query()->firstOrFail();

        $calendar = AcademicCalendar::query()->firstOrCreate(
            ['calendar_year' => (string) now()->format('Y'), 'type' => 'semester'],
            [
                'opening_date' => now()->subDays(10)->toDateString(),
                'closing_date' => now()->addMonths(6)->toDateString(),
            ],
        );

        $department = Department::factory()->create(['name' => 'Setup Gaps '.Str::random(6)]);
        $institutionDepartment = InstitutionDepartment::query()->create([
            'tenant_id' => $tenant->id,
            'department_id' => $department->id,
            'department_code' => 'sg-'.Str::lower(Str::random(6)),
            'description' => 'Setup gap scanning',
        ]);

        $departmentCourse = DepartmentCourse::query()->create([
            'tenant_id' => $tenant->id,
            'institution_department_id' => $institutionDepartment->id,
            'course_id' => Course::factory()->create()->id,
        ]);

        $departmentLevel = DepartmentLevel::query()->create([
            'tenant_id' => $tenant->id,
            'institution_department_id' => $institutionDepartment->id,
            'level_id' => Level::factory()->create([
                'name' => 'ND '.Str::random(4),
                'calendar_type' => 'semester',
            ])->id,
        ]);

        $classConfigId = DB::table('class_configs')->insertGetId([
            'institution_department_id' => $institutionDepartment->id,
            'department_course_id' => $departmentCourse->id,
            'department_level_id' => $departmentLevel->id,
            'mode_of_study_id' => ModeOfStudy::query()->create(['name' => 'Full Time '.Str::random(5)])->id,
            'calendar_year' => (string) $calendar->calendar_year,
            'name' => 'ND 1 Class',
            'kind' => 'standard',
            'slug' => 'standard',
            'status' => 'open',
            'course_syllabus_ids' => json_encode($overrides['course_syllabus_ids'] ?? []),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'tenantId' => (int) $tenant->id,
            'institutionDepartment' => $institutionDepartment,
            'classConfigId' => $classConfigId,
            'calendarYear' => (string) $calendar->calendar_year,
        ];
    }
}

if (! function_exists('makeSetupGapStaffUser')) {
    /**
     * A staff user in the given role, optionally attached to a department (how heads of department are
     * resolved).
     */
    function makeSetupGapStaffUser(int $tenantId, string $roleSlug, ?InstitutionDepartment $department = null): User
    {
        $user = User::factory()->create(['tenant_id' => $tenantId]);
        $user->assignRole(Role::query()->where('slug', $roleSlug)->firstOrFail());

        $staff = Staff::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'employee_number' => 'SG-'.strtoupper(uniqid()),
            'title_id' => Title::query()->create(['name' => 'Mr '.uniqid()])->id,
            'gender_id' => Gender::query()->create(['title' => 'Gender '.uniqid()])->id,
            'marital_status_id' => MaritalStatus::query()->create(['title' => 'Single '.uniqid()])->id,
        ]);

        if ($department instanceof InstitutionDepartment) {
            $staff->institutionDepartments()->attach($department->id);
        }

        return $user;
    }
}

if (! function_exists('scanSetupGaps')) {
    /**
     * @return array{raised: int, reopened: int, resolved: int, seen: int, failed: list<string>}
     */
    function scanSetupGaps(): array
    {
        return app(SetupGapScanner::class)->scan();
    }
}

if (! function_exists('makeSetupGapDivisionHead')) {
    /**
     * A head of division. The divisions table naming their staff record is what widens their scope
     * from one department to every department under them — the role alone does not.
     *
     * @param  list<InstitutionDepartment>  $departments
     */
    function makeSetupGapDivisionHead(int $tenantId, array $departments): User
    {
        $user = makeSetupGapStaffUser($tenantId, 'head-of-division');

        $divisionId = DB::table('divisions')->insertGetId([
            'name' => 'Division '.Str::random(6),
            'head_of_division_id' => DB::table('staff')->where('user_id', $user->id)->value('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($departments as $department) {
            DB::table('institution_departments')
                ->where('id', $department->id)
                ->update(['division_id' => $divisionId]);
        }

        return $user->fresh();
    }
}

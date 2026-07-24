<?php

use App\Enums\AcademicCalendars\AcademicCalendarTypeEnum;
use App\Enums\AcademicCalendars\ClassMetaDataTypeEnum;
use App\Enums\Rbac\RoleEnum;
use App\Enums\Shared\EmploymentTypeEnum;
use App\Models\AcademicCalendars\AcademicCalendar;
use App\Models\AcademicCalendars\AcademicCalendarClassMetaData;
use App\Models\AcademicCalendars\ClassMetaDataType;
use App\Models\Rbac\Permission;
use App\Models\Rbac\Role;
use App\Models\Institution\Staff;
use App\Models\Shared\EmploymentType;
use App\Models\Shared\Gender;
use App\Models\Shared\MaritalStatus;
use App\Models\Shared\Title;
use App\Models\Users\User;
use Illuminate\Support\Facades\DB;

if (! function_exists('createLecturerUserWithStaff')) {
    /**
     * @param  array<string, mixed>  $context
     * @return array{0: User, 1: Staff}
     */
    function createLecturerUserWithStaff(array $context): array
    {
        $lecturerUser = User::factory()->create([
            'tenant_id' => $context['tenant']->id,
            'first_name' => 'Ada',
            'last_name' => 'Lecturer',
        ]);
        $lecturerUser->assignRole(Role::query()->where('name', RoleEnum::LECTURER->name())->firstOrFail());

        foreach ([
            'view:lecturer-dashboard',
            'view:lecturer-classes',
            'view:lecturer-modules',
            'viewAny:course-work',
            'view:course-work',
            'create:course-work',
            'update:course-work',
            'import:course-work',
            'export:course-work',
            'view:academic-calendars',
        ] as $permission) {
            Permission::findOrCreate($permission, 'web');
            $lecturerUser->givePermissionTo($permission);
        }

        $staff = Staff::query()->create([
            'tenant_id' => $context['tenant']->id,
            'user_id' => $lecturerUser->id,
            'title_id' => Title::query()->firstOrCreate(['name' => 'Ms'])->id,
            'gender_id' => Gender::query()->firstOrCreate(['title' => 'Female'])->id,
            'marital_status_id' => MaritalStatus::query()->firstOrCreate(['title' => 'Single'])->id,
            'employment_type_id' => EmploymentType::query()->firstOrCreate([
                'name' => EmploymentTypeEnum::FULL_TIME->value,
            ], [
                'description' => EmploymentTypeEnum::FULL_TIME->description(),
            ])->id,
            'employee_number' => 'LECT-PORTAL-'.uniqid(),
        ]);

        return [$lecturerUser, $staff];
    }
}

if (! function_exists('assignLecturerToClassModule')) {
    /**
     * @param  array<string, mixed>  $context
     */
    function assignLecturerToClassModule(array $context, Staff $staff, bool $asTutor = true): void
    {
        DB::table('course_syllabus_module_lecturers')->insert([
            'tenant_id' => $context['tenant']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'staff_id' => $staff->id,
            'academic_calendar_class_id' => $context['academicCalendarClass']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (! $asTutor) {
            return;
        }

        $lecturerTypeId = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->value('id');

        AcademicCalendarClassMetaData::query()->create([
            'tenant_id' => $context['tenant']->id,
            'academic_calendar_class_id' => $context['academicCalendarClass']->id,
            'staff_id' => $staff->id,
            'class_metadata_type_id' => $lecturerTypeId,
        ]);
    }
}

if (! function_exists('assignClassTutorOnly')) {
    /**
     * @param  array<string, mixed>  $context
     */
    function assignClassTutorOnly(array $context, Staff $staff): void
    {
        $lecturerTypeId = ClassMetaDataType::query()
            ->where('name', ClassMetaDataTypeEnum::LECTURER->value)
            ->value('id');

        AcademicCalendarClassMetaData::query()->create([
            'tenant_id' => $context['tenant']->id,
            'academic_calendar_class_id' => $context['academicCalendarClass']->id,
            'staff_id' => $staff->id,
            'class_metadata_type_id' => $lecturerTypeId,
        ]);
    }
}

if (! function_exists('assignSyllabusTemplateLecturer')) {
    /**
     * @param  array<string, mixed>  $context
     */
    function assignSyllabusTemplateLecturer(array $context, Staff $staff): void
    {
        DB::table('course_syllabus_module_lecturers')->insert([
            'tenant_id' => $context['tenant']->id,
            'course_syllabus_module_id' => $context['module']->id,
            'staff_id' => $staff->id,
            'academic_calendar_class_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

if (! function_exists('prepareLecturerCalendar')) {
    /**
     * @param  array<string, mixed>  $context
     */
    function prepareLecturerCalendar(array $context): int
    {
        $calendarId = (int) $context['studentEnrolment']->academic_calendar_id;
        AcademicCalendar::query()->whereKey($calendarId)->update([
            'type' => AcademicCalendarTypeEnum::SEMESTER->value,
            'opening_date' => now()->subDays(10)->toDateString(),
            'closing_date' => now()->addMonths(3)->toDateString(),
        ]);
        seedDashboardIntakePeriod($context['tenant']->id);

        return $calendarId;
    }
}

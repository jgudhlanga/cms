<?php

namespace App\Helpers;

use App\Models\Rbac\Permission;
use App\Models\Rbac\RoleGroup;
use App\Support\Rbac\PermissionRegistry;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class PermissionHelper
{
    public static function getGroupId($slug): mixed
    {
        $roleGroup = RoleGroup::where('slug', $slug)->first();

        return $roleGroup->id ?? null;
    }

    public static function assignSuperUserPermissions($role): void
    {
        $excludedPermissions = collect(array_merge(
            self::portalPermissions(),
            ['manageOwnData:tenants', 'viewOnlyOwnDepartment:departments', 'viewOnlyOwnHostel:hostels']
        ));

        $permissionNames = collect(PermissionRegistry::allValues())
            ->reject(fn ($permission) => $excludedPermissions->contains($permission))
            ->values()
            ->all();

        $role->syncPermissions(self::resolvePermissions($permissionNames));
    }

    public static function portalPermissions(): array
    {
        return [
            'viewOwnDashboard:students',
            'manageOwnStudentPersonalDetails:students',
            'manageOwnStudentApplicationDetails:students',
            'manageOwnStudentSponsorDetails:students',
            'manageOwnStudentContactDetails:students',
            'manageOwnStudentFinancialDetails:students',
            'manageOwnStudentAcademicDetails:students',
            'manageOwnStudentAccommodationDetails:students',
            'viewOwnExamResults:students',
            'view:next-of-kins',
            'create:next-of-kins',
            'update:next-of-kins',
            'delete:next-of-kins',
            'forceDelete:next-of-kins',
        ];
    }

    /**
     * @return list<string>
     */
    public static function lecturerPermissions(): array
    {
        return [
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
        ];
    }

    /**
     * @return list<string>
     */
    public static function hodPermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                'view:dashboards',
                'view-academic:dashboards',
                'view-enrolment:dashboards',
                'viewOnlyOwnDepartment:departments',
                'viewAny:department-metadata',
                'view:department-metadata',
                'update:department-metadata',
                'department-setup:levels',
                'department-setup:courses',
                'department-setup:class-sizes',
                'viewAny:course-syllabuses',
                'view:course-syllabuses',
                'create:course-syllabuses',
                'update:course-syllabuses',
                'delete:course-syllabuses',
                'import:course-syllabuses',
                'export:course-syllabuses',
                'viewAny:course-syllabus-modules',
                'view:course-syllabus-modules',
                'create:course-syllabus-modules',
                'update:course-syllabus-modules',
                'delete:course-syllabus-modules',
                'import:course-syllabus-modules',
                'export:course-syllabus-modules',
                'viewAny:students',
                'view:students',
                'export:students',
                'viewAny:student-applications',
                'view:student-applications',
                'update:student-applications',
                'export:student-applications',
                'view:class-lists',
                'create:class-lists',
                'update:class-lists',
                'verify:class-lists',
                'viewAny:examinations',
                'view:examinations',
                'export:examinations',
                'viewAny:course-work',
                'view:course-work',
                'create:course-work',
                'update:course-work',
                'import:course-work',
                'export:course-work',
                'viewAny:assessment-calendar',
                'view:assessment-calendar',
                'viewAny:academic-calendars',
                'view:academic-calendars',
            ],
            self::lecturerPermissions()
        )));
    }

    /**
     * @return list<string>
     */
    public static function headOfDivisionPermissions(): array
    {
        return array_values(array_unique(array_merge(
            self::hodPermissions(),
            [
                'viewAny:department-metadata',
                'view:department-metadata',
            ]
        )));
    }

    /**
     * @return list<string>
     */
    public static function vpAcademicsPermissions(): array
    {
        return [
            'view:dashboards',
            'view-academic:dashboards',
            'view-enrolment:dashboards',
            'view-staff:dashboards',
            'view-examinations:dashboards',
            'viewAny:department-metadata',
            'view:department-metadata',
            'create:department-metadata',
            'update:department-metadata',
            'department-setup:levels',
            'department-setup:courses',
            'department-setup:class-sizes',
            'viewAny:course-syllabuses',
            'view:course-syllabuses',
            'create:course-syllabuses',
            'update:course-syllabuses',
            'viewAny:course-syllabus-modules',
            'view:course-syllabus-modules',
            'create:course-syllabus-modules',
            'update:course-syllabus-modules',
            'viewAny:students',
            'view:students',
            'export:students',
            'viewAny:student-applications',
            'view:student-applications',
            'update:student-applications',
            'export:student-applications',
            'view:class-lists',
            'create:class-lists',
            'update:class-lists',
            'verify:class-lists',
            'confirm:class-lists',
            'manage-final:class-lists',
            'viewAny:examinations',
            'view:examinations',
            'export:examinations',
            'viewAny:course-work',
            'view:course-work',
            'create:course-work',
            'update:course-work',
            'import:course-work',
            'export:course-work',
            'crud-settings:course-work',
            'viewAny:assessment-calendar',
            'view:assessment-calendar',
            'create:assessment-calendar',
            'update:assessment-calendar',
            'delete:assessment-calendar',
            'viewAny:academic-calendars',
            'view:academic-calendars',
            'create:academic-calendars',
            'update:academic-calendars',
            'delete:academic-calendars',
            'update:academic-calendar-student-enrolments',
            'toggle:coursework-capture',
            'view:missing-marks-report',
            'export:missing-marks-report',
            'escalate:missing-marks',
            'remind:missing-marks',
            'view:institution-settings',
            ...self::resourceAbilities(['divisions', 'departments', 'intake-periods', 'assessment-types'], ['viewAny', 'view', 'create', 'update']),
            ...self::resourceAbilities(['document-templates'], ['viewAny', 'view', 'create', 'update']),
            'viewAny:users',
            'view:users',
            'update:users',
            'viewAny:roles',
            'view:roles',
            'update:roles',
        ];
    }

    /**
     * @return list<string>
     */
    public static function idCardAdminPermissions(): array
    {
        return [
            'viewAny:student-id-card-requests',
            'view:student-id-card-requests',
            'review:student-id-card-requests',
            'print:student-id-card-requests',
            'issue:student-id-card-requests',
            'viewAuditTrail:student-id-card-requests',
            'view:student-id-card-settings',
            'update:student-id-card-settings',
        ];
    }

    /**
     * @return list<string>
     */
    public static function registrarPermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                'view:dashboards',
                'viewAny:students',
                'view:students',
                'export:students',
                'uploadIdPhoto:students',
                'viewAny:student-applications',
                'view:student-applications',
            ],
            self::idCardAdminPermissions()
        )));
    }

    /**
     * @return list<string>
     */
    public static function registryOfficerPermissions(): array
    {
        return self::registrarPermissions();
    }

    /**
     * @return list<string>
     */
    public static function vpAdminPermissions(): array
    {
        return array_values(array_unique(array_merge(
            [
                'view:dashboards',
                'view-finance:dashboards',
                'view-hostel:dashboards',
                'view-examinations:dashboards',
                'viewAny:finances',
                'view:finances',
                'export:finances',
                'export-to-pastel:finances',
                'viewAny:finance-settings',
                'view:finance-settings',
            ],
            self::deanPermissions(),
            self::idCardAdminPermissions()
        )));
    }

    /**
     * @return list<string>
     */
    public static function principalPermissions(): array
    {
        $settingsResources = [
            'genders',
            'countries',
            'languages',
            'provinces',
            'races',
            'statuses',
            'marital-statuses',
            'titles',
            'relationships',
            'address-types',
            'districts',
            'religions',
            'communication-methods',
            'employment-types',
            'id-types',
            'document-types',
            'fee-types',
            'sponsor-types',
            'workflow-steps',
            'workflow-step-actions',
            'academic-levels',
            'payment-methods',
            'payment-days',
            'payment-frequencies',
        ];

        $institutionConfigResources = [
            'divisions',
            'departments',
            'courses',
            'grades',
            'levels',
            'mode-of-studies',
            'subjects',
            'intake-periods',
            'assessment-types',
            'student-enrolment-statuses',
            'semesters',
        ];

        return array_values(array_unique(array_merge(
            self::vpAcademicsPermissions(),
            self::vpAdminPermissions(),
            [
                'viewAny:dashboards',
                'view-attendance:dashboards',
                'view-staff:dashboards',
                'view:acl-settings',
                'viewAny:permissions',
                'view:permissions',
                'create:users',
                'delete:users',
                'restore:users',
                'export:users',
                'create:roles',
                'view:settings',
                'view:institution-settings',
            ],
            self::resourceAbilities($settingsResources, ['viewAny', 'view', 'update']),
            self::resourceAbilities($institutionConfigResources, ['viewAny', 'view', 'update'])
        )));
    }

    /**
     * @param  list<string>  $resources
     * @param  list<string>  $abilities
     * @return list<string>
     */
    public static function resourceAbilities(array $resources, array $abilities): array
    {
        $permissions = [];
        foreach ($resources as $resource) {
            foreach ($abilities as $ability) {
                $permissions[] = "{$ability}:{$resource}";
            }
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function deanPermissions(): array
    {
        return [
            'view:dashboards',
            'view-hostel:dashboards',
            'viewAny:hostels',
            'view:hostels',
            'create:hostels',
            'update:hostels',
            'delete:hostels',
            'restore:hostels',
            'crud-settings:hostels',
            'viewAny:hostel-amenities',
            'view:hostel-amenities',
            'create:hostel-amenities',
            'update:hostel-amenities',
            'delete:hostel-amenities',
            'viewAny:hostel-rooms',
            'view:hostel-rooms',
            'create:hostel-rooms',
            'update:hostel-rooms',
            'delete:hostel-rooms',
            'viewAny:hostel-room-allocations',
            'view:hostel-room-allocations',
            'create:hostel-room-allocations',
            'update:hostel-room-allocations',
            'delete:hostel-room-allocations',
            'viewAny:hostel-applications',
            'view:hostel-applications',
            'create:hostel-applications',
            'update:hostel-applications',
            'delete:hostel-applications',
            'viewAny:hms-settings',
            'view:hms-settings',
            'create:hms-settings',
            'update:hms-settings',
            'viewAny:hostel-queries',
            'view:hostel-queries',
            'update:hostel-queries',
            'viewAny:hostel-leaves',
            'view:hostel-leaves',
            'update:hostel-leaves',
            'viewAny:hostel-notices',
            'view:hostel-notices',
            'create:hostel-notices',
            'update:hostel-notices',
            'manage:hostel-check-in',
            'import:hostel-applications',
            'confirm:hostel-payments',
        ];
    }

    /**
     * @return list<string>
     */
    public static function wardenPermissions(): array
    {
        return [
            'view:dashboards',
            'view-hostel:dashboards',
            'viewOnlyOwnHostel:hostels',
            'viewAny:hostels',
            'view:hostels',
            'update:hostels',
            'viewAny:hostel-amenities',
            'view:hostel-amenities',
            'update:hostel-amenities',
            'viewAny:hostel-rooms',
            'view:hostel-rooms',
            'update:hostel-rooms',
            'viewAny:hostel-room-allocations',
            'view:hostel-room-allocations',
            'update:hostel-room-allocations',
            'viewAny:hostel-applications',
            'view:hostel-applications',
            'update:hostel-applications',
            'viewAny:hostel-queries',
            'view:hostel-queries',
            'update:hostel-queries',
            'viewAny:hostel-leaves',
            'view:hostel-leaves',
            'update:hostel-leaves',
            'manage:hostel-check-in',
        ];
    }

    /**
     * @return list<string>
     */
    public static function itSupportTechnicianPermissions(): array
    {
        return [
            'view:dashboards',
            'view-hostel:dashboards',
            'viewAny:students',
            'view:students',
            'viewAny:users',
            'view:users',
            'update:users',
            'manage:data-maintenance',
            ...self::resourceAbilities([
                'hostels',
                'hostel-amenities',
                'hostel-rooms',
                'hostel-room-allocations',
                'hostel-applications',
                'hms-settings',
                'hostel-queries',
                'hostel-leaves',
                'hostel-notices',
            ], ['viewAny', 'view']),
        ];
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    public static function resolvePermissions(array $permissionNames): Collection
    {
        foreach ($permissionNames as $permissionName) {
            self::ensurePermissionExists($permissionName);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return Permission::query()
            ->whereIn('name', $permissionNames)
            ->where('guard_name', 'web')
            ->get();
    }

    public static function ensurePermissionExists(string $permissionName, string $guardName = 'web'): Permission
    {
        $permission = Permission::withTrashed()
            ->where('name', $permissionName)
            ->where('guard_name', $guardName)
            ->first();

        if ($permission instanceof Permission) {
            if ($permission->trashed()) {
                $permission->restore();
            }

            return $permission;
        }

        return Permission::query()->create([
            'name' => $permissionName,
            'guard_name' => $guardName,
        ]);
    }
}

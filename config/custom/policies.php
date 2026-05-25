<?php

use App\Policies\Dashboards\DashboardPolicy;
use App\Policies\Finance\FinancePolicy;
use App\Policies\Finance\FinanceSettingsPolicy;
use App\Policies\Institution\CourseSyllabusModulePolicy;
use App\Policies\Institution\CourseSyllabusPolicy;
use App\Policies\Institution\DepartmentMetaDataPolicy;
use App\Policies\Settings\InstitutionSetupPolicy;
use App\Policies\Settings\SettingPolicy;
use App\Policies\Students\PortalPolicy;
use App\Policies\Students\StudentMetaDataPolicy;

return [

    SettingPolicy::class => [
        'viewSettings',
        'createSettings',
        'updateSettings',
        'deleteSettings',
        'restoreSettings',
        'forceDeleteSettings',
        'importSettings',
        'exportSettings',
    ],

    InstitutionSetupPolicy::class => [
        'viewInstitutionSettings',
        'createInstitutionSettings',
        'updateInstitutionSettings',
        'deleteInstitutionSettings',
        'restoreInstitutionSettings',
        'forceDeleteInstitutionSettings',
        'importInstitutionSettings',
        'exportInstitutionSettings',
    ],

    DepartmentMetaDataPolicy::class => [
        'viewAnyDepartmentMetaData',
        'viewDepartmentMetaData',
        'createDepartmentMetaData',
        'updateDepartmentMetaData',
        'deleteDepartmentMetaData',
        'restoreDepartmentMetaData',
        'forceDeleteDepartmentMetaData',
        'importDepartmentMetaData',
        'exportDepartmentMetaData',
    ],

    CourseSyllabusPolicy::class => [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'forceDelete',
        'import',
        'export',
        'viewAuditTrail',
    ],

    CourseSyllabusModulePolicy::class => [
        'viewAny',
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'forceDelete',
        'import',
        'export',
        'viewAuditTrail',
    ],

    DashboardPolicy::class => [
        'viewDashboard',
    ],

    PortalPolicy::class => [
        'viewStudentDashboard',
        'manageStudentPersonalDetails',
        'manageStudentProgramDetails',
        'manageStudentSponsors',
        'manageStudentContacts',
        'manageStudentFinancialRecords',
        'manageStudentAcademicRecords',
    ],

    StudentMetaDataPolicy::class => [
        'manageStudentMetadata',
    ],

    FinancePolicy::class => [
        'viewFinances',
    ],

    FinanceSettingsPolicy::class => [
        'viewFinanceSettings',
        'createFinanceSettings',
        'updateFinanceSettings',
        'deleteFinanceSettings',
        'restoreFinanceSettings',
        'forceDeleteFinanceSettings',
    ],

];

<?php


return [

    App\Policies\Settings\SettingPolicy::class => [
        'viewSettings',
        'createSettings',
        'updateSettings',
        'deleteSettings',
        'restoreSettings',
        'forceDeleteSettings',
        'importSettings',
        'exportSettings',
    ],

    App\Policies\Settings\InstitutionSetupPolicy::class => [
        'viewInstitutionSettings',
        'createInstitutionSettings',
        'updateInstitutionSettings',
        'deleteInstitutionSettings',
        'restoreInstitutionSettings',
        'forceDeleteInstitutionSettings',
        'importInstitutionSettings',
        'exportInstitutionSettings',
    ],

    App\Policies\Institution\DepartmentMetaDataPolicy::class => [
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

    App\Policies\Dashboards\DashboardPolicy::class => [
        'viewDashboard',
    ],

    App\Policies\Students\PortalPolicy::class => [
        'viewStudentDashboard',
        'manageStudentPersonalDetails',
        'manageStudentProgramDetails',
        'manageStudentSponsors',
        'manageStudentContacts',
        'manageStudentFinancialRecords',
        'manageStudentAcademicRecords',
    ],

    App\Policies\Students\StudentMetaDataPolicy::class => [
        'manageStudentMetadata',
    ],

];

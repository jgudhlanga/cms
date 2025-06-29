<?php

namespace App\Providers;

use App\Policies\Dashboards\DashboardPolicy;
use App\Policies\Institution\DepartmentMetaDataPolicy;
use App\Policies\Settings\InstitutionSetupPolicy;
use App\Policies\Settings\SettingPolicy;
use App\Policies\Students\PortalPolicy;
use App\Policies\Students\StudentMetaDataPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();
        $this->registerSettingsPolicies();
        $this->registerInstitutionSettingsPolicies();
        $this->registerDepartmentMetaDataPolicies();
        $this->registerDashboardPolicies();
        $this->registerPortalPolicies();
        $this->registerStudentMetadataPolicies();

        Event::listen(Login::class, function ($event) {
            $user = $event->user;
            $user->update([
                'last_login_at' => now(),
                'login_count' => ($user->login_count ?? 0) + 1,
            ]);
        });
    }

    private function registerSettingsPolicies(): void
    {
        Gate::define('viewSettings', [SettingPolicy::class, 'viewSettings']);
        Gate::define('createSettings', [SettingPolicy::class, 'createSettings']);
        Gate::define('updateSettings', [SettingPolicy::class, 'updateSettings']);
        Gate::define('deleteSettings', [SettingPolicy::class, 'deleteSettings']);
        Gate::define('restoreSettings', [SettingPolicy::class, 'restoreSettings']);
        Gate::define('forceDeleteSettings', [SettingPolicy::class, 'forceDeleteSettings']);
        Gate::define('importSettings', [SettingPolicy::class, 'importSettings']);
        Gate::define('exportSettings', [SettingPolicy::class, 'exportSettings']);
    }

    private function registerInstitutionSettingsPolicies(): void
    {
        Gate::define('viewInstitutionSettings', [InstitutionSetupPolicy::class, 'viewInstitutionSettings']);
        Gate::define('createInstitutionSettings', [InstitutionSetupPolicy::class, 'createInstitutionSettings']);
        Gate::define('updateInstitutionSettings', [InstitutionSetupPolicy::class, 'updateInstitutionSettings']);
        Gate::define('deleteInstitutionSettings', [InstitutionSetupPolicy::class, 'deleteInstitutionSettings']);
        Gate::define('restoreInstitutionSettings', [InstitutionSetupPolicy::class, 'restoreInstitutionSettings']);
        Gate::define('forceDeleteInstitutionSettings', [InstitutionSetupPolicy::class, 'forceDeleteInstitutionSettings']);
        Gate::define('importInstitutionSettings', [InstitutionSetupPolicy::class, 'importInstitutionSettings']);
        Gate::define('exportInstitutionSettings', [InstitutionSetupPolicy::class, 'exportInstitutionSettings']);
    }

    private function registerDepartmentMetaDataPolicies(): void
    {
        Gate::define('viewAnyDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'viewAnyDepartmentMetaData']);
        Gate::define('viewDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'viewDepartmentMetaData']);
        Gate::define('createDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'createDepartmentMetaData']);
        Gate::define('updateDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'updateDepartmentMetaData']);
        Gate::define('deleteIDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'deleteDepartmentMetaData']);
        Gate::define('restoreDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'restoreDepartmentMetaData']);
        Gate::define('forceDeleteDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'forceDeleteDepartmentMetaData']);
        Gate::define('importDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'importDepartmentMetaData']);
        Gate::define('exportDepartmentMetaData', [DepartmentMetaDataPolicy::class, 'exportDepartmentMetaData']);
    }

    private function registerDashboardPolicies(): void
    {
        Gate::define('viewDashboard', [DashboardPolicy::class, 'viewDashboard']);
    }

    private function registerPortalPolicies(): void
    {
        Gate::define('viewStudentDashboard', [PortalPolicy::class, 'viewStudentDashboard']);
        Gate::define('manageStudentPersonalDetails', [PortalPolicy::class, 'manageStudentPersonalDetails']);
        Gate::define('manageStudentProgramDetails', [PortalPolicy::class, 'manageStudentProgramDetails']);
        Gate::define('manageStudentSponsors', [PortalPolicy::class, 'manageStudentSponsors']);
        Gate::define('manageStudentContacts', [PortalPolicy::class, 'manageStudentContacts']);
        Gate::define('manageStudentFinancialRecords', [PortalPolicy::class, 'manageStudentFinancialRecords']);
        Gate::define('manageStudentAcademicRecords', [PortalPolicy::class, 'manageStudentAcademicRecords']);
    }

    private function registerStudentMetadataPolicies(): void
    {
        Gate::define('manageStudentMetadata', [StudentMetaDataPolicy::class, 'manageStudentMetadata']);
    }
}

<?php

namespace App\Providers;

use App\Policies\Settings\InstitutionSetupPolicy;
use App\Policies\Settings\SettingPolicy;
use Illuminate\Http\Resources\Json\JsonResource;
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
}

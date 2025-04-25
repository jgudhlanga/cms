<?php

namespace App\Providers;

use App\Policies\Settings\SettingPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Resources\Json\JsonResource;

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
        $this->registerGeneralPolicies();
    }

    private function registerGeneralPolicies(): void
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
}

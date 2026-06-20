<?php

namespace App\Services\Acl;

use App\Models\Acl\Module;
use Illuminate\Support\Facades\Cache;

class AclModuleStateService
{
    private const string CACHE_KEY = 'acl_module_state';

    private const int CACHE_TTL_SECONDS = 60;

    /**
     * @return array<string, array{enabled: bool, settings: array<string, mixed>}>
     */
    public function all(): array
    {
        /** @var array<string, array{enabled: bool, settings: array<string, mixed>}> $state */
        $state = Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL_SECONDS,
            function (): array {
                return Module::query()
                    ->get(['slug', 'status', 'settings'])
                    ->mapWithKeys(fn (Module $module) => [
                        $module->slug => [
                            'enabled' => (bool) $module->status,
                            'settings' => is_array($module->settings) ? $module->settings : [],
                        ],
                    ])
                    ->all();
            },
        );

        return $state;
    }

    public function isEnabled(string $slug): bool
    {
        $state = $this->all()[$slug] ?? null;

        if ($state === null) {
            return true;
        }

        return (bool) ($state['enabled'] ?? true);
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsFor(string $slug): array
    {
        return $this->all()[$slug]['settings'] ?? [];
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('dashboard_module_state');
    }
}

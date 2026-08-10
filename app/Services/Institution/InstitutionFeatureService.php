<?php

declare(strict_types=1);

namespace App\Services\Institution;

use App\Models\Institution\InstitutionFeature;
use Illuminate\Support\Facades\DB;

class InstitutionFeatureService
{
    /**
     * @return array<string, bool>
     */
    public function defaults(): array
    {
        return InstitutionFeature::DEFAULT_FEATURES;
    }

    public function forTenant(int $tenantId): InstitutionFeature
    {
        $feature = InstitutionFeature::query()
            ->withoutGlobalScopes()
            ->firstOrCreate(
                ['tenant_id' => $tenantId],
                ['features' => $this->defaults()]
            );

        $merged = array_merge($this->defaults(), $feature->features ?? []);

        if ($merged !== ($feature->features ?? [])) {
            $feature->features = $merged;
            $feature->save();
        }

        return $feature;
    }

    public function allowsOnlineClearance(int $tenantId): bool
    {
        return $this->forTenant($tenantId)->allowsOnlineClearance();
    }

    /**
     * @param  array<string, bool>  $features
     */
    public function update(int $tenantId, array $features): InstitutionFeature
    {
        return DB::transaction(function () use ($tenantId, $features): InstitutionFeature {
            $record = $this->forTenant($tenantId);
            $merged = array_merge($this->defaults(), $record->features ?? []);

            foreach ($features as $key => $value) {
                if (! array_key_exists($key, $this->defaults())) {
                    continue;
                }

                $merged[$key] = (bool) $value;
            }

            $record->features = $merged;
            $record->save();

            return $record->fresh();
        });
    }

    public function setAllowOnlineClearance(int $tenantId, bool $enabled): InstitutionFeature
    {
        return $this->update($tenantId, [
            InstitutionFeature::ALLOW_ONLINE_CLEARANCE => $enabled,
        ]);
    }
}

<?php

namespace Database\Seeders\HMS;

use App\Enums\Shared\TenantEnum;
use App\Models\HMS\HostelAmenity;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HostelAmenitySeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const array DEFAULT_AMENITIES = [
        'Chair',
        'Bed',
        'Curtain',
        'BIC',
        'Reading Lamp',
        'Power outlet',
    ];

    public function run(): void
    {
        $tenantId = TenantEnum::HARARE_POLY->id();

        foreach (self::DEFAULT_AMENITIES as $name) {
            HostelAmenity::query()->firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'slug' => Str::slug($name),
                ],
                [
                    'name' => $name,
                ],
            );
        }
    }
}

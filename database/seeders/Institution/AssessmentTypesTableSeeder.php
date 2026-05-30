<?php

namespace Database\Seeders\Institution;

use App\Enums\Shared\TenantEnum;
use App\Models\Institution\AssessmentType;
use App\Models\Institution\ModeOfStudy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssessmentTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'name' => 'Innovation Based',
                'modes_of_study' => ['Full Time', 'Part time', 'Block Release'],
                'weight_percent' => 20,
            ],
            [
                'name' => 'Research Based',
                'modes_of_study' => ['Full Time', 'Part time', 'Block Release'],
                'weight_percent' => 20,
            ],
            [
                'name' => 'Test',
                'modes_of_study' => ['Full Time', 'Part time', 'Block Release'],
                'weight_percent' => 20,
            ],
            [
                'name' => 'First Visit',
                'modes_of_study' => ['Ojet'],
            ],
            [
                'name' => 'Second Visit',
                'modes_of_study' => ['Ojet'],
            ],
            [
                'name' => 'Log Book',
                'modes_of_study' => ['Ojet'],
            ],
        ];

        $modeLookup = ModeOfStudy::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (ModeOfStudy $modeOfStudy) => [Str::lower($modeOfStudy->name) => $modeOfStudy->id]);

        foreach ($data as $row) {
            $modeIds = collect($row['modes_of_study'])
                ->map(fn (string $modeName) => $modeLookup->get(Str::lower($modeName)))
                ->filter()
                ->values()
                ->all();

            AssessmentType::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'tenant_id' => TenantEnum::HARARE_POLY->id(),
                    'modes_of_study' => $modeIds,
                    'description' => null,
                    'weight_percent' => $row['weight_percent'] ?? null,
                ],
            );
        }
    }
}

<?php

namespace Database\Seeders\Institution;

use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Shared\TenantEnum;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\FeeType;
use App\Models\Tenants\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FeeStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tuitionFeeType = FeeType::where('slug', Str::slug(FeeTypeEnum::TUITION_FEE->name()))->first();
        $registrationFeeType = FeeType::where('slug', Str::slug(FeeTypeEnum::REGISTRATION_FEE->name()))->first();
        $tenant = Tenant::where('name', TenantEnum::HARARE_POLY->value)->first();
        $levels = [
            LevelEnum::ABMA_LEVEL_3->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 600],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 600],
            ],
            LevelEnum::ABMA_LEVEL_4->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 650],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 650],
            ],
            LevelEnum::ABMA_LEVEL_5->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 700],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 700],
            ],
            LevelEnum::ABMA_LEVEL_6->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 720],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 720],
            ],
            LevelEnum::NC->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 305],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 305],
                ['mode' => ModeOfStudyEnum::OJET->value, 'amount' => 225],
                ['mode' => ModeOfStudyEnum::BLOCK_RELEASE->value, 'amount' => 375],
            ],
            LevelEnum::ND->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 355],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 355],
                ['mode' => ModeOfStudyEnum::OJET->value, 'amount' => 225],
                ['mode' => ModeOfStudyEnum::BLOCK_RELEASE->value, 'amount' => 375],
            ],
            LevelEnum::HND->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 405],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 405],
                ['mode' => ModeOfStudyEnum::OJET->value, 'amount' => 285],
                ['mode' => ModeOfStudyEnum::BLOCK_RELEASE->value, 'amount' => 375],
            ],
            LevelEnum::BTECH->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 620],
                ['mode' => ModeOfStudyEnum::BLOCK_RELEASE->value, 'amount' => 620],
            ],
            LevelEnum::SDP->value => [
                ['mode' => ModeOfStudyEnum::FULL_TIME->value, 'amount' => 375],
                ['mode' => ModeOfStudyEnum::PART_TIME->value, 'amount' => 375],
            ],
        ];

        foreach($levels as $level => $arrData) {
            // get the level
           $levelModel = Level::where('name', $level)->first();
            foreach($arrData as $arr) {
                // get model
                $mode = ModeOfStudy::where('name', $arr['mode'])->first();
                FeeStructure::create([
                    'tenant_id' => $tenant->id ?? null,
                    'fee_type_id' => $tuitionFeeType->id ?? null,
                    'level_id' => $levelModel?->id ?? null,
                    'mode_of_study_id' => $mode?->id ??  null,
                    'local_fca_amount' => $arr['amount'],
                ]);
            }
        }
        // registration fee - one structure
        FeeStructure::create([
            'tenant_id' => $tenant->id ?? null,
            'fee_type_id' => $registrationFeeType->id ?? null,
            'level_id' => null,
            'mode_of_study_id' => null,
            'local_fca_amount' => 20,
        ]);
    }
}

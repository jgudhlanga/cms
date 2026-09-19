<?php

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use Illuminate\Support\Str;

if (! function_exists('seedOfferLetterDocumentPrerequisites')) {
    function seedOfferLetterDocumentPrerequisites(StudentApplication $studentApplication): void
    {
        FeeType::query()->firstOrCreate(
            ['name' => FeeTypeEnum::TUITION_FEE->name()],
            ['description' => FeeTypeEnum::TUITION_FEE->description()],
        );

        createOfferLetterTemplate($studentApplication, [
            'name' => 'Standard Offer Letter',
            'body' => '<p>Congratulations {studentName}</p>',
        ]);
    }
}

if (! function_exists('createOfferLetterTemplate')) {
    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<int>  $departmentIds
     * @param  list<int>  $levelIds
     */
    function createOfferLetterTemplate(
        StudentApplication $application,
        array $overrides = [],
        array $departmentIds = [],
        array $levelIds = [],
    ): OfferLetterTemplate {
        $name = $overrides['name'] ?? ('Offer '.Str::upper(Str::random(6)));
        unset($overrides['name']);

        $template = OfferLetterTemplate::query()->firstOrCreate(
            [
                'tenant_id' => $application->tenant_id,
                'intake_period_id' => $application->intake_period_id,
                'name' => $name,
            ],
            array_merge([
                'body' => '<p>{studentName} {tuition}</p>',
                'header_line_1' => 'Republic of Zimbabwe',
                'header_line_2' => 'Harare Polytechnic',
            ], $overrides),
        );

        if ($overrides !== []) {
            $template->update($overrides);
        }

        if ($departmentIds !== []) {
            $template->institutionDepartments()->sync($departmentIds);
        }

        if ($levelIds !== []) {
            $template->levels()->sync($levelIds);
        }

        return $template->fresh(['institutionDepartments', 'levels']) ?? $template;
    }
}

if (! function_exists('makeIntakeLatest')) {
    function makeIntakeLatest(IntakePeriod $intakePeriod): void
    {
        IntakePeriod::query()
            ->whereKeyNot($intakePeriod->id)
            ->update([
                'end_date' => now()->subYears(2)->toDateString(),
            ]);

        $intakePeriod->update([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
        ]);
    }
}

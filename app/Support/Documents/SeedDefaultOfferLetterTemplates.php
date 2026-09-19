<?php

declare(strict_types=1);

namespace App\Support\Documents;

use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Models\Institution\Department;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\OfferLetterTemplate;

final class SeedDefaultOfferLetterTemplates
{
    public const string HEXCO_GENERIC = 'HEXCO – Generic';

    public const string ENGINEERING_HEXCO = 'Engineering – HEXCO';

    public const string ABMA_USD = 'ABMA – USD Only';

    public const string BLOCK_RELEASE_USD = 'Block Release – USD Only';

    public const string SDP_GENERIC = 'SDP – Generic';

    public const string SDP_MECHANICAL = 'SDP – Mechanical';

    public const string SDP_MECHANICAL_OJET = 'SDP – Mechanical OJET';

    public const string MECHANICAL_SDP_TUITION = '375.00';

    public const string MECHANICAL_SDP_OJET_TUITION = '237.00';

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return [
            self::HEXCO_GENERIC,
            self::ENGINEERING_HEXCO,
            self::ABMA_USD,
            self::BLOCK_RELEASE_USD,
            self::SDP_GENERIC,
            self::SDP_MECHANICAL,
            self::SDP_MECHANICAL_OJET,
        ];
    }

    /**
     * @param  array{
     *     header_line_1?: string|null,
     *     header_line_2?: string|null,
     *     header_address_line_1?: string|null,
     *     header_address_line_2?: string|null,
     *     header_telephone?: string|null,
     *     header_email?: string|null,
     *     header_website?: string|null,
     *     generic_body?: string|null,
     *     usd_body?: string|null,
     *     sdp_body?: string|null
     * }  $source
     */
    public function execute(IntakePeriod $intake, array $source = []): int
    {
        $created = 0;
        $letterhead = $this->letterhead($source);

        $created += $this->createNamed($intake, self::HEXCO_GENERIC, [
            ...$letterhead,
            'helper_description' => __('trans.offer_letter_template_help_hexco_generic'),
            'body' => $source['generic_body'] ?? $this->defaultBody(),
        ], [], []);

        $engineeringIds = $this->engineeringDepartmentIds((int) $intake->tenant_id);
        $hexcoLevelIds = $this->levelIds([
            LevelEnum::NC->name(),
            LevelEnum::ND->name(),
            LevelEnum::HND->name(),
            LevelEnum::BTECH->name(),
        ]);
        if ($engineeringIds !== []) {
            $created += $this->createNamed($intake, self::ENGINEERING_HEXCO, [
                ...$letterhead,
                'helper_description' => __('trans.offer_letter_template_help_engineering_hexco'),
                'body' => $source['generic_body'] ?? $this->defaultBody(),
            ], $engineeringIds, $hexcoLevelIds);
        }

        $abmaLevelIds = $this->levelIds([
            LevelEnum::ABMA_LEVEL_3->name(),
            LevelEnum::ABMA_LEVEL_4->name(),
            LevelEnum::ABMA_LEVEL_5->name(),
            LevelEnum::ABMA_LEVEL_6->name(),
        ]);
        if ($abmaLevelIds !== []) {
            $created += $this->createNamed($intake, self::ABMA_USD, [
                ...$letterhead,
                'helper_description' => __('trans.offer_letter_template_help_abma'),
                'body' => $source['usd_body'] ?? $this->defaultBody(),
            ], [], $abmaLevelIds);
        }

        $blockReleaseId = $this->modeId(ModeOfStudyEnum::BLOCK_RELEASE->label());
        if ($blockReleaseId !== null) {
            $created += $this->createNamed($intake, self::BLOCK_RELEASE_USD, [
                ...$letterhead,
                'mode_of_study_id' => $blockReleaseId,
                'helper_description' => __('trans.offer_letter_template_help_block_release'),
                'body' => $source['usd_body'] ?? $this->defaultBody(),
            ], [], []);
        }

        $sdpLevelIds = $this->levelIds([LevelEnum::SDP->name()]);
        if ($sdpLevelIds !== []) {
            $created += $this->createNamed($intake, self::SDP_GENERIC, [
                ...$letterhead,
                'helper_description' => __('trans.offer_letter_template_help_sdp_generic'),
                'body' => $source['sdp_body'] ?? $this->defaultBody(),
            ], [], $sdpLevelIds);

            $mechanicalIds = $this->departmentIdsFor(
                (int) $intake->tenant_id,
                DepartmentEnum::MECHANICAL_AND_PRODUCTION_ENGINEERING->label(),
            );
            if ($mechanicalIds !== []) {
                $created += $this->createNamed($intake, self::SDP_MECHANICAL, [
                    ...$letterhead,
                    'tuition_override' => self::MECHANICAL_SDP_TUITION,
                    'helper_description' => __('trans.offer_letter_template_help_sdp_mechanical'),
                    'body' => $source['sdp_body'] ?? $this->defaultBody(),
                ], $mechanicalIds, $sdpLevelIds);

                $ojetId = $this->modeId(ModeOfStudyEnum::OJET->label());
                if ($ojetId !== null) {
                    $created += $this->createNamed($intake, self::SDP_MECHANICAL_OJET, [
                        ...$letterhead,
                        'mode_of_study_id' => $ojetId,
                        'tuition_override' => self::MECHANICAL_SDP_OJET_TUITION,
                        'helper_description' => __('trans.offer_letter_template_help_sdp_mechanical_ojet'),
                        'body' => $source['sdp_body'] ?? $this->defaultBody(),
                    ], $mechanicalIds, $sdpLevelIds);
                }
            }
        }

        return $created;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<int>  $departmentIds
     * @param  list<int>  $levelIds
     */
    private function createNamed(IntakePeriod $intake, string $name, array $attributes, array $departmentIds, array $levelIds): int
    {
        $exists = OfferLetterTemplate::withTrashed()
            ->where('tenant_id', $intake->tenant_id)
            ->where('intake_period_id', $intake->id)
            ->where('name', $name)
            ->exists();

        if ($exists) {
            return 0;
        }

        $template = OfferLetterTemplate::query()->create([
            'tenant_id' => $intake->tenant_id,
            'intake_period_id' => $intake->id,
            'name' => $name,
            'helper_description' => $attributes['helper_description'] ?? null,
            'mode_of_study_id' => $attributes['mode_of_study_id'] ?? null,
            'course_id' => $attributes['course_id'] ?? null,
            'tuition_override' => $attributes['tuition_override'] ?? null,
            'header_line_1' => $attributes['header_line_1'] ?? null,
            'header_line_2' => $attributes['header_line_2'] ?? null,
            'header_address_line_1' => $attributes['header_address_line_1'] ?? null,
            'header_address_line_2' => $attributes['header_address_line_2'] ?? null,
            'header_telephone' => $attributes['header_telephone'] ?? null,
            'header_email' => $attributes['header_email'] ?? null,
            'header_website' => $attributes['header_website'] ?? null,
            'body' => $this->bodyWithoutLetterDate((string) ($attributes['body'] ?? $this->defaultBody())),
        ]);

        if ($departmentIds !== []) {
            $template->institutionDepartments()->sync($departmentIds);
        }

        if ($levelIds !== []) {
            $template->levels()->sync($levelIds);
        }

        return 1;
    }

    /**
     * @param  array<string, mixed>  $source
     * @return array<string, string|null>
     */
    private function letterhead(array $source): array
    {
        return [
            'header_line_1' => $source['header_line_1'] ?? 'MINISTRY OF HIGHER AND TERTIARY EDUCATION, INNOVATION, SCIENCE AND TECHNOLOGY DEVELOPMENT',
            'header_line_2' => $source['header_line_2'] ?? 'Harare Polytechnic',
            'header_address_line_1' => $source['header_address_line_1'] ?? 'P. O. Box CY 407, Causeway, Harare',
            'header_address_line_2' => $source['header_address_line_2'] ?? null,
            'header_telephone' => $source['header_telephone'] ?? '+263 8677 000 343',
            'header_email' => $source['header_email'] ?? 'hararepoly@hrepoly.ac.zw',
            'header_website' => $source['header_website'] ?? 'www.hrepoly.ac.zw',
        ];
    }

    private function defaultBody(): string
    {
        return '<p>Dear {studentName} ({studentIdNumber}),</p><p>We are pleased to offer you a place on {course} ({level}) in the Department of {department} for {intakePeriod}, {modeOfStudy}. Student number: {studentNumber}.</p><p>Tuition: {tuition}</p>';
    }

    private function bodyWithoutLetterDate(string $body): string
    {
        $withoutDate = preg_replace('/\{date\}/', '', $body) ?? $body;

        return str_replace('  ', ' ', $withoutDate);
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    private function levelIds(array $names): array
    {
        return Level::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function engineeringDepartmentIds(int $tenantId): array
    {
        $names = [
            DepartmentEnum::ELECTRICAL_ENGINEERING->label(),
            DepartmentEnum::MECHANICAL_AND_PRODUCTION_ENGINEERING->label(),
            DepartmentEnum::AUTOMOTIVE_ENGINEERING->label(),
        ];

        return InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->whereHas('department', fn ($query) => $query->whereIn('name', $names))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function departmentIdsFor(int $tenantId, string $departmentName): array
    {
        $departmentId = Department::query()->where('name', $departmentName)->value('id');
        if ($departmentId === null) {
            return [];
        }

        return InstitutionDepartment::query()
            ->where('tenant_id', $tenantId)
            ->where('department_id', $departmentId)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    private function modeId(string $name): ?int
    {
        $id = ModeOfStudy::query()->where('name', $name)->value('id');

        return $id !== null ? (int) $id : null;
    }
}

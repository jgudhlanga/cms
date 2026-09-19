<?php

declare(strict_types=1);

namespace App\Support\Documents;

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\DocumentTypeEnum;
use App\Models\Institution\DocumentTemplate;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Shared\DocumentType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class MigrateDocumentTemplatesToOfferLetterTemplates
{
    public function __construct(
        private readonly SeedDefaultOfferLetterTemplates $seedDefaults = new SeedDefaultOfferLetterTemplates,
        private readonly CopyOfferLetterTemplateLogos $copyLogos = new CopyOfferLetterTemplateLogos,
    ) {}

    public function execute(): void
    {
        if (! Schema::hasTable('document_templates') || ! Schema::hasColumn('document_templates', 'document_type_id')) {
            return;
        }

        $documentType = DocumentType::query()
            ->where('name', DocumentTypeEnum::OFFER_LETTER->name())
            ->first();

        if (! $documentType instanceof DocumentType) {
            return;
        }

        DB::transaction(function () use ($documentType): void {
            $templates = DocumentTemplate::withTrashed()
                ->where('document_type_id', $documentType->id)
                ->orderBy('id')
                ->get();

            $byTenant = $templates->groupBy(fn (DocumentTemplate $template): int => (int) $template->tenant_id);

            foreach ($byTenant as $tenantId => $tenantTemplates) {
                $this->migrateTenant((int) $tenantId, $tenantTemplates);
            }

            $templates->each(function (DocumentTemplate $template): void {
                $template->clearMediaCollection('logo-1');
                $template->clearMediaCollection('logo-2');
                $template->forceDelete();
            });
        });
    }

    /**
     * @param  Collection<int, DocumentTemplate>  $templates
     */
    private function migrateTenant(int $tenantId, Collection $templates): void
    {
        $intakes = IntakePeriod::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        if ($intakes->isEmpty()) {
            return;
        }

        $latestIntake = $intakes->first(
            fn (IntakePeriod $intake): bool => $intake->status === IntakePeriodStatusEnum::Open,
        ) ?? $intakes->first();
        if (! $latestIntake instanceof IntakePeriod) {
            return;
        }

        $intakeIds = $templates
            ->map(fn (DocumentTemplate $template): ?int => $this->filledId($template->intake_period_id))
            ->filter()
            ->unique()
            ->values();

        if ($intakeIds->isEmpty()) {
            $intakeIds = collect([$latestIntake->id]);
        }

        $seededIntakes = [];

        foreach ($intakeIds as $intakeId) {
            $intake = $intakes->firstWhere('id', $intakeId) ?? IntakePeriod::query()->find($intakeId);
            if (! $intake instanceof IntakePeriod) {
                continue;
            }

            $forIntake = $templates->filter(
                function (DocumentTemplate $template) use ($intakeId, $latestIntake): bool {
                    $assigned = $this->filledId($template->intake_period_id);

                    return $assigned === (int) $intakeId
                        || ($assigned === null && (int) $intakeId === (int) $latestIntake->id);
                },
            );

            $this->seedIntakeFromSources($intake, $forIntake);
            $seededIntakes[] = (int) $intake->id;
        }

        $letterheadSource = $templates->first();
        $bodies = [
            'header_line_1' => $letterheadSource?->header_line_1,
            'header_line_2' => $letterheadSource?->header_line_2,
            'header_address_line_1' => $letterheadSource?->header_address_line_1,
            'header_address_line_2' => $letterheadSource?->header_address_line_2,
            'header_telephone' => $letterheadSource?->header_telephone,
            'header_email' => $letterheadSource?->header_email,
            'header_website' => $letterheadSource?->header_website,
            'generic_body' => $this->firstMatching(
                $templates,
                fn (DocumentTemplate $template): bool => ! $this->isUsdOnlyName($template) && ! $this->isSdpName($template),
            )?->body,
            'usd_body' => $this->firstMatching(
                $templates,
                fn (DocumentTemplate $template): bool => $this->isUsdOnlyName($template),
            )?->body,
            'sdp_body' => $this->firstMatching(
                $templates,
                fn (DocumentTemplate $template): bool => $this->isSdpName($template),
            )?->body,
        ];

        foreach ($intakes as $intake) {
            if (in_array((int) $intake->id, $seededIntakes, true)) {
                continue;
            }

            $this->seedDefaults->execute($intake, $bodies);
        }
    }

    /**
     * @param  Collection<int, DocumentTemplate>  $templates
     */
    private function seedIntakeFromSources(IntakePeriod $intake, Collection $templates): void
    {
        $generic = $this->firstMatching(
            $templates,
            fn (DocumentTemplate $template): bool => ! $this->isUsdOnlyName($template) && ! $this->isSdpName($template),
        );
        $usdOnly = $this->firstMatching(
            $templates,
            fn (DocumentTemplate $template): bool => $this->isUsdOnlyName($template),
        );
        $sdp = $this->firstMatching(
            $templates,
            fn (DocumentTemplate $template): bool => $this->isSdpName($template) && ! $this->isUsdOnlyName($template),
        );

        $letterheadSource = $generic ?? $usdOnly ?? $sdp ?? $templates->first();
        $source = [
            'header_line_1' => $letterheadSource?->header_line_1,
            'header_line_2' => $letterheadSource?->header_line_2,
            'header_address_line_1' => $letterheadSource?->header_address_line_1,
            'header_address_line_2' => $letterheadSource?->header_address_line_2,
            'header_telephone' => $letterheadSource?->header_telephone,
            'header_email' => $letterheadSource?->header_email,
            'header_website' => $letterheadSource?->header_website,
            'generic_body' => $generic?->body,
            'usd_body' => $usdOnly?->body ?? $generic?->body,
            'sdp_body' => $sdp?->body ?? $generic?->body,
        ];

        $this->seedDefaults->execute($intake, $source);

        $created = OfferLetterTemplate::query()
            ->where('tenant_id', $intake->tenant_id)
            ->where('intake_period_id', $intake->id)
            ->get()
            ->keyBy('name');

        if ($generic instanceof DocumentTemplate && $created->has(SeedDefaultOfferLetterTemplates::HEXCO_GENERIC)) {
            $this->copyLogos->fromDocumentTemplate(
                $generic,
                $created->get(SeedDefaultOfferLetterTemplates::HEXCO_GENERIC),
            );
        }

        $usdLogoSource = $usdOnly ?? $generic;
        if ($usdLogoSource instanceof DocumentTemplate) {
            foreach ([
                SeedDefaultOfferLetterTemplates::ABMA_USD,
                SeedDefaultOfferLetterTemplates::BLOCK_RELEASE_USD,
            ] as $name) {
                if ($created->has($name)) {
                    $this->copyLogos->fromDocumentTemplate($usdLogoSource, $created->get($name));
                }
            }
        }

        $sdpLogoSource = $sdp ?? $generic;
        if ($sdpLogoSource instanceof DocumentTemplate) {
            foreach ([
                SeedDefaultOfferLetterTemplates::SDP_GENERIC,
                SeedDefaultOfferLetterTemplates::SDP_MECHANICAL,
                SeedDefaultOfferLetterTemplates::SDP_MECHANICAL_OJET,
            ] as $name) {
                if ($created->has($name)) {
                    $this->copyLogos->fromDocumentTemplate($sdpLogoSource, $created->get($name));
                }
            }
        }

        $this->copyRemaining($intake, $templates, $created);
    }

    /**
     * @param  Collection<int, DocumentTemplate>  $templates
     * @param  Collection<string, OfferLetterTemplate>  $created
     */
    private function copyRemaining(IntakePeriod $intake, Collection $templates, Collection $created): void
    {
        $known = SeedDefaultOfferLetterTemplates::names();

        foreach ($templates as $source) {
            if (in_array((string) $source->name, $known, true) || $this->isWorkingScenarioSource($source)) {
                continue;
            }

            if (preg_match('/^ABMA Level [3-6]/i', (string) $source->name) === 1) {
                continue;
            }

            $name = $this->uniqueName($intake, (string) $source->name);
            $copy = OfferLetterTemplate::query()->create([
                'tenant_id' => $intake->tenant_id,
                'intake_period_id' => $intake->id,
                'name' => $name,
                'helper_description' => __('trans.offer_letter_template_help_custom'),
                'mode_of_study_id' => $this->filledId($source->mode_of_study_id),
                'course_id' => $this->filledId($source->course_id),
                'tuition_override' => $source->tuition_override,
                'header_line_1' => $source->header_line_1,
                'header_line_2' => $source->header_line_2,
                'header_address_line_1' => $source->header_address_line_1,
                'header_address_line_2' => $source->header_address_line_2,
                'header_telephone' => $source->header_telephone,
                'header_email' => $source->header_email,
                'header_website' => $source->header_website,
                'body' => str_replace('{date}', '', (string) $source->body),
            ]);

            $departmentId = $this->filledId($source->institution_department_id);
            if ($departmentId !== null) {
                $copy->institutionDepartments()->sync([$departmentId]);
            }

            $levelId = $this->filledId($source->level_id);
            if ($levelId !== null) {
                $copy->levels()->sync([$levelId]);
            }

            $this->copyLogos->fromDocumentTemplate($source, $copy);
            $created->put($name, $copy);
        }
    }

    private function isWorkingScenarioSource(DocumentTemplate $template): bool
    {
        $name = strtolower((string) $template->name);

        return str_contains($name, 'usd only')
            || str_contains($name, 'sdp')
            || $name === 'february 2026'
            || str_contains($name, 'hexco');
    }

    /**
     * @param  Collection<int, DocumentTemplate>  $sources
     * @param  callable(DocumentTemplate): bool  $callback
     */
    private function firstMatching(Collection $sources, callable $callback): ?DocumentTemplate
    {
        $match = $sources->first($callback);

        return $match instanceof DocumentTemplate ? $match : null;
    }

    private function isUsdOnlyName(DocumentTemplate $template): bool
    {
        return str_contains(strtolower((string) $template->name), 'usd only');
    }

    private function isSdpName(DocumentTemplate $template): bool
    {
        return str_contains(strtolower((string) $template->name), 'sdp');
    }

    private function uniqueName(IntakePeriod $intake, string $name): string
    {
        $candidate = $name !== '' ? $name : 'Offer letter';
        $index = 2;
        while (
            OfferLetterTemplate::withTrashed()
                ->where('tenant_id', $intake->tenant_id)
                ->where('intake_period_id', $intake->id)
                ->where('name', $candidate)
                ->exists()
        ) {
            $candidate = $name.' ('.$index.')';
            $index++;
        }

        return $candidate;
    }

    private function filledId(mixed $id): ?int
    {
        if ($id === null || (int) $id < 1) {
            return null;
        }

        return (int) $id;
    }
}

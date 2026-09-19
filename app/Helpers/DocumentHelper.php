<?php

namespace App\Helpers;

use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Students\StudentApplication;
use App\Services\Documents\OfferLetterAssembler;
use Illuminate\Database\Eloquent\Builder;

class DocumentHelper
{
    public static function assembleOfferLetter(
        StudentApplication $studentApplication,
        bool $requireVerifiedClassList = true,
    ): array {
        return app(OfferLetterAssembler::class)
            ->assemble($studentApplication, $requireVerifiedClassList)
            ->toLegacyList();
    }

    public static function resolvePdfHeaderTemplate(?int $tenantId = null): OfferLetterTemplate
    {
        $latestIntakeId = IntakePeriod::query()
            ->when($tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->value('id');

        if ($latestIntakeId !== null) {
            $wildcard = self::wildcardHeaderQuery($tenantId)
                ->where('intake_period_id', $latestIntakeId)
                ->first();
            if ($wildcard instanceof OfferLetterTemplate) {
                return $wildcard;
            }

            $anyOnLatest = self::anyHeaderQuery($tenantId)
                ->where('intake_period_id', $latestIntakeId)
                ->first();
            if ($anyOnLatest instanceof OfferLetterTemplate) {
                return $anyOnLatest;
            }
        }

        $wildcard = self::wildcardHeaderQuery($tenantId)->first();
        if ($wildcard instanceof OfferLetterTemplate) {
            return $wildcard;
        }

        $any = self::anyHeaderQuery($tenantId)->first();
        if ($any instanceof OfferLetterTemplate) {
            return $any;
        }

        return new OfferLetterTemplate([
            'header_line_1' => 'Republic of Zimbabwe',
            'header_line_2' => 'Harare Polytechnic',
            'header_address_line_1' => 'Harare',
            'header_address_line_2' => '',
            'header_telephone' => '',
            'header_email' => '',
            'header_website' => '',
        ]);
    }

    private static function wildcardHeaderQuery(?int $tenantId): Builder
    {
        return self::anyHeaderQuery($tenantId)
            ->whereDoesntHave('institutionDepartments')
            ->whereDoesntHave('levels')
            ->where(function (Builder $scopeQuery): void {
                $scopeQuery
                    ->whereNull('course_id')
                    ->orWhere('course_id', 0);
            })
            ->where(function (Builder $scopeQuery): void {
                $scopeQuery
                    ->whereNull('mode_of_study_id')
                    ->orWhere('mode_of_study_id', 0);
            });
    }

    private static function anyHeaderQuery(?int $tenantId): Builder
    {
        return OfferLetterTemplate::query()
            ->whereNotNull('header_line_1')
            ->when($tenantId !== null, fn (Builder $query) => $query->where('tenant_id', $tenantId))
            ->orderByDesc('id');
    }
}

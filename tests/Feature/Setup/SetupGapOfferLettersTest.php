<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Setup\SetupGapCheckEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Setup\SetupGap;
use App\Models\Tenants\Tenant;
use Illuminate\Support\Str;

function offerLetterGap(?int $intakePeriodId = null): ?SetupGap
{
    $gaps = SetupGap::query()
        ->where('check_key', SetupGapCheckEnum::INTAKE_PERIOD_MISSING_OFFER_LETTERS->value)
        ->get();

    if ($intakePeriodId === null) {
        return $gaps->first();
    }

    return $gaps->first(
        fn (SetupGap $gap): bool => (int) ($gap->meta['intakePeriodId'] ?? 0) === $intakePeriodId,
    );
}

it('raises a gap for an open intake with no offer letters', function (): void {
    $intake = IntakePeriod::query()->create([
        'tenant_id' => Tenant::query()->value('id'),
        'name' => 'Open without letters '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    scanSetupGaps();

    $gap = offerLetterGap((int) $intake->id);

    expect($gap)->not->toBeNull()
        ->and($gap->resolved_at)->toBeNull()
        ->and($gap->severity->value)->toBe('warning')
        ->and($gap->url)->toBe(route('intake-periods.offer-letter-templates.index', $intake));
});

it('does not raise a gap for a suspended regular intake', function (): void {
    $intake = IntakePeriod::query()->create([
        'tenant_id' => Tenant::query()->value('id'),
        'name' => 'Suspended regular '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'is_continuous' => false,
        'status' => IntakePeriodStatusEnum::Suspended,
    ]);

    scanSetupGaps();

    expect(offerLetterGap((int) $intake->id))->toBeNull();
});

it('raises a gap for a suspended continuous intake with no offer letters', function (): void {
    $intake = IntakePeriod::query()->create([
        'tenant_id' => Tenant::query()->value('id'),
        'name' => 'Suspended continuous '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'is_continuous' => true,
        'status' => IntakePeriodStatusEnum::Suspended,
    ]);

    scanSetupGaps();

    expect(offerLetterGap((int) $intake->id))->not->toBeNull();
});

it('closes the gap once offer letters are added', function (): void {
    $intake = IntakePeriod::query()->create([
        'tenant_id' => Tenant::query()->value('id'),
        'name' => 'Gap close '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    scanSetupGaps();
    expect(offerLetterGap((int) $intake->id)->resolved_at)->toBeNull();

    OfferLetterTemplate::query()->create([
        'tenant_id' => $intake->tenant_id,
        'intake_period_id' => $intake->id,
        'name' => 'HEXCO Generic',
        'header_line_1' => 'Republic of Zimbabwe',
        'header_line_2' => 'Harare Polytechnic',
        'body' => '<p>Hi</p>',
    ]);

    scanSetupGaps();

    expect(offerLetterGap((int) $intake->id)->resolved_at)->not->toBeNull();
});

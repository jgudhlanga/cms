<?php

use App\Actions\Institution\CopyOfferLetterTemplatesToIntakeAction;
use App\DTO\Institution\IntakePeriodDto;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Rbac\Permission;
use App\Models\Tenants\Tenant;
use App\Models\Users\User;
use App\Repositories\Institution\interface\IIntakePeriodRepository;
use App\Support\Documents\SeedDefaultOfferLetterTemplates;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function (): void {
    Queue::fake();
});

function makeIntakeWithTemplates(int $tenantId, string $name, string $startDate, array $templateNames): IntakePeriod
{
    $intake = IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => $name,
        'start_date' => $startDate,
        'end_date' => now()->addMonths(3)->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Closed,
    ]);

    foreach ($templateNames as $templateName) {
        $template = OfferLetterTemplate::query()->create([
            'tenant_id' => $tenantId,
            'intake_period_id' => $intake->id,
            'name' => $templateName,
            'body' => '<p>Copied {date} body</p>',
            'header_line_1' => 'Republic of Zimbabwe',
            'header_line_2' => 'Harare Polytechnic',
        ]);
        $template->institutionDepartments()->sync([]);
    }

    return $intake;
}

it('copies offer letter templates from the most recent intake that already has them', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    makeIntakeWithTemplates($tenantId, 'Older '.Str::random(4), now()->subMonths(8)->toDateString(), ['Older letter']);
    $source = makeIntakeWithTemplates($tenantId, 'Newer '.Str::random(4), now()->subMonths(2)->toDateString(), ['Newer letter']);

    $target = IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Target '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    $created = app(CopyOfferLetterTemplatesToIntakeAction::class)->execute($target);

    $names = OfferLetterTemplate::query()
        ->where('intake_period_id', $target->id)
        ->pluck('name')
        ->all();

    expect($created)->toBe(1)
        ->and($names)->toBe(['Newer letter'])
        ->and(OfferLetterTemplate::query()->where('intake_period_id', $target->id)->value('body'))
        ->toBe('<p>Copied body</p>');

    $sourceTemplate = OfferLetterTemplate::query()
        ->where('intake_period_id', $source->id)
        ->where('name', 'Newer letter')
        ->firstOrFail();
    expect($sourceTemplate->body)->toBe('<p>Copied {date} body</p>');
});

it('does not copy again when the target intake already has templates', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    makeIntakeWithTemplates($tenantId, 'Source '.Str::random(4), now()->subMonth()->toDateString(), ['Source letter']);

    $target = IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'Already seeded '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);
    OfferLetterTemplate::query()->create([
        'tenant_id' => $tenantId,
        'intake_period_id' => $target->id,
        'name' => 'Existing',
        'header_line_1' => 'Republic of Zimbabwe',
        'header_line_2' => 'Harare Polytechnic',
        'body' => '<p>Stay</p>',
    ]);

    expect(app(CopyOfferLetterTemplatesToIntakeAction::class)->execute($target))->toBe(0)
        ->and(OfferLetterTemplate::query()->where('intake_period_id', $target->id)->count())->toBe(1);
});

it('seeds default templates when no prior intake has offer letters', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    $target = IntakePeriod::query()->create([
        'tenant_id' => $tenantId,
        'name' => 'First '.Str::random(4),
        'start_date' => now()->toDateString(),
        'end_date' => now()->addMonths(4)->toDateString(),
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    $created = app(CopyOfferLetterTemplatesToIntakeAction::class)->execute($target);

    expect($created)->toBeGreaterThan(0)
        ->and(
            OfferLetterTemplate::query()
                ->where('intake_period_id', $target->id)
                ->where('name', SeedDefaultOfferLetterTemplates::HEXCO_GENERIC)
                ->exists(),
        )->toBeTrue();
});

it('copies templates when a new intake is created through the repository', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    makeIntakeWithTemplates($tenantId, 'Repo source '.Str::random(4), now()->subMonth()->toDateString(), ['Repo letter']);

    $user = User::factory()->create(['tenant_id' => $tenantId]);
    $this->actingAs($user);

    $created = app(IIntakePeriodRepository::class)->create(new IntakePeriodDto(
        name: 'Repo target '.Str::random(4),
        start_date: now()->toDateString(),
        end_date: now()->addMonths(4)->toDateString(),
        description: null,
        status: IntakePeriodStatusEnum::Closed->value,
    ));

    expect(
        OfferLetterTemplate::query()
            ->where('intake_period_id', $created->id)
            ->where('name', 'Repo letter')
            ->exists(),
    )->toBeTrue();
});

it('keeps templates when a soft-deleted intake is restored', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    $intake = makeIntakeWithTemplates($tenantId, 'Restore '.Str::random(4), now()->toDateString(), ['Keep me']);
    $templateId = OfferLetterTemplate::query()->where('intake_period_id', $intake->id)->value('id');

    $intake->delete();
    expect(OfferLetterTemplate::query()->whereKey($templateId)->exists())->toBeTrue();

    $intake->restore();
    expect(OfferLetterTemplate::query()->whereKey($templateId)->value('intake_period_id'))->toBe($intake->id);
});

it('cascade force-deletes offer letter templates with the intake', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    $intake = makeIntakeWithTemplates($tenantId, 'Force '.Str::random(4), now()->toDateString(), ['Gone']);
    $intakeId = $intake->id;

    $intake->forceDelete();

    expect(OfferLetterTemplate::withTrashed()->where('intake_period_id', $intakeId)->count())->toBe(0);
});

it('includes the offer letter count on the intake periods index', function (): void {
    $tenantId = (int) Tenant::query()->value('id');
    $intake = makeIntakeWithTemplates(
        $tenantId,
        'Counted '.Str::random(4),
        now()->toDateString(),
        ['One', 'Two'],
    );
    $intake->update([
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
        'end_date' => now()->addYear()->toDateString(),
    ]);

    $user = User::factory()->create(['tenant_id' => $tenantId]);
    Permission::findOrCreate('viewAny:intake-periods', 'web');
    $user->givePermissionTo('viewAny:intake-periods');

    $this->actingAs($user)
        ->get(route('intake-periods.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('institution/dropdowns/intakePeriods/Index')
            ->has('intakePeriods.data')
            ->where(
                'intakePeriods.data.0.attributes.offerLetterTemplateCount',
                2,
            ));
});

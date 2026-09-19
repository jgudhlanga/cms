<?php

use App\Actions\Documents\GenerateAndStoreOfferLetterAction;
use App\Enums\Institution\DepartmentEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\DocumentTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\DocumentHelper;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Mail\Enrolments\VerifiedStudentsOfferLetterMail;
use App\Models\Institution\Department;
use App\Models\Institution\DocumentTemplate;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\InstitutionDepartment;
use App\Models\Institution\Level;
use App\Models\Institution\ModeOfStudy;
use App\Models\Institution\OfferLetterTemplate;
use App\Models\Rbac\Permission;
use App\Models\Shared\DocumentType;
use App\Models\Shared\FeeType;
use App\Models\Users\User;
use App\Services\Documents\OfferLetterTemplateResolver;
use App\Services\Students\StudentOfferLetterService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function (): void {
    Queue::fake();
});

it('prefers a department-scoped template over a generic offer letter', function (): void {
    $application = createVerifiedStudentApplication('OFFER-SCOPE-'.strtoupper(str()->random(4)));
    createOfferLetterTemplate($application, ['name' => 'Generic Offer Letter', 'body' => '<p>Generic</p>']);

    $scoped = createOfferLetterTemplate(
        $application,
        ['name' => 'Department Offer Letter', 'body' => '<p>Department</p>'],
        [(int) $application->institution_department_id],
    );

    $resolved = app(OfferLetterTemplateResolver::class)->resolve($application->fresh());

    expect($resolved->is($scoped))->toBeTrue();
});

it('matches a multi-department engineering template and does not steal ABMA', function (): void {
    $application = createVerifiedStudentApplication('OFFER-ENG-'.strtoupper(str()->random(4)));
    $tenantId = (int) $application->tenant_id;

    $electrical = Department::factory()->create(['name' => DepartmentEnum::ELECTRICAL_ENGINEERING->label()]);
    $mechanical = Department::factory()->create(['name' => DepartmentEnum::MECHANICAL_AND_PRODUCTION_ENGINEERING->label()]);
    $automotive = Department::factory()->create(['name' => DepartmentEnum::AUTOMOTIVE_ENGINEERING->label()]);

    $application->institutionDepartment->update(['department_id' => $electrical->id]);

    $mechanicalDept = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $mechanical->id,
        'department_code' => 'me-'.Str::lower(Str::random(6)),
        'description' => 'Mechanical',
    ]);
    $automotiveDept = InstitutionDepartment::query()->create([
        'tenant_id' => $tenantId,
        'department_id' => $automotive->id,
        'department_code' => 'ae-'.Str::lower(Str::random(6)),
        'description' => 'Automotive',
    ]);

    $hexco = Level::factory()->create(['name' => LevelEnum::ND->name()]);
    $abma = Level::factory()->create(['name' => LevelEnum::ABMA_LEVEL_3->name()]);
    $application->departmentLevel->update(['level_id' => $hexco->id]);

    createOfferLetterTemplate($application, ['name' => 'HEXCO Generic', 'body' => '<p>Generic</p>']);
    $engineering = createOfferLetterTemplate(
        $application,
        ['name' => 'Engineering HEXCO', 'body' => '<p>Engineering</p>'],
        [
            (int) $application->institution_department_id,
            (int) $mechanicalDept->id,
            (int) $automotiveDept->id,
        ],
        [(int) $hexco->id],
    );
    $abmaTemplate = createOfferLetterTemplate(
        $application,
        ['name' => 'ABMA USD Only', 'body' => '<p>ABMA</p>'],
        [],
        [(int) $abma->id],
    );

    $resolvedHexco = app(OfferLetterTemplateResolver::class)->resolve($application->fresh());
    expect($resolvedHexco->is($engineering))->toBeTrue();

    $application->departmentLevel->update(['level_id' => $abma->id]);
    $resolvedAbma = app(OfferLetterTemplateResolver::class)->resolve($application->fresh());
    expect($resolvedAbma->is($abmaTemplate))->toBeTrue();
});

it('uses one ABMA template for levels 3 through 6', function (): void {
    $application = createVerifiedStudentApplication('OFFER-ABMA-'.strtoupper(str()->random(4)));
    $levels = collect([
        LevelEnum::ABMA_LEVEL_3->name(),
        LevelEnum::ABMA_LEVEL_4->name(),
        LevelEnum::ABMA_LEVEL_5->name(),
        LevelEnum::ABMA_LEVEL_6->name(),
    ])->map(fn (string $name): Level => Level::factory()->create(['name' => $name]));

    createOfferLetterTemplate($application, ['name' => 'Generic Offer Letter']);
    $abmaTemplate = createOfferLetterTemplate(
        $application,
        ['name' => 'ABMA Engineering Letter', 'body' => '<p>USD only wording {tuition}</p>'],
        [],
        $levels->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
    );

    foreach ($levels as $level) {
        $application->departmentLevel->update(['level_id' => $level->id]);
        $resolved = app(OfferLetterTemplateResolver::class)->resolve($application->fresh());
        expect($resolved->is($abmaTemplate))->toBeTrue();
    }
});

it('uses a tuition override on a scoped SDP mechanical ojet template', function (): void {
    $application = createVerifiedStudentApplication('OFFER-SDP-'.strtoupper(str()->random(4)));
    $sdp = Level::factory()->create(['name' => LevelEnum::SDP->name()]);
    $application->departmentLevel->update(['level_id' => $sdp->id]);

    $mechanical = Department::factory()->create([
        'name' => DepartmentEnum::MECHANICAL_AND_PRODUCTION_ENGINEERING->label(),
    ]);
    $application->institutionDepartment->update(['department_id' => $mechanical->id]);

    $ojet = ModeOfStudy::query()->firstOrCreate(['name' => ModeOfStudyEnum::OJET->label()]);
    $application->update(['mode_of_study_id' => $ojet->id]);

    createOfferLetterTemplate($application, [
        'name' => 'SDP Generic',
        'body' => '<p>{tuition}</p>',
    ], [], [(int) $sdp->id]);

    createOfferLetterTemplate($application, [
        'name' => 'SDP Mechanical OJET',
        'tuition_override' => '237.00',
        'mode_of_study_id' => $ojet->id,
        'body' => '<p>{tuition}</p>',
    ], [(int) $application->institution_department_id], [(int) $sdp->id]);

    [, , , , , , , , , $tuition] = DocumentHelper::assembleOfferLetter($application->fresh());

    expect($tuition)->toBe('237.00');
});

it('does not resolve a template from another intake', function (): void {
    $application = createVerifiedStudentApplication('OFFER-ISO-'.strtoupper(str()->random(4)));
    $other = createVerifiedStudentApplication('OFFER-ISO-B-'.strtoupper(str()->random(4)));
    createOfferLetterTemplate($other, ['name' => 'Other intake letter']);

    expect(fn () => app(OfferLetterTemplateResolver::class)->resolve($application->fresh()))
        ->toThrow(ModelNotFoundException::class);
});

it('stores the offer letter on first download and ignores later template and fee edits', function (): void {
    $application = createVerifiedStudentApplication('OFFER-LOCK-'.strtoupper(str()->random(4)));
    seedOfferLetterDocumentPrerequisites($application);

    $template = OfferLetterTemplate::query()
        ->where('tenant_id', $application->tenant_id)
        ->where('intake_period_id', $application->intake_period_id)
        ->where('name', 'Standard Offer Letter')
        ->firstOrFail();
    $template->update(['body' => '<p>Original {studentName}</p>']);

    $url = app(StudentOfferLetterService::class)->signedDownloadUrl($application->id);
    $first = $this->get($url);
    $first->assertSuccessful();
    expect($first->headers->get('content-type'))->toContain('application/pdf');

    $application->refresh();
    expect($application->offer_letter_id)->not->toBeNull();

    $media = $application->offerLetter;
    expect($media)->toBeInstanceOf(Media::class);
    $originalBytes = file_get_contents($media->getPath());
    expect($originalBytes)->not->toBeFalse()->and(strlen((string) $originalBytes))->toBeGreaterThan(100);

    $template->update(['body' => '<p>Changed after issue</p>']);

    $tuitionFeeType = FeeType::query()->firstOrCreate(
        ['name' => FeeTypeEnum::TUITION_FEE->name()],
        ['description' => FeeTypeEnum::TUITION_FEE->description()],
    );
    FeeStructure::query()->create([
        'tenant_id' => $application->tenant_id,
        'fee_type_id' => $tuitionFeeType->id,
        'level_id' => $application->departmentLevel->level->id,
        'mode_of_study_id' => $application->mode_of_study_id,
        'amount' => 999.99,
        'local_fca_amount' => 999.99,
    ]);

    $second = $this->get($url);
    $second->assertSuccessful();
    $secondBytes = file_get_contents($application->fresh()->offerLetter->getPath());

    expect($secondBytes)->toBe($originalBytes)
        ->and($secondBytes)->not->toContain('Changed after issue')
        ->and($application->fresh()->offer_letter_id)->toBe($media->id);
});

it('does not persist an offer letter when staff preview a template', function (): void {
    $application = createVerifiedStudentApplication('OFFER-PREV-'.strtoupper(str()->random(4)));
    $template = createOfferLetterTemplate($application, ['body' => '<p>Preview {studentName}</p>']);

    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);
    Permission::findOrCreate('viewAny:intake-periods', 'web');
    $user->givePermissionTo('viewAny:intake-periods');

    $this->actingAs($user)
        ->get(route('intake-periods.offer-letter-templates.preview', [
            'intake_period' => $application->intake_period_id,
            'offer_letter_template' => $template->id,
        ]))
        ->assertSuccessful();

    expect($application->fresh()->offer_letter_id)->toBeNull()
        ->and($application->fresh()->getFirstMedia('offer-letter'))->toBeNull();
});

it('does not send the offer letter email when pdf generation fails', function (): void {
    Mail::fake();
    $application = createVerifiedStudentApplication('OFFER-JOB-'.strtoupper(str()->random(4)));

    $job = new SendOfferLetterJob(
        (string) $application->student->user->full_name,
        (string) $application->student->user->email,
        (string) $application->id,
    );

    expect(fn () => $job->handle(app(GenerateAndStoreOfferLetterAction::class)))
        ->toThrow(ModelNotFoundException::class);

    Mail::assertNothingSent();
    expect($application->fresh()->offer_letter_id)->toBeNull();
});

it('stores the pdf before emailing the signed download link', function (): void {
    Mail::fake();
    $application = createVerifiedStudentApplication('OFFER-SEND-'.strtoupper(str()->random(4)));
    seedOfferLetterDocumentPrerequisites($application);

    $job = new SendOfferLetterJob(
        (string) $application->student->user->full_name,
        (string) $application->student->user->email,
        (string) $application->id,
    );
    $job->handle(app(GenerateAndStoreOfferLetterAction::class));

    Mail::assertSent(VerifiedStudentsOfferLetterMail::class);
    expect($application->fresh()->offer_letter_id)->not->toBeNull();
});

it('uses a wildcard offer letter for other pdf headers instead of a department-scoped template', function (): void {
    $application = createVerifiedStudentApplication('OFFER-HEAD-'.strtoupper(str()->random(4)));
    makeIntakeLatest($application->intakePeriod);

    createOfferLetterTemplate(
        $application,
        [
            'name' => 'Engineering Header',
            'header_line_1' => 'Engineering Division',
        ],
        [(int) $application->institution_department_id],
    );

    $wildcard = createOfferLetterTemplate($application, [
        'name' => 'Generic Header',
        'header_line_1' => 'Republic of Zimbabwe',
    ]);

    $resolved = DocumentHelper::resolvePdfHeaderTemplate($application->tenant_id);

    expect($resolved->is($wildcard))->toBeTrue()
        ->and($resolved->header_line_1)->toBe('Republic of Zimbabwe');
});

it('creates an offer letter template for an intake through http', function (): void {
    $application = createVerifiedStudentApplication('OFFER-HTTP-'.strtoupper(str()->random(4)));
    $user = User::factory()->create(['tenant_id' => $application->tenant_id]);
    Permission::findOrCreate('update:intake-periods', 'web');
    $user->givePermissionTo('update:intake-periods');

    $this->actingAs($user)
        ->post(route('intake-periods.offer-letter-templates.store', $application->intakePeriod), [
            'name' => 'Engineering HEXCO',
            'institution_department_ids' => [(int) $application->institution_department_id],
            'level_ids' => [(int) $application->departmentLevel->level_id],
            'body' => '<p>Grouped</p>',
            'helper_description' => 'Shared when fees match',
        ])
        ->assertRedirect(route('intake-periods.offer-letter-templates.index', $application->intakePeriod));

    $created = OfferLetterTemplate::query()
        ->where('intake_period_id', $application->intake_period_id)
        ->where('name', 'Engineering HEXCO')
        ->first();

    expect($created)->not->toBeNull()
        ->and($created->institutionDepartments->pluck('id')->all())->toBe([(int) $application->institution_department_id])
        ->and($created->levels->pluck('id')->all())->toBe([(int) $application->departmentLevel->level_id]);
});

it('rejects creating an offer letter as a document template', function (): void {
    $user = User::factory()->create();
    Permission::findOrCreate('create:document-templates', 'web');
    $user->givePermissionTo('create:document-templates');

    $documentType = DocumentType::query()->firstOrCreate(
        ['name' => DocumentTypeEnum::OFFER_LETTER->name()],
        ['description' => DocumentTypeEnum::OFFER_LETTER->description()],
    );

    $this->actingAs($user)
        ->from(route('document-templates.create'))
        ->post(route('document-templates.store'), [
            'name' => 'Should not exist',
            'document_type_id' => $documentType->id,
            'body' => '<p>Nope</p>',
        ])
        ->assertRedirect(route('document-templates.create'))
        ->assertSessionHasErrors('document_type_id');
});

it('has no leftover offer letter document templates after the cutover', function (): void {
    $documentType = DocumentType::query()->firstOrCreate(
        ['name' => DocumentTypeEnum::OFFER_LETTER->name()],
        ['description' => DocumentTypeEnum::OFFER_LETTER->description()],
    );

    expect(
        DocumentTemplate::withTrashed()
            ->where('document_type_id', $documentType->id)
            ->count(),
    )->toBe(0);
});

<?php

use App\Enums\Rbac\RoleEnum;
use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\DocumentTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\Rbac\Role;
use App\Models\Institution\DocumentTemplate;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\DocumentType;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;

function seedOfferLetterDocumentPrerequisites(StudentApplication $studentApplication): void
{
    FeeType::query()->firstOrCreate(
        ['name' => FeeTypeEnum::TUITION_FEE->name()],
        ['description' => FeeTypeEnum::TUITION_FEE->description()],
    );

    $documentType = DocumentType::query()->firstOrCreate(
        ['name' => DocumentTypeEnum::OFFER_LETTER->name()],
        ['description' => DocumentTypeEnum::OFFER_LETTER->description()],
    );

    DocumentTemplate::query()->firstOrCreate(
        [
            'tenant_id' => $studentApplication->tenant_id,
            'document_type_id' => $documentType->id,
            'name' => 'Standard Offer Letter',
        ],
        [
            'body' => '<p>Congratulations {{ $studentName }}</p>',
            'header_line_1' => 'Republic of Zimbabwe',
            'header_line_2' => 'Harare Polytechnic',
        ],
    );
}

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

it('allows offer letter download for applications in an active open intake', function (): void {
    $studentApplication = createVerifiedStudentApplication('OFFER-ACTIVE-'.strtoupper(str()->random(4)));

    $studentApplication->intakePeriod->update([
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $response = $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('allows offer letter download for applications on the latest closed intake', function (): void {
    $studentApplication = createVerifiedStudentApplication('OFFER-LATEST-CLOSED-'.strtoupper(str()->random(4)));

    makeIntakeLatest($studentApplication->intakePeriod);

    $studentApplication->intakePeriod->update([
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Closed,
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $response = $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('allows offer letter download for applications on the latest suspended intake', function (): void {
    $studentApplication = createVerifiedStudentApplication('OFFER-LATEST-SUSP-'.strtoupper(str()->random(4)));

    makeIntakeLatest($studentApplication->intakePeriod);

    $studentApplication->intakePeriod->update([
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Suspended,
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $response = $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('blocks offer letter download for applications in a past closed intake that is not latest', function (): void {
    $studentApplication = createVerifiedStudentApplication('OFFER-PAST-'.strtoupper(str()->random(4)));

    $studentApplication->intakePeriod->update([
        'is_active' => false,
        'status' => IntakePeriodStatusEnum::Closed,
        'start_date' => now()->subYears(3)->startOfMonth()->toDateString(),
        'end_date' => now()->subYears(3)->endOfMonth()->toDateString(),
    ]);

    IntakePeriod::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'name' => 'Newer Intake '.strtoupper(str()->random(4)),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]))->assertNotFound();
});

it('allows offer letter download for OJET applications on a past closed intake that is not latest', function (): void {
    $studentApplication = createVerifiedStudentApplication('OFFER-OJET-'.strtoupper(str()->random(4)));

    $ojetMode = ModeOfStudy::query()->firstOrCreate(['name' => ModeOfStudyEnum::OJET->value]);
    $studentApplication->update(['mode_of_study_id' => $ojetMode->id]);

    $studentApplication->intakePeriod->update([
        'is_active' => false,
        'status' => IntakePeriodStatusEnum::Closed,
        'start_date' => now()->subYears(3)->startOfMonth()->toDateString(),
        'end_date' => now()->subYears(3)->endOfMonth()->toDateString(),
    ]);

    IntakePeriod::query()->create([
        'tenant_id' => $studentApplication->tenant_id,
        'name' => 'Newer Intake '.strtoupper(str()->random(4)),
        'start_date' => now()->startOfMonth()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'calendar_year' => '2026/2027',
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $response = $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('allows offer letter download while impersonating a student', function (): void {
    Role::findOrCreate(RoleEnum::STUDENT->name(), 'web');

    $studentApplication = createVerifiedStudentApplication('OFFER-IMP-'.strtoupper(str()->random(4)));
    $studentUser = $studentApplication->student->user;
    $studentUser->assignRole(RoleEnum::STUDENT->name());

    $studentApplication->intakePeriod->update([
        'is_active' => true,
        'status' => IntakePeriodStatusEnum::Open,
        'end_date' => now()->addYear()->toDateString(),
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $impersonator = User::factory()->create();
    $impersonator->givePermissionTo('root:manage');

    $this->actingAs($impersonator)
        ->get(route('impersonate', ['id' => $studentUser->id]))
        ->assertRedirect();

    $response = $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

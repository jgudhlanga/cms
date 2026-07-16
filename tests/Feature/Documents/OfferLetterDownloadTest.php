<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use App\Enums\Shared\DocumentTypeEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\Institution\DocumentTemplate;
use App\Models\Shared\DocumentType;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;

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

it('blocks offer letter download for applications in a past closed intake', function (): void {
    $studentApplication = createVerifiedStudentApplication('OFFER-PAST-'.strtoupper(str()->random(4)));

    $studentApplication->intakePeriod->update([
        'is_active' => false,
        'status' => IntakePeriodStatusEnum::Closed,
    ]);

    seedOfferLetterDocumentPrerequisites($studentApplication);

    $this->get(route('documents.offer-letter', [
        'student_application' => $studentApplication->id,
    ]))->assertNotFound();
});

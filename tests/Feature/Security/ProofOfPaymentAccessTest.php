<?php

use App\Enums\Shared\FeeTypeEnum;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Support\Media\ProofOfPaymentMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
 * Proofs of payment hold bank and personal details. They are stored on a private disk and only served,
 * through documents.proof-of-payment, to the payer and to staff allowed to see the record.
 */

beforeEach(function () {
    Storage::fake('public');
    Storage::fake(ProofOfPaymentMedia::DISK);
});

/**
 * @return array{0: StudentApplication, 1: User}
 */
function proofOfPaymentApplication(): array
{
    $application = createVerifiedStudentApplication('POP-'.Str::upper(Str::random(5)));

    return [$application, $application->student->user];
}

test('proof of payment uploads are stored on the private disk', function () {
    [$application] = proofOfPaymentApplication();

    $media = $application->addMedia(UploadedFile::fake()->image('proof.jpg'))->toMediaCollection('application-fee');

    expect($media->disk)->toBe(ProofOfPaymentMedia::DISK);
    Storage::disk(ProofOfPaymentMedia::DISK)->assertExists($media->getPathRelativeToRoot());
    Storage::disk('public')->assertMissing($media->getPathRelativeToRoot());
});

test('an application proof opens for its student and permitted staff only', function () {
    [$application, $owner] = proofOfPaymentApplication();
    $media = $application->addMedia(UploadedFile::fake()->image('proof.jpg'))->toMediaCollection('tuition-fee');
    $url = route('documents.proof-of-payment', $media);

    $this->actingAs($owner)->get($url)->assertOk();

    $otherApplicant = User::factory()->create(['tenant_id' => $application->tenant_id]);
    $this->actingAs($otherApplicant)->getJson($url)->assertForbidden();

    $staff = User::factory()->create(['tenant_id' => $application->tenant_id]);
    $staff->givePermissionTo('view:student-applications');
    $this->actingAs($staff)->get($url)->assertOk();
});

test('guests are sent to the login page', function () {
    [$application] = proofOfPaymentApplication();
    $media = $application->addMedia(UploadedFile::fake()->image('proof.jpg'))->toMediaCollection('application-fee');

    $this->get(route('documents.proof-of-payment', $media))->assertRedirect(route('login'));
});

test('the route does not serve media that is not a proof of payment', function () {
    [$application, $owner] = proofOfPaymentApplication();
    $offerLetter = $application->addMedia(UploadedFile::fake()->create('offer.pdf', 10))->toMediaCollection('offer-letter');

    $this->actingAs($owner)
        ->getJson(route('documents.proof-of-payment', $offerLetter))
        ->assertNotFound();
});

test('a ledger proof opens for the paying user and finance staff only', function () {
    [$application, $owner] = proofOfPaymentApplication();
    $feeType = FeeType::query()->firstOrCreate(['slug' => FeeTypeEnum::APPLICATION_FEE->slug()], ['name' => 'Application fee']);

    $ledger = Ledger::query()->forceCreate([
        'tenant_id' => $application->tenant_id,
        'ledgerable_type' => $owner->getMorphClass(),
        'ledgerable_id' => $owner->id,
        'fee_type_id' => $feeType->id,
        'type' => 'invoice',
        'payment_status' => 'pending',
        'amount' => 20,
        'currency' => 'USD',
        'system_reference' => 'POP-'.Str::uuid(),
    ]);
    $media = $ledger->addMedia(UploadedFile::fake()->image('proof.jpg'))->toMediaCollection('receipts');
    $ledger->update(['proof_of_payment_id' => $media->id]);

    expect($media->disk)->toBe(ProofOfPaymentMedia::DISK)
        ->and($ledger->fresh()->proof_of_payment_url)->toBe(route('documents.proof-of-payment', ['media' => $media->id]));

    $this->actingAs($owner)->get($ledger->fresh()->proof_of_payment_url)->assertOk();

    $otherUser = User::factory()->create(['tenant_id' => $application->tenant_id]);
    $this->actingAs($otherUser)->getJson($ledger->fresh()->proof_of_payment_url)->assertForbidden();

    $financeStaff = User::factory()->create(['tenant_id' => $application->tenant_id]);
    $financeStaff->givePermissionTo('view:finances');
    $this->actingAs($financeStaff)->get($ledger->fresh()->proof_of_payment_url)->assertOk();
});

test('the move command secures earlier public uploads without changing media ids', function () {
    [$application] = proofOfPaymentApplication();
    $legacy = $application->addMedia(UploadedFile::fake()->image('legacy.jpg'))->toMediaCollection('application-fee', 'public');
    $path = $legacy->getPathRelativeToRoot();

    Storage::disk('public')->assertExists($path);

    $this->artisan('media:secure-proofs-of-payment', ['--dry-run' => true])->assertSuccessful();

    expect($legacy->fresh()->disk)->toBe('public');
    Storage::disk('public')->assertExists($path);

    $this->artisan('media:secure-proofs-of-payment')->assertSuccessful();

    $moved = $legacy->fresh();

    expect($moved->id)->toBe($legacy->id)
        ->and($moved->disk)->toBe(ProofOfPaymentMedia::DISK)
        ->and($moved->conversions_disk)->toBe(ProofOfPaymentMedia::DISK);
    Storage::disk(ProofOfPaymentMedia::DISK)->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

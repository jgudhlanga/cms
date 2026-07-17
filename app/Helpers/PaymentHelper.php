<?php

namespace App\Helpers;

use App\DTO\Integrations\CreateInvoiceDto;
use App\DTO\Integrations\CreateReceiptDto;
use App\DTO\Integrations\UpdateReceiptDto;
use App\Enums\Shared\FeeTypeEnum;
use App\Http\Resources\Institution\FeeStructureResource;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Students\ApplicationFeeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class PaymentHelper
{
    /* -----------------------------------------------------------------
     |  Fee & Ledger Retrieval
     | -----------------------------------------------------------------
     */

    public static function getFeeTypeBySlug(string $slug): ?FeeType
    {
        return FeeType::where('slug', $slug)->first();
    }

    public static function getFeeStructureResourceBySlug(string $slug): FeeStructureResource
    {
        $feeType = self::getFeeTypeBySlug($slug);
        $feeStructure = FeeStructure::where('fee_type_id', $feeType->id)->first();

        return FeeStructureResource::make($feeStructure);
    }

    public static function getLatestLedgerRecord(
        string $slug,
        ?string $recordType = null,
        ?User $user = null,
        ?IntakePeriod $intakePeriod = null
    ): ?Ledger {
        $feeType = self::getFeeTypeBySlug($slug);
        if (! $feeType) {
            return null;
        }

        $user = self::resolveUser($user);

        return self::getLatestLedgerRecordForLedgerable($user, $slug, $recordType, $intakePeriod);
    }

    public static function getLatestLedgerRecordForLedgerable(
        Model $ledgerable,
        string $slug,
        ?string $recordType = null,
        ?IntakePeriod $intakePeriod = null
    ): ?Ledger {
        $feeType = self::getFeeTypeBySlug($slug);
        if (! $feeType) {
            return null;
        }

        $intakePeriod = self::resolveIntakePeriod($intakePeriod);

        return $ledgerable->ledgerTransactions()
            ->where('fee_type_id', $feeType->id)
            ->when($recordType, fn ($q) => $q->where('type', $recordType))
            ->where('intake_period_id', $intakePeriod->id)
            ->latest()
            ->first();
    }

    /**
     * @return array{invoice: ?Ledger, receipt: ?Ledger}
     */
    public static function getLedgerPairByOrderReference(string $orderReference): array
    {
        $records = Ledger::where('system_reference', $orderReference)->get();

        return [
            'invoice' => $records->firstWhere('type', 'invoice'),
            'receipt' => $records->firstWhere('type', 'receipt'),
        ];
    }

    public static function hasPaidReceipt(Model $ledgerable, FeeTypeEnum $feeType): bool
    {
        $feeTypeModel = self::getFeeTypeBySlug($feeType->slug());
        if (! $feeTypeModel) {
            return false;
        }

        return $ledgerable->ledgerTransactions()
            ->where('fee_type_id', $feeTypeModel->id)
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->exists();
    }

    /* -----------------------------------------------------------------
     |  Payment Gateway
     | -----------------------------------------------------------------
     */

    /**
     * @throws ConnectionException
     */
    public static function checkTransactionStatus(string $orderReference): array
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(
            config('custom.payments.payment-gateway.base_url').
            "/payments/transaction/{$orderReference}/status/check"
        )->json();
    }

    /* -----------------------------------------------------------------
     |  Ledger Entry Management
     | -----------------------------------------------------------------
     */

    public static function createInvoiceEntry(
        CreateInvoiceDto $dto,
        Model $ledgerable,
        ?IntakePeriod $intakePeriod = null,
        bool $hasPaymentGateway = true,
        ?int $studentApplicationId = null,
    ): Ledger {
        $attributes = $dto->toArray();
        if ($studentApplicationId !== null) {
            $attributes['student_application_id'] = $studentApplicationId;
        }

        return self::createLedgerEntryOn($ledgerable, $attributes, $intakePeriod, $hasPaymentGateway);
    }

    public static function createReceiptEntry(
        CreateReceiptDto $dto,
        Model $ledgerable,
        ?IntakePeriod $intakePeriod = null,
        bool $hasPaymentGateway = true,
        ?int $studentApplicationId = null,
    ): Ledger {
        $attributes = $dto->toArray();
        if ($studentApplicationId !== null) {
            $attributes['student_application_id'] = $studentApplicationId;
        }

        return self::createLedgerEntryOn($ledgerable, $attributes, $intakePeriod, $hasPaymentGateway);
    }

    public static function updateReceiptEntry(Ledger $ledger, UpdateReceiptDto $dto): Ledger
    {
        return tap($ledger)->update($dto->toArray());
    }

    public static function deleteNotPaidLedgerEntries(string $orderReference, ?IntakePeriod $intakePeriod = null): void
    {
        $ledger = Ledger::where('system_reference', $orderReference)->first();

        if (! $ledger) {
            return;
        }

        $intakePeriod = $intakePeriod ?? IntakePeriod::query()->find($ledger->intake_period_id);
        if (! $intakePeriod) {
            return;
        }

        Ledger::where('ledgerable_id', $ledger->ledgerable_id)
            ->where('ledgerable_type', $ledger->ledgerable_type)
            ->where('fee_type_id', $ledger->fee_type_id)
            ->where('intake_period_id', $intakePeriod->id)
            ->where('payment_status', '!=', 'paid')
            ->delete();
    }

    /* -----------------------------------------------------------------
     |  DTO Assemblers
     | -----------------------------------------------------------------
     */

    public static function assembleInvoiceData(Request $request, array $data, int $tenantId): CreateInvoiceDto
    {
        return new CreateInvoiceDto(
            tenant_id: $tenantId,
            fee_type_id: $request->feeTypeId,
            type: 'invoice',
            payment_status: 'pending',
            amount: $request->amount,
            system_reference: $request->orderReference,
            payment_reference: $data['transactionReference'],
            response_code: $data['responseCode'],
            response_message: $data['responseMessage'],
        );
    }

    public static function assembleReceiptData(Request $request, array $data, int $tenantId): CreateReceiptDto
    {
        return new CreateReceiptDto(
            tenant_id: $tenantId,
            fee_type_id: $request->feeTypeId,
            type: 'receipt',
            payment_status: 'pending',
            amount: 0.00,
            system_reference: $request->orderReference,
            payment_reference: $data['transactionReference'] ?? null,
        );
    }

    public static function assembleReceiptUpdateData(array $data): UpdateReceiptDto
    {
        return new UpdateReceiptDto(
            payment_status: $data['status'],
            payment_option: $data['paymentOption'] ?? null,
            payment_date: $data['createdDate'],
            amount: $data['amount'],
            system_reference: $data['orderReference'],
            payment_reference: $data['reference'],
            currency: $data['currency'],
            client_fee: $data['clientFee'],
            merchant_fee: $data['merchantFee'],
        );
    }

    /* -----------------------------------------------------------------
     |  Registration Fee Helpers
     | -----------------------------------------------------------------
     */

    public static function isApplicationFeeExempt(?User $user): bool
    {
        if ($user === null || ! is_string($user->email)) {
            return false;
        }

        $email = strtolower(trim($user->email));
        $exemptEmails = array_map(
            static fn (mixed $exemptEmail): string => strtolower(trim((string) $exemptEmail)),
            (array) config('custom.applications.application_fee_exempt_emails', []),
        );

        return in_array($email, $exemptEmails, true);
    }

    public static function levelRequiresApplicationFeePayment(Level $level, ?User $user = null): bool
    {
        return (bool) $level->has_application_fee_payment
            && ! self::isApplicationFeeExempt($user);
    }

    public static function hasPaidApplicationFeeAndNotApplied(?User $user = null, ?IntakePeriod $intakePeriod = null): bool
    {
        $user = self::resolveUser($user);
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);

        $applicationFee = app(ApplicationFeeService::class)->forUserAndIntake($user, $intakePeriod);

        if ($applicationFee === null) {
            return false;
        }

        if ($applicationFee->student_application_id !== null) {
            return false;
        }

        return $applicationFee->isPaid() || self::hasPaidReceipt($applicationFee, FeeTypeEnum::APPLICATION_FEE);
    }

    /**
     * Determine if the user has paid the application fee for the current intake.
     */
    public static function hasPaidApplicationFee(User $user, ?IntakePeriod $intakePeriod = null): bool
    {
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);
        $applicationFee = app(ApplicationFeeService::class)->forUserAndIntake($user, $intakePeriod);

        if ($applicationFee === null) {
            return false;
        }

        return $applicationFee->isPaid() || self::hasPaidReceipt($applicationFee, FeeTypeEnum::APPLICATION_FEE);
    }

    public static function updateRegistrationFeeLedgerEntries(
        StudentApplication $studentApplication,
        ?User $user = null,
        ?IntakePeriod $intakePeriod = null
    ): void {
        $user = self::resolveUser($user);
        $applicationFee = app(ApplicationFeeService::class)->activeApplicationFee($user);

        if ($applicationFee === null) {
            $intakePeriod = self::resolveIntakePeriod($intakePeriod);
            $applicationFee = app(ApplicationFeeService::class)->forUserAndIntake($user, $intakePeriod);
        } else {
            $intakePeriod = $applicationFee->intakePeriod;
        }

        if ($applicationFee === null) {
            return;
        }

        $invoice = self::getLatestLedgerRecordForLedgerable(
            $applicationFee,
            FeeTypeEnum::APPLICATION_FEE->slug(),
            'invoice',
            $intakePeriod,
        );
        $receipt = self::getLatestLedgerRecordForLedgerable(
            $applicationFee,
            FeeTypeEnum::APPLICATION_FEE->slug(),
            'receipt',
            $intakePeriod,
        );

        if (! $invoice || ! $receipt) {
            return;
        }

        $updateData = [
            'student_application_id' => $studentApplication->id,
            'level_id' => $studentApplication->departmentLevel->level_id,
        ];

        $invoice->update($updateData);
        $receipt->update($updateData);

        app(ApplicationFeeService::class)->markSubmitted($applicationFee, $studentApplication);
    }

    /* -----------------------------------------------------------------
     |  Cash Invoice Helpers
     | -----------------------------------------------------------------
     */

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public static function invoiceCashStudent(
        User $user,
        FeeType $feeType,
        float $amount,
        string $systemReference,
        string $paymentReference,
        StudentApplication $studentApplication,
        ?IntakePeriod $intakePeriod = null
    ): void {
        $invoiceDto = new CreateInvoiceDto(
            tenant_id: $user->tenant_id,
            fee_type_id: $feeType->id,
            type: 'invoice',
            payment_status: 'pending',
            amount: $amount,
            system_reference: $systemReference,
            payment_reference: $paymentReference,
            response_code: null,
            response_message: null,
        );

        $receiptDto = new CreateReceiptDto(
            tenant_id: $user->tenant_id,
            fee_type_id: $feeType->id,
            type: 'receipt',
            payment_status: 'pending',
            amount: 0.00,
            system_reference: $systemReference,
            payment_reference: $paymentReference,
        );

        $invoice = self::createInvoiceEntry($invoiceDto, $user, $intakePeriod, false);
        $receipt = self::createReceiptEntry($receiptDto, $user, $intakePeriod, false);

        self::updateRegistrationFeeLedgerEntries($studentApplication, $user, $intakePeriod);

        if ($mediaId = self::handleProofOfPaymentUpload($invoice)) {
            $invoice->update(['proof_of_payment_id' => $mediaId]);
            $receipt->update(['proof_of_payment_id' => $mediaId, 'payment_option' => 'cash']);
        }
    }

    /* -----------------------------------------------------------------
     |  File Upload Helpers
     | -----------------------------------------------------------------
     */

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    private static function handleProofOfPaymentUpload(Ledger $invoice): ?int
    {
        if (! request()->hasFile('proof_of_payment')) {
            return null;
        }

        $file = request()->file('proof_of_payment');

        if (! $file->isValid() || $file->getSize() <= 0) {
            return null;
        }

        return $invoice->addMedia($file)
            ->toMediaCollection('receipts')
            ->id ?? null;
    }

    /* -----------------------------------------------------------------
     |  Private Utilities
     | -----------------------------------------------------------------
     */

    private static function resolveUser(?User $user = null): User
    {
        return $user ?? request()->user();
    }

    private static function resolveIntakePeriod(?IntakePeriod $intakePeriod = null): Model
    {
        return $intakePeriod ?? Helper::resolveIntakePeriod();
    }

    private static function createLedgerEntryOn(
        Model $ledgerable,
        array $attributes,
        ?IntakePeriod $intakePeriod = null,
        bool $hasPaymentGateway = true
    ): Ledger {
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);

        $attributes['tenant_id'] ??= $ledgerable->tenant_id ?? self::resolveUser()->tenant_id;
        $attributes['intake_period_id'] = $intakePeriod->id;
        $attributes['payment_gateway'] = $hasPaymentGateway
            ? config('custom.payments.payment-gateway.name')
            : null;

        return $ledgerable->ledgerTransactions()->create($attributes);
    }

    public static function levelsWithApplicationFee(): Collection|array
    {
        return Level::where('has_application_fee_payment', true)->get();
    }
}

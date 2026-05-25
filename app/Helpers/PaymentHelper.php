<?php

namespace App\Helpers;

use App\DTO\Integrations\CreateInvoiceDto;
use App\DTO\Integrations\CreateReceiptDto;
use App\DTO\Integrations\UpdateReceiptDto;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use LaravelIdea\Helper\App\Models\Institution\_IH_Level_C;
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

    public static function getLatestLedgerRecord(
        string        $slug,
        ?string       $recordType = null,
        ?User         $user = null,
        ?IntakePeriod $intakePeriod = null
    ): ?object
    {
        $feeType = self::getFeeTypeBySlug($slug);
        if (!$feeType) return null;

        $user = self::resolveUser($user);
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);

        return $user->ledgerTransactions()
            ->where('fee_type_id', $feeType->id)
            ->when($recordType, fn($q, $type) => $q->where('type', $type))
            ->where('intake_period_id', $intakePeriod->id)
            ->latest()
            ->first();
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
            config('custom.payments.payment-gateway.base_url') .
            "/payments/transaction/{$orderReference}/status/check"
        )->json();
    }

    /* -----------------------------------------------------------------
     |  Ledger Entry Management
     | -----------------------------------------------------------------
     */

    public static function createInvoiceEntry(
        CreateInvoiceDto $dto,
        ?User            $user = null,
        ?IntakePeriod    $intakePeriod = null,
        bool             $hasPaymentGateway = true
    ): Ledger
    {
        return self::createLedgerEntry($dto->toArray(), $user, $intakePeriod, $hasPaymentGateway);
    }

    public static function createReceiptEntry(
        CreateReceiptDto $dto,
        ?User            $user = null,
        ?IntakePeriod    $intakePeriod = null,
        bool             $hasPaymentGateway = true
    ): Ledger
    {
        return self::createLedgerEntry($dto->toArray(), $user, $intakePeriod, $hasPaymentGateway);
    }

    public static function updateReceiptEntry(Ledger $ledger, UpdateReceiptDto $dto): Ledger
    {
        return tap($ledger)->update($dto->toArray());
    }

    public static function deleteNotPaidLedgerEntries(string $orderReference, ?IntakePeriod $intakePeriod = null): void
    {
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);

        $ledger = Ledger::where('system_reference', $orderReference)
            ->where('intake_period_id', $intakePeriod->id)
            ->first();

        if (!$ledger) return;

        Ledger::where('ledgerable_id', $ledger->ledgerable_id)
            ->where('ledgerable_type', $ledger->ledgerable_type)
            ->where('intake_period_id', $intakePeriod->id)
            ->where('payment_status', '!=', 'paid')
            ->delete();
    }

    /* -----------------------------------------------------------------
     |  DTO Assemblers
     | -----------------------------------------------------------------
     */

    public static function assembleInvoiceData(Request $request, array $data): CreateInvoiceDto
    {
        return new CreateInvoiceDto(
            tenant_id: self::resolveUser()->tenant_id,
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

    public static function assembleReceiptData(Request $request, array $data): CreateReceiptDto
    {
        return new CreateReceiptDto(
            tenant_id: self::resolveUser()->tenant_id,
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

    public static function hasPaidApplicationFeeAndNotApplied(?User $user = null, ?IntakePeriod $intakePeriod = null): bool
    {
        $user = self::resolveUser($user);
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);
        /**$name = strtolower(trim($intakePeriod->name ?? ''));
        if (
            in_array(
                $name,
                ['january intake (sdp & abma)', 'january intake (sdp & abma & ojet)']
            )
        ) {
            return true;
        }*/

        /*$debugEmails = ['jamesgudhlanga@gmail.com', 'ethanmuku2020@gmail.com'];
        // Whitelist specific email for dev/testing
        if (in_array($user->email, $debugEmails)) {
            return true;
        }*/

        $feeType = self::getFeeTypeBySlug(FeeTypeEnum::APPLICATION_FEE->slug());
        if (!$feeType) return false;

        return $user->ledgerTransactions()
            ->where('fee_type_id', $feeType->id)
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->whereNull('student_program_id')
            ->whereNull('level_id')
            ->where('intake_period_id', $intakePeriod->id)
            ->exists();
    }

    /**
     * Determine if the user has paid the application fee for the current intake.
     */
    public static function hasPaidApplicationFee(User $user): bool
    {
        $feeType = PaymentHelper::getFeeTypeBySlug(FeeTypeEnum::APPLICATION_FEE->slug());

        return $user->ledgerTransactions()
            ->where('type', 'receipt')
            ->where('fee_type_id', $feeType->id)
            ->where('payment_status', 'paid')
            ->where('intake_period_id', Helper::resolveIntakePeriod()->id)
            ->exists();
    }

    public static function updateRegistrationFeeLedgerEntries(
        StudentProgram $studentProgram,
        ?User          $user = null,
        ?IntakePeriod  $intakePeriod = null
    ): void
    {
        $invoice = self::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice', $user, $intakePeriod);
        $receipt = self::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt', $user, $intakePeriod);

        if (!$invoice || !$receipt) return;

        $updateData = [
            'student_program_id' => $studentProgram->id,
            'level_id' => $studentProgram->departmentLevel->level_id,
        ];

        $invoice->update($updateData);
        $receipt->update($updateData);
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
        User           $user,
        FeeType        $feeType,
        float          $amount,
        string         $systemReference,
        string         $paymentReference,
        StudentProgram $studentProgram,
        ?IntakePeriod  $intakePeriod = null
    ): void
    {
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

        self::updateRegistrationFeeLedgerEntries($studentProgram, $user, $intakePeriod);

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
        if (!request()->hasFile('proof_of_payment')) return null;

        $file = request()->file('proof_of_payment');

        if (!$file->isValid() || $file->getSize() <= 0) return null;

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

    private static function createLedgerEntry(
        array         $attributes,
        ?User         $user = null,
        ?IntakePeriod $intakePeriod = null,
        bool          $hasPaymentGateway = true
    ): Ledger
    {
        $user = self::resolveUser($user);
        $intakePeriod = self::resolveIntakePeriod($intakePeriod);

        $attributes['tenant_id'] ??= $user->tenant_id;
        $attributes['intake_period_id'] = $intakePeriod->id;
        $attributes['payment_gateway'] = $hasPaymentGateway
            ? config('custom.payments.payment-gateway.name')
            : null;

        return $user->ledgerTransactions()->create($attributes);
    }

    public static function levelsWithApplicationFee(): Collection|array
    {
        return Level::where('has_application_fee_payment', true)->get();
    }
}

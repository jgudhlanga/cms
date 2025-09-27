<?php

namespace App\Helpers;

use App\DTO\Integrations\CreateInvoiceDto;
use App\DTO\Integrations\CreateReceiptDto;
use App\DTO\Integrations\UpdateReceiptDto;
use App\Enums\Shared\FeeTypeEnum;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\StudentProgram;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PaymentHelper
{
    public static function getFeeTypeBySlug(string $slug): ?FeeType
    {
        return FeeType::where('slug', $slug)->first();
    }

    public static function getLatestLedgerRecord(string $slug, ?string $recordType = null): ?Ledger
    {
        $feeType = self::getFeeTypeBySlug($slug);
        $user = auth()->user();
        return $user->ledgerTransactions()->where('fee_type_id', $feeType?->id)
            ->when($recordType, fn($query, $recordType) => $query->where('type', $recordType))
            ->orderByDesc('created_at')
            ->first();
    }

    /**
     * @throws ConnectionException
     */
    public static function checkTransactionStatus(string $orderReference)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(config('custom.payments.payment-gateway.base_url') . '/payments/transaction/' . $orderReference . '/status/check');
        return $response->json();
    }

    public static function getUser()
    {
        return request()->user();
    }

    public static function createInvoiceEntry(CreateInvoiceDto $dto): Ledger
    {
        $user = self::getUser();
        return $user->ledgerTransactions()->create([
            'tenant_id' => $dto->tenant_id,
            'fee_type_id' => $dto->fee_type_id,
            'type' => $dto->type,
            'payment_status' => $dto->payment_status,
            'amount' => $dto->amount,
            'system_reference' => $dto->system_reference,
            'payment_reference' => $dto->payment_reference,
            'response_code' => $dto->response_code,
            'response_message' => $dto->response_message,
        ]);
    }

    public static function createReceiptEntry(CreateReceiptDto $dto): Ledger
    {
        $user = self::getUser();
        return $user->ledgerTransactions()->create([
            'tenant_id' => $dto->tenant_id,
            'fee_type_id' => $dto->fee_type_id,
            'type' => $dto->type,
            'payment_status' => $dto->payment_status,
            'amount' => $dto->amount,
            'system_reference' => $dto->system_reference,
            'payment_reference' => $dto->payment_reference,
        ]);
    }

    public static function updateReceiptEntry(Ledger $ledger, UpdateReceiptDto $dto): Ledger
    {
        return tap($ledger)->update([
            'payment_status' => $dto->payment_status ?? null,
            'payment_option' => $dto->payment_option ?? null,
            'amount' => $dto->amount,
            'payment_date' => $dto->payment_date,
            'system_reference' => $dto->system_reference ?? null,
            'payment_reference' => $dto->payment_reference ?? null,
            'currency' => $dto->currency ?? null,
            'client_fee' => $dto->client_fee ?? null,
            'merchant_fee' => $dto->merchant_fee ?? null,
        ]);
    }

    public static function deleteNotPaidLedgerEntries(string $orderReference): void
    {
        // Find a ledger with the provided order reference
        $ledger = Ledger::where('system_reference', $orderReference)->first();

        if (!$ledger) {
            return; // No matching ledger found
        }

        // Delete (soft delete) all related ledgers for the same ledgerable
        // where payment_status is not 'paid'
        Ledger::where('ledgerable_id', $ledger->ledgerable_id)
            ->where('ledgerable_type', $ledger->ledgerable_type)
            ->where('payment_status', '!=', 'paid')
            ->delete();

    }

    /**
     * @param Request $request
     * @param mixed $data
     * @return CreateInvoiceDto
     */
    public static function assembleInvoiceData(Request $request, mixed $data): CreateInvoiceDto
    {
        return new CreateInvoiceDto(
            tenant_id: self::getUser()->tenant_id,
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

    public static function assembleReceiptData(Request $request, mixed $data): CreateReceiptDto
    {
        return new CreateReceiptDto(
            tenant_id: self::getUser()?->tenant_id,
            fee_type_id: $request->feeTypeId,
            type: 'receipt',
            payment_status: 'pending',
            amount: 0.00,
            system_reference: $request->orderReference,
            payment_reference: $data['transactionReference'] ?? null,
        );
    }

    public static function assembleReceiptUpdateData(mixed $data): UpdateReceiptDto
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

    public static function hasPaidRegistrationFee(): bool
    {
        $user = auth()->user();
        $feeType = self::getFeeTypeBySlug(FeeTypeEnum::APPLICATION_FEE->slug());

        if (!$feeType) {
            return false;
        }

        return $user->ledgerTransactions()
            ->where('fee_type_id', $feeType->id)
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->whereNull('student_program_id')
            ->whereNull('level_id')
            ->latest()
            ->exists();
    }

    public static function updateRegistrationFeeLedgerEntries(StudentProgram $studentProgram): void
    {
        $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice');
        $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt');
        $invoice->update(['student_program_id' => $studentProgram->id, 'level_id' => $studentProgram->departmentLevel->level_id]);
        $receipt->update(['student_program_id' => $studentProgram->id, 'level_id' => $studentProgram->departmentLevel->level_id]);
    }
}

<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\UpdateLedgerRequest;
use App\Http\Resources\Integrations\LedgerResource;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Users\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function initiatePayment(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-key' => config('custom.payments.payment-gateway.api_key'),
            'x-api-secret' => config('custom.payments.payment-gateway.secret'),
        ])->post(config('custom.payments.payment-gateway.base_url') . '/payments/initiate-transaction', [
            'orderReference' => $request->orderReference,
            'amount' => $request->amount,
            'returnUrl' => config('custom.payments.payment-gateway.return_url'),
            'resultUrl' => config('custom.payments.payment-gateway.result_url'),
            'cancelUrl' => config('custom.payments.payment-gateway.cancel_url'),
            'failureUrl' => config('custom.payments.payment-gateway.failure_url'),
            'itemName' => $request->itemName,
            'itemDescription' => $request->itemDescription,
            'currencyCode' => $request->currencyCode,
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'mobilePhoneNumber' => $request->mobilePhoneNumber,
            'email' => $request->email,
            'paymentMethod' => $request->paymentMethod,
        ]);
        $data = $response->json();
        if (!empty($data['paymentUrl'])) {
            PaymentHelper::createInvoiceEntry(PaymentHelper::assembleInvoiceData($request, $data));
            PaymentHelper::createReceiptEntry(PaymentHelper::assembleReceiptData($request, $data));
        }
        return $data;
    }

    /**
     * @throws ConnectionException
     */
    public function feedback()
    {
        $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice');
        $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt');
        $check = $this->checkStatus($invoice->system_reference);
        // check the payment status from the payment gateway using the system_reference
        if (!empty($check['status']) && Str::lower($check['status']) == 'paid') {
            // update a receipt entry and invoice payment status
            $receipt = PaymentHelper::updateReceiptEntry($receipt, PaymentHelper::assembleReceiptUpdateData($check));
            $invoice->update(['payment_status' => $check['status']]);
            // delete not paid entries for the same ledgerable_id and ledgerable_type
            PaymentHelper::deleteNotPaidLedgerEntries($invoice->system_reference);
        } else {
            $invoice->update(['payment_status' => $check['status'] ?? 'pending']);
            $receipt->update(['payment_status' => $check['status'] ?? 'pending']);
        }

        $details = LedgerResource::make($receipt);
        return Inertia::render('integrations/payments/Feedback', compact('details'));
    }

    public function cancelled()
    {
        $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice');
        $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt');
        $invoice->update(['payment_status' => 'cancelled']);
        $receipt->update(['payment_status' => 'cancelled']);
        $details = LedgerResource::make($invoice);
        return Inertia::render('integrations/payments/Cancelled', compact('details'));
    }

    public function failed()
    {
        $details = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice');
        $details->update(['payment_status' => 'failed']);
        $details = LedgerResource::make($details);
        return Inertia::render('integrations/payments/Failure', compact('details'));
    }

    public function result(): void
    {

    }

    /**
     * @throws ConnectionException
     */
    public function checkStatus(string $orderReference)
    {
        // get either system_reference or payment_reference
        $reference = Ledger::where('system_reference', $orderReference)->first();
        if (!$reference) {
            $reference = Ledger::where('payment_reference', $orderReference)->first();
        }
        //we can search by email to the user, use the id as ledgerable_id and user as ledgerable_type in ledgers table
        if (!$reference) {
            // If not found, try searching by email
            $user = User::where('email', $orderReference)->first();

            if ($user) {
                $reference = Ledger::where('ledgerable_id', $user->id)
                    ->where('ledgerable_type', User::class)
                    ->first();
            }
        }
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(config('custom.payments.payment-gateway.base_url') . '/payments/transaction/' . trim($reference->system_reference) . '/status/check');
        return $response->json();
    }

    public function getLedgerEntries(string $search)
    {
        // Try to find a ledger by system or payment reference
        $reference = Ledger::where('system_reference', $search)
            ->orWhere('payment_reference', $search)
            ->withTrashed()
            ->first();
        // If not found, try by user email
        if (!$reference) {
            $user = User::where('email', $search)->first();

            if ($user) {
                $reference = Ledger::where('ledgerable_id', $user->id)
                    ->withTrashed()
                    ->where('ledgerable_type', User::class)
                    ->first();
            }
        }
        if (!$reference) {
            return response()->json([
                "message" => "No ledger entries found for the provided search {$search}"
            ], 404);
        }
        // Fetch all ledger entries for that ledgerable
        $entries = Ledger::where('ledgerable_id', $reference->ledgerable_id)
            ->where('ledgerable_type', $reference->ledgerable_type)
            ->where('type', 'invoice')
            ->get();

        return LedgerResource::collection($entries);
    }


    /**
     * @throws ConnectionException
     */
    public function checkPaymentStatusForCurrenUser()
    {
        // get either system_reference or payment_reference
        $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice');
        $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt');
        if (!$invoice) {
            return response()->json(['status' => 'not invoice']);
        }
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(config('custom.payments.payment-gateway.base_url') . '/payments/transaction/' . trim($invoice->system_reference) . '/status/check');
        $check = $response->json();
        if (!empty($check['status']) && Str::lower($check['status']) == 'paid') {
            // update a receipt entry and invoice payment status
            PaymentHelper::updateReceiptEntry($receipt, PaymentHelper::assembleReceiptUpdateData($check));
            $invoice->update(['payment_status' => $check['status']]);
            // delete not paid entries for the same ledgerable_id and ledgerable_type
            PaymentHelper::deleteNotPaidLedgerEntries($invoice->system_reference);
            return response()->json(['status' => $check['status']]);
        } else {
            $invoice->update(['payment_status' => $check['status'] ?? 'pending']);
            $receipt->update(['payment_status' => $check['status'] ?? 'pending']);
        }
        return response()->json(['status' => 'not paid']);
    }

    public function updateLedgerRecords(UpdateLedgerRequest $request)
    {
        [
            $amount, $clientFee, $createdDate, $currency, $merchantFee, $paymentOption,
            $orderReference, $paymentReference, $paymentStatus,] = $this->extractFilters($request);
        $records = Ledger::where('system_reference', $orderReference)->get();
        if (!$records->isEmpty()) {
            foreach ($records as $record) {
                if (!empty($paymentStatus) && Str::lower($paymentStatus) === 'paid' && $record->type == 'receipt') {
                    PaymentHelper::updateReceiptEntry($record, PaymentHelper::assembleReceiptUpdateData([
                            'status' => $paymentStatus,
                            'paymentOption' => $paymentOption,
                            'createdDate' => $createdDate,
                            'amount' => $amount,
                            'orderReference' => $orderReference,
                            'reference' => $paymentReference,
                            'currency' => $currency,
                            'clientFee' => $clientFee,
                            'merchantFee' => $merchantFee,]
                    ));
                } else {
                    $record->update(['payment_status' => $paymentStatus ?? 'pending']);
                }
            }
            if (!empty($paymentStatus) && Str::lower($paymentStatus) === 'paid') {
                PaymentHelper::deleteNotPaidLedgerEntries($orderReference);
            }
        }
    }

    public function createCheckStatus()
    {
        return Inertia::render('institution/tools/CheckPaymentStatus');
    }

    private function extractFilters(UpdateLedgerRequest $request): array
    {
        $amount = $request->amount ? $request->amount : null;
        $clientFee = $request->clientFee ? $request->clientFee : null;
        $createdDate = $request->createdDate ? $request->createdDate : null;
        $currency = $request->currency ? $request->currency : null;
        $merchantFee = $request->merchantFee ? $request->merchantFee : 0;
        $orderReference = $request->orderReference ? $request->orderReference : null;
        $paymentReference = $request->paymentReference ? $request->paymentReference : null;
        $paymentStatus = $request->paymentStatus ? $request->paymentStatus : null;
        $paymentOption = $request->paymentOption ? $request->paymentOption : null;
        return [$amount, $clientFee, $createdDate, $currency, $merchantFee, $paymentOption,
            $orderReference, $paymentReference, $paymentStatus,];
    }

    public function checkUserIntakePeriodApplicationFeePaymentStatus(User $user, IntakePeriod $intakePeriod)
    {
        return PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt', $user, $intakePeriod);
    }


    public function checkLevelRequiresApplicationFeePayment(Level $level)
    {
        return $level->has_application_fee_payment;
    }

}

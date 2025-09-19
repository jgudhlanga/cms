<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\Integrations\LedgerResource;
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
        $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::REGISTRATION_FEE->slug(), 'invoice');
        $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::REGISTRATION_FEE->slug(), 'receipt');
        $check = $this->checkStatus($invoice->system_reference);
        // check the payment status from the payment gateway using the system_reference
        if (!empty($check['status']) && Str::lower($check['status']) == 'paid') {
            // update a receipt entry and invoice payment status
            $receipt = PaymentHelper::updateReceiptEntry($receipt, PaymentHelper::assembleReceiptUpdateData($check));
            $invoice->update(['payment_status' => $check['status']]);
        } else {
            $invoice->update(['payment_status' => $check['status'] ?? 'pending']);
            $receipt->update(['payment_status' => $check['status'] ?? 'pending']);
        }

        $details = LedgerResource::make($receipt);
        return Inertia::render('integrations/payments/Feedback', compact('details'));
    }

    public function cancelled()
    {
        $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::REGISTRATION_FEE->slug(), 'invoice');
        $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::REGISTRATION_FEE->slug(), 'receipt');
        $invoice->update(['payment_status' => 'cancelled']);
        $receipt->update(['payment_status' => 'cancelled']);
        $details = LedgerResource::make($invoice);
        return Inertia::render('integrations/payments/Cancelled', compact('details'));
    }

    public function failed()
    {
        $details = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::REGISTRATION_FEE->slug(), 'invoice');
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
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(config('custom.payments.payment-gateway.base_url') . '/payments/transaction/' . $orderReference . '/status/check');
        return $response->json();
    }

}

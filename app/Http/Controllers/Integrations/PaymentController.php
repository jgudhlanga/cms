<?php

namespace App\Http\Controllers\Integrations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

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
        ])->post(config('custom.payments.payment-gateway.base_url').'/payments/initiate-transaction', [
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

        return $response->json();
    }
    public function feedback()
    {
        return Inertia::render('integrations/payments/Feedback');
    }

    public function cancelled()
    {
        return Inertia::render('integrations/payments/Cancelled');
    }

    public function failed()
    {
        return Inertia::render('integrations/payments/Failure');
    }

    public function result(): void
    {
        // Nothing to do here
    }
}

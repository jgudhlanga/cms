<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\InitiatePaymentRequest;
use App\Http\Requests\Integrations\UpdateLedgerRequest;
use App\Http\Resources\Institution\FeeStructureResource;
use App\Http\Resources\Integrations\LedgerResource;
use App\Models\HMS\HostelApplication;
use App\Models\Institution\FeeStructure;
use App\Models\Institution\IntakePeriod;
use App\Models\Institution\Level;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use App\Services\HMS\StudentAccommodationFeeService;
use App\Services\Integrations\OnlinePaymentContextResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected OnlinePaymentContextResolver $paymentContextResolver,
    ) {}

    /**
     * @throws ConnectionException
     */
    public function initiatePayment(InitiatePaymentRequest $request): array
    {
        $context = $this->paymentContextResolver->resolveForInitiate($request);
        $orderReference = $request->orderReference;

        $this->paymentContextResolver->storeOrderReferenceInSession($orderReference);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-key' => config('custom.payments.payment-gateway.api_key'),
            'x-api-secret' => config('custom.payments.payment-gateway.secret'),
        ])->post(config('custom.payments.payment-gateway.base_url').'/payments/initiate-transaction', [
            'orderReference' => $orderReference,
            'amount' => $request->amount,
            'returnUrl' => $this->paymentContextResolver->appendOrderReferenceToUrl(
                config('custom.payments.payment-gateway.return_url'),
                $orderReference,
            ),
            'resultUrl' => config('custom.payments.payment-gateway.result_url'),
            'cancelUrl' => $this->paymentContextResolver->appendOrderReferenceToUrl(
                config('custom.payments.payment-gateway.cancel_url'),
                $orderReference,
            ),
            'failureUrl' => $this->paymentContextResolver->appendOrderReferenceToUrl(
                config('custom.payments.payment-gateway.failure_url'),
                $orderReference,
            ),
            'itemName' => $request->itemName,
            'itemDescription' => $request->itemDescription,
            'currencyCode' => $request->currencyCode,
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'mobilePhoneNumber' => $request->mobilePhoneNumber,
            'email' => $request->email,
            'paymentMethod' => $request->paymentMethod,
        ]);

        $data = $response->json() ?? [];
        $tenantId = (int) ($context->ledgerable->tenant_id ?? $request->user()->tenant_id);

        if (! empty($data['paymentUrl'])) {
            PaymentHelper::createInvoiceEntry(
                PaymentHelper::assembleInvoiceData($request, $data, $tenantId),
                $context->ledgerable,
                $context->intakePeriod,
                studentProgramId: $context->studentProgramId,
            );
            PaymentHelper::createReceiptEntry(
                PaymentHelper::assembleReceiptData($request, $data, $tenantId),
                $context->ledgerable,
                $context->intakePeriod,
                studentProgramId: $context->studentProgramId,
            );
        }

        return $data;
    }

    /**
     * @throws ConnectionException
     */
    public function feedback(): Response
    {
        ['invoice' => $invoice, 'receipt' => $receipt] = $this->paymentContextResolver->resolveLedgerPair();

        abort_if($invoice === null || $receipt === null, 404);

        $check = $this->checkStatus($invoice->system_reference);
        $receipt = $this->syncLedgerPaymentStatus($invoice, $receipt, $check) ?? $receipt;

        return $this->renderPaymentStatusPage('integrations/payments/Feedback', $receipt);
    }

    public function cancelled(): Response
    {
        ['invoice' => $invoice, 'receipt' => $receipt] = $this->paymentContextResolver->resolveLedgerPair();

        abort_if($invoice === null || $receipt === null, 404);

        $invoice->update(['payment_status' => 'cancelled']);
        $receipt->update(['payment_status' => 'cancelled']);

        return $this->renderPaymentStatusPage('integrations/payments/Cancelled', $invoice);
    }

    public function failed(): Response
    {
        ['invoice' => $invoice, 'receipt' => $receipt] = $this->paymentContextResolver->resolveLedgerPair();

        abort_if($invoice === null, 404);

        $invoice->update(['payment_status' => 'failed']);
        $receipt?->update(['payment_status' => 'failed']);

        return $this->renderPaymentStatusPage('integrations/payments/Failure', $invoice);
    }

    public function result(): void {}

    /**
     * @throws ConnectionException
     */
    public function checkStatus(string $orderReference): array
    {
        $reference = Ledger::where('system_reference', $orderReference)->first();
        if (! $reference) {
            $reference = Ledger::where('payment_reference', $orderReference)->first();
        }

        if (! $reference) {
            $user = User::where('email', $orderReference)->first();

            if ($user) {
                $reference = Ledger::where('ledgerable_id', $user->id)
                    ->where('ledgerable_type', User::class)
                    ->first();
            }
        }

        if (! $reference) {
            return ['status' => 'not_found'];
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(config('custom.payments.payment-gateway.base_url').'/payments/transaction/'.trim($reference->system_reference).'/status/check');

        return $response->json() ?? [];
    }

    public function getLedgerEntries(string $search)
    {
        $reference = Ledger::where('system_reference', $search)
            ->orWhere('payment_reference', $search)
            ->withTrashed()
            ->first();

        if (! $reference) {
            $user = User::where('email', $search)->first();

            if ($user) {
                $reference = Ledger::where('ledgerable_id', $user->id)
                    ->withTrashed()
                    ->where('ledgerable_type', User::class)
                    ->first();
            }
        }

        if (! $reference) {
            return response()->json([
                'message' => "No ledger entries found for the provided search {$search}",
            ], 404);
        }

        $entries = Ledger::where('ledgerable_id', $reference->ledgerable_id)
            ->where('ledgerable_type', $reference->ledgerable_type)
            ->where('type', 'invoice')
            ->get();

        return LedgerResource::collection($entries);
    }

    /**
     * @throws ConnectionException
     */
    public function checkPaymentStatusForCurrenUser(Request $request): JsonResponse
    {
        $invoice = null;
        $receipt = null;

        $orderReference = $request->input('orderReference')
            ?? session(OnlinePaymentContextResolver::SESSION_ORDER_REFERENCE_KEY);

        if ($orderReference) {
            ['invoice' => $invoice, 'receipt' => $receipt] = PaymentHelper::getLedgerPairByOrderReference($orderReference);
        }

        if ($invoice === null && $request->filled('feeTypeId')) {
            $feeType = FeeType::query()->find($request->integer('feeTypeId'));
            $feeTypeEnum = $feeType ? FeeTypeEnum::fromFeeType($feeType) : null;
            $user = $request->user();

            if ($feeTypeEnum && $user) {
                $ledgerable = $user;

                if ($feeTypeEnum->ledgerableClass() === HostelApplication::class) {
                    $ledgerable = $this->paymentContextResolver->resolveForInitiate(
                        $request->merge(['feeTypeId' => $feeType->id])
                    )->ledgerable;
                } elseif ($feeTypeEnum->ledgerableClass() === StudentProgram::class && $request->filled('ledgerableId')) {
                    $ledgerable = $this->paymentContextResolver->resolveForInitiate(
                        $request->merge(['feeTypeId' => $feeType->id])
                    )->ledgerable;
                }

                $invoice = PaymentHelper::getLatestLedgerRecordForLedgerable(
                    $ledgerable,
                    $feeTypeEnum->slug(),
                    'invoice',
                );
                $receipt = PaymentHelper::getLatestLedgerRecordForLedgerable(
                    $ledgerable,
                    $feeTypeEnum->slug(),
                    'receipt',
                );
            }
        }

        if ($invoice === null) {
            $invoice = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'invoice');
            $receipt = PaymentHelper::getLatestLedgerRecord(FeeTypeEnum::APPLICATION_FEE->slug(), 'receipt');
        }

        if ($invoice === null) {
            return response()->json(['status' => 'not invoice']);
        }

        $receipt ??= PaymentHelper::getLedgerPairByOrderReference($invoice->system_reference)['receipt'];

        $check = $this->checkStatus($invoice->system_reference);

        if (! empty($check['status']) && Str::lower($check['status']) === 'paid') {
            $this->syncLedgerPaymentStatus($invoice, $receipt, $check);

            return response()->json(['status' => $check['status']]);
        }

        $invoice->update(['payment_status' => $check['status'] ?? 'pending']);
        $receipt?->update(['payment_status' => $check['status'] ?? 'pending']);

        return response()->json(['status' => 'not paid']);
    }

    public function updateLedgerRecords(UpdateLedgerRequest $request): void
    {
        [
            $amount, $clientFee, $createdDate, $currency, $merchantFee, $paymentOption,
            $orderReference, $paymentReference, $paymentStatus,
        ] = $this->extractFilters($request);

        $records = Ledger::where('system_reference', $orderReference)->get();

        if (! $records->isEmpty()) {
            foreach ($records as $record) {
                if (! empty($paymentStatus) && Str::lower($paymentStatus) === 'paid' && $record->type == 'receipt') {
                    PaymentHelper::updateReceiptEntry($record, PaymentHelper::assembleReceiptUpdateData([
                        'status' => $paymentStatus,
                        'paymentOption' => $paymentOption,
                        'createdDate' => $createdDate,
                        'amount' => $amount,
                        'orderReference' => $orderReference,
                        'reference' => $paymentReference,
                        'currency' => $currency,
                        'clientFee' => $clientFee,
                        'merchantFee' => $merchantFee,
                    ]));
                } else {
                    $record->update(['payment_status' => $paymentStatus ?? 'pending']);
                }
            }

            if (! empty($paymentStatus) && Str::lower($paymentStatus) === 'paid') {
                PaymentHelper::deleteNotPaidLedgerEntries($orderReference);
            }
        }
    }

    public function createCheckStatus(): Response
    {
        return Inertia::render('institution/tools/CheckPaymentStatus');
    }

    public function checkUserIntakePeriodApplicationFeePaymentStatus(User $user, IntakePeriod $intakePeriod)
    {
        return PaymentHelper::getLatestLedgerRecord(
            FeeTypeEnum::APPLICATION_FEE->slug(),
            'receipt',
            $user,
            $intakePeriod,
        );
    }

    public function checkLevelRequiresApplicationFeePayment(Level $level): bool
    {
        return $level->has_application_fee_payment;
    }

    public function registrationFeePaymentOptions(): Response
    {
        $registrationFee = PaymentHelper::getFeeStructureResourceBySlug(FeeTypeEnum::APPLICATION_FEE->slug());

        return Inertia::render('portal/application/RegistrationFeePaymentOptions', compact('registrationFee'));
    }

    /**
     * @throws AuthorizationException
     */
    public function accommodationFeePaymentOptions(StudentAccommodationFeeService $feeService): Response|RedirectResponse
    {
        $this->authorize('manageStudentAccommodationDetails');

        $student = $this->profileStudent();
        $feeType = PaymentHelper::getFeeTypeBySlug(FeeTypeEnum::STUDENT_ACCOMMODATION_FEE->slug());
        $feeStructure = $feeType
            ? FeeStructure::query()->where('fee_type_id', $feeType->id)->first()
            : null;

        if ($feeStructure === null) {
            return redirect()
                ->route('portal.profile.accommodations')
                ->with('error', __('students.accommodation_fee_payment_unavailable'));
        }

        $openApplication = $feeService->openAwaitingPaymentApplication($student);

        if ($openApplication === null) {
            return redirect()
                ->route('portal.profile.accommodations')
                ->with('error', __('students.accommodation_payment_application_required'));
        }

        return Inertia::render('portal/hms/AccommodationFeePaymentOptions', [
            'accommodationFee' => FeeStructureResource::make($feeStructure),
            'fees' => $feeService->summaryForStudent($student),
            'hostelApplicationId' => $openApplication->id,
        ]);
    }

    private function syncLedgerPaymentStatus(Ledger $invoice, ?Ledger $receipt, array $check): ?Ledger
    {
        if (! empty($check['status']) && Str::lower($check['status']) === 'paid' && $receipt !== null) {
            $receipt = PaymentHelper::updateReceiptEntry(
                $receipt,
                PaymentHelper::assembleReceiptUpdateData($check),
            );
            $invoice->update(['payment_status' => $check['status']]);
            PaymentHelper::deleteNotPaidLedgerEntries($invoice->system_reference);

            return $receipt;
        }

        $invoice->update(['payment_status' => $check['status'] ?? 'pending']);
        $receipt?->update(['payment_status' => $check['status'] ?? 'pending']);

        return $receipt;
    }

    private function profileStudent(): Student
    {
        return request()->user()->studentProfile;
    }

    private function renderPaymentStatusPage(string $page, Ledger $ledger): Response
    {
        $ledger->loadMissing('feeType');
        $feeTypeEnum = $ledger->feeType
            ? FeeTypeEnum::fromFeeType($ledger->feeType)
            : null;

        return Inertia::render($page, [
            'details' => LedgerResource::make($ledger),
            'redirectRoute' => $this->paymentContextResolver->postPaymentRouteForLedger($ledger),
            'isApplicationFee' => $feeTypeEnum === FeeTypeEnum::APPLICATION_FEE,
            'isAccommodationFee' => $feeTypeEnum?->isAccommodationFee() ?? false,
        ]);
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

        return [
            $amount, $clientFee, $createdDate, $currency, $merchantFee, $paymentOption,
            $orderReference, $paymentReference, $paymentStatus,
        ];
    }
}

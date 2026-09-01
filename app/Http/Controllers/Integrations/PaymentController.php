<?php

namespace App\Http\Controllers\Integrations;

use App\Enums\Integrations\LedgerEmailSearchTypeEnum;
use App\Enums\Integrations\PaymentCurrencyCodeEnum;
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
use App\Models\Students\ApplicationFee;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\HMS\AccommodationPaymentQuoteService;
use App\Services\HMS\StudentAccommodationFeeService;
use App\Services\Integrations\LedgerEmailSearchService;
use App\Services\Integrations\OnlinePaymentContextResolver;
use App\Services\Students\ApplicationFeeService;
use App\Services\Students\StudentExamResultAccessService;
use App\Services\Students\StudentFeeClearanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected OnlinePaymentContextResolver $paymentContextResolver,
        protected ApplicationFeeService $applicationFeeService,
        protected LedgerEmailSearchService $ledgerEmailSearchService,
    ) {}

    /**
     * @throws ConnectionException
     */
    public function initiatePayment(InitiatePaymentRequest $request): array|JsonResponse
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

        if (empty($data['paymentUrl'])) {
            $gatewayMessage = $data['responseMessage'] ?? null;

            return response()->json([
                'responseMessage' => filled($gatewayMessage)
                    ? $gatewayMessage
                    : __('trans.payment_error_description'),
                'responseCode' => $data['responseCode'] ?? null,
            ], 502);
        }

        PaymentHelper::createInvoiceEntry(
            PaymentHelper::assembleInvoiceData($request, $data, $tenantId),
            $context->ledgerable,
            $context->intakePeriod,
            studentApplicationId: $context->studentApplicationId,
        );
        PaymentHelper::createReceiptEntry(
            PaymentHelper::assembleReceiptData($request, $data, $tenantId),
            $context->ledgerable,
            $context->intakePeriod,
            studentApplicationId: $context->studentApplicationId,
        );

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

        return $this->renderPaymentStatusPage(
            'integrations/payments/Cancelled',
            $invoice,
            useFailureRoute: true,
        );
    }

    public function failed(): Response
    {
        ['invoice' => $invoice, 'receipt' => $receipt] = $this->paymentContextResolver->resolveLedgerPair();

        abort_if($invoice === null, 404);

        $invoice->update(['payment_status' => 'failed']);
        $receipt?->update(['payment_status' => 'failed']);

        return $this->renderPaymentStatusPage(
            'integrations/payments/Failure',
            $invoice,
            useFailureRoute: true,
        );
    }

    public function result(Request $request): JsonResponse
    {
        $this->assertValidPaymentWebhook($request);

        $ledgerRequest = UpdateLedgerRequest::createFrom($request);
        $ledgerRequest->setContainer(app())->setRedirector(app(Redirector::class))->validateResolved();

        $this->updateLedgerRecords($ledgerRequest);

        return response()->json(['status' => 'ok']);
    }

    /**
     * @throws ConnectionException
     */
    public function checkStatus(string $orderReference, ?Request $request = null): array
    {
        $request ??= request();

        $reference = $this->ledgerEmailSearchService->findByReference($orderReference);

        if ($reference === null) {
            $user = $this->ledgerEmailSearchService->findUserByEmail($orderReference);

            if ($user !== null) {
                $requestedType = LedgerEmailSearchTypeEnum::tryFromRequest(
                    $request->input('ledgerableType') ?? $request->query('ledgerableType'),
                );

                $reference = $this->ledgerEmailSearchService->resolveReferenceLedgerByEmailPriority(
                    $user,
                    $requestedType,
                );
            }
        }

        if ($reference === null) {
            return ['status' => 'not_found'];
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->get(config('custom.payments.payment-gateway.base_url').'/payments/transaction/'.trim($reference->system_reference).'/status/check');

        return $response->json() ?? [];
    }

    public function getLedgerEntries(string $search, Request $request)
    {
        $reference = $this->ledgerEmailSearchService->findByReference($search, withTrashed: true);

        if ($reference !== null) {
            return LedgerResource::collection(
                $this->ledgerEmailSearchService->invoicesForReferenceLedger($reference),
            );
        }

        $user = $this->ledgerEmailSearchService->findUserByEmail($search);

        if ($user === null) {
            return response()->json([
                'message' => "No ledger entries found for the provided search {$search}",
            ], 404);
        }

        $discoveredTypes = $this->ledgerEmailSearchService->discoverTypes($user);

        if ($discoveredTypes->isEmpty()) {
            return response()->json([
                'message' => "No ledger entries found for the provided search {$search}",
            ], 404);
        }

        $requestedType = LedgerEmailSearchTypeEnum::tryFromRequest($request->query('ledgerableType'));

        if ($requestedType !== null && ! $discoveredTypes->contains($requestedType)) {
            return response()->json([
                'message' => 'Invalid ledgerable type for the provided search.',
            ], 422);
        }

        if ($requestedType === null) {
            return response()->json([
                'requiresTypeSelection' => true,
                'types' => $this->ledgerEmailSearchService->formatTypeOptions($discoveredTypes),
            ]);
        }

        $type = $requestedType;

        return LedgerResource::collection(
            $this->ledgerEmailSearchService->resolveInvoices($user, $type),
        );
    }

    /**
     * @throws ConnectionException
     */
    public function checkPaymentStatusForCurrenUser(Request $request): JsonResponse
    {
        $user = $request->user();
        $feeType = $request->filled('feeTypeId')
            ? FeeType::query()->find($request->integer('feeTypeId'))
            : null;
        $feeTypeEnum = $feeType ? FeeTypeEnum::fromFeeType($feeType) : null;

        if (
            $user
            && $feeTypeEnum === FeeTypeEnum::APPLICATION_FEE
            && $this->applicationFeeService->paidUnusedFee($user) !== null
        ) {
            return response()->json(['status' => 'paid']);
        }

        $ledgerable = $this->resolveLedgerableForCurrentUserStatusCheck($request, $feeType, $feeTypeEnum);
        $invoice = null;
        $receipt = null;

        $orderReference = $request->input('orderReference')
            ?? session(OnlinePaymentContextResolver::SESSION_ORDER_REFERENCE_KEY);

        if ($orderReference) {
            ['invoice' => $invoice, 'receipt' => $receipt] = PaymentHelper::getLedgerPairByOrderReference($orderReference);

            if ($ledgerable !== null && ! $this->ledgerBelongsToLedgerable($invoice, $ledgerable)) {
                $invoice = null;
                $receipt = null;
            }
        }

        if ($invoice === null && $ledgerable !== null && $feeTypeEnum !== null) {
            $intakePeriod = $ledgerable instanceof ApplicationFee ? $ledgerable->intakePeriod : null;
            $invoice = PaymentHelper::getPaidOrLatestLedgerRecordForLedgerable(
                $ledgerable,
                $feeTypeEnum->slug(),
                'invoice',
                $intakePeriod,
            );
            $receipt = PaymentHelper::getPaidOrLatestLedgerRecordForLedgerable(
                $ledgerable,
                $feeTypeEnum->slug(),
                'receipt',
                $intakePeriod,
            );
        }

        if ($invoice === null) {
            $applicationFee = $this->applicationFeeService->activeApplicationFee($request->user())
                ?? $this->applicationFeeService->forUserAndIntake($request->user());
            if ($applicationFee !== null) {
                $invoice = PaymentHelper::getPaidOrLatestLedgerRecordForLedgerable(
                    $applicationFee,
                    FeeTypeEnum::APPLICATION_FEE->slug(),
                    'invoice',
                    $applicationFee->intakePeriod,
                );
                $receipt = PaymentHelper::getPaidOrLatestLedgerRecordForLedgerable(
                    $applicationFee,
                    FeeTypeEnum::APPLICATION_FEE->slug(),
                    'receipt',
                    $applicationFee->intakePeriod,
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

        if ($this->ledgerIsPaid($invoice) || $this->ledgerIsPaid($receipt)) {
            return response()->json(['status' => 'paid']);
        }

        $check = $this->checkStatus($invoice->system_reference);
        $this->syncLedgerPaymentStatus($invoice, $receipt, $check);

        if ($this->gatewayPaymentStatus($check) === 'paid' || $this->ledgerIsPaid($invoice->fresh()) || $this->ledgerIsPaid($receipt?->fresh())) {
            return response()->json(['status' => 'paid']);
        }

        return response()->json(['status' => 'not paid']);
    }

    public function updateLedgerRecords(UpdateLedgerRequest $request): void
    {
        [
            $amount, $clientFee, $createdDate, $currency, $merchantFee, $paymentOption,
            $orderReference, $paymentReference, $paymentStatus,
        ] = $this->extractFilters($request);

        /** @var Collection<int, Ledger> $records */
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
        $applicationFee = $this->applicationFeeService->forUserAndIntake($user, $intakePeriod);

        if ($applicationFee === null) {
            return null;
        }

        return PaymentHelper::getLatestLedgerRecordForLedgerable(
            $applicationFee,
            FeeTypeEnum::APPLICATION_FEE->slug(),
            'receipt',
            $intakePeriod,
        );
    }

    public function checkLevelRequiresApplicationFeePayment(Level $level): bool
    {
        return PaymentHelper::levelRequiresApplicationFeePayment($level, request()->user());
    }

    public function registrationFeePaymentOptions(): Response|RedirectResponse
    {
        $user = request()->user();
        $applicationFee = $this->applicationFeeService->activeApplicationFee($user);

        if ($applicationFee === null) {
            return redirect()
                ->route('portal.application.level-options')
                ->with('error', __('trans.application_fee_record_required'));
        }

        if (PaymentHelper::isApplicationFeeExempt($user)) {
            $this->applicationFeeService->abandonUnpaidApplicationFee($user);
            session(['application.level_id' => $applicationFee->level_id]);

            return to_route('portal.application.create');
        }

        if ($applicationFee->isPaid() || $applicationFee->hasPaidReceipt()) {
            session(['application.level_id' => $applicationFee->level_id]);

            return $user->has_student_profile
                ? to_route('portal.profile.applications', ['fee_paid' => 1])
                : to_route('portal.application.create');
        }

        $applicationFee->load(['level', 'intakePeriod']);
        $registrationFee = PaymentHelper::getFeeStructureResourceBySlug(FeeTypeEnum::APPLICATION_FEE->slug());

        return Inertia::render('portal/application/RegistrationFeePaymentOptions', [
            'registrationFee' => $registrationFee,
            'applicationFeeId' => $applicationFee->id,
            'applicationFeeStatus' => $applicationFee->status->value,
            'applicationFeeStatusLabel' => $applicationFee->status->label(),
            'levelName' => $applicationFee->level?->name,
            'intakeName' => $applicationFee->intakePeriod?->name,
            'applicationStep' => 'fee',
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function tuitionFeePaymentOptions(
        StudentFeeClearanceService $feeClearanceService,
        StudentExamResultAccessService $examResultAccessService,
    ): Response|RedirectResponse {
        $this->authorize('manageStudentFinancialRecords');

        $student = $this->profileStudent();
        $student->loadMissing(['user', 'latestApplication']);

        $studentApplication = $student->latestApplication;

        if (! ($studentApplication instanceof StudentApplication)) {
            return redirect()
                ->route('portal.profile.financials')
                ->with('error', __('trans.tuition_fee_payment_unavailable'));
        }

        $feeType = PaymentHelper::getFeeTypeBySlug(FeeTypeEnum::TUITION_FEE->slug());

        if ($feeType === null) {
            return redirect()
                ->route('portal.profile.financials')
                ->with('error', __('trans.tuition_fee_payment_unavailable'));
        }

        if ($examResultAccessService->clearanceStatus($student)['accountsCleared']) {
            return redirect()
                ->route('portal.profile.financials')
                ->with('error', __('trans.tuition_fee_accounts_cleared'));
        }

        $fees = $feeClearanceService->evaluate($student);

        if (! ($fees['isEnrolled'] ?? false)) {
            return redirect()
                ->route('portal.profile.financials')
                ->with('error', __('trans.student_not_enrolled_contact_it'));
        }

        if ((float) $fees['outstanding'] < 0.01 || ! ($fees['hasStudentNumber'] ?? false)) {
            return redirect()
                ->route('portal.profile.financials')
                ->with('error', __('trans.tuition_fee_nothing_outstanding'));
        }

        return Inertia::render('portal/student/TuitionFeePaymentOptions', [
            'student' => [
                'id' => $student->id,
                'name' => $student->user?->full_name,
                'studentNumber' => $student->student_number,
            ],
            'tuitionFee' => [
                'feeTypeId' => $feeType->id,
                'ledgerableId' => $studentApplication->id,
                'paymentAmount' => number_format((float) $fees['outstanding'], 2, '.', ''),
                'currencyCode' => PaymentCurrencyCodeEnum::Usd->value,
                'itemName' => $feeType->name,
            ],
            'feeSummary' => $fees,
            'returnTo' => request()->string('returnTo')->toString() ?: 'financials',
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function accommodationFeeCurrencySelection(
        StudentAccommodationFeeService $feeService,
        AccommodationPaymentQuoteService $quoteService,
    ): Response|RedirectResponse {
        $context = $this->resolveAccommodationPaymentOrRedirect($feeService);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        return Inertia::render('portal/hms/AccommodationFeeCurrencySelection', [
            'accommodationFee' => FeeStructureResource::make($context['feeStructure']),
            'fees' => $context['fees'],
            'hostelApplicationId' => $context['openApplication']->id,
            'quote' => $quoteService->previewForStudent($context['student'], $context['feeStructure']),
        ]);
    }

    /**
     * @throws AuthorizationException
     */
    public function accommodationFeePaymentOptions(
        StudentAccommodationFeeService $feeService,
        AccommodationPaymentQuoteService $quoteService,
    ): Response|RedirectResponse {
        $context = $this->resolveAccommodationPaymentOrRedirect($feeService);

        if ($context instanceof RedirectResponse) {
            return $context;
        }

        $currency = request()->query('currency');

        if (! is_string($currency) || PaymentCurrencyCodeEnum::tryFromSelection($currency) === null) {
            return redirect()->route('portal.profile.accommodations.pay.currency');
        }

        $quote = $quoteService->quoteForCurrency($context['student'], $currency, $context['feeStructure']);

        if ($quote === null) {
            return redirect()
                ->route('portal.profile.accommodations.pay.currency')
                ->with('error', __('students.accommodation_payment_currency_unavailable'));
        }

        return Inertia::render('portal/hms/AccommodationFeePaymentOptions', [
            'accommodationFee' => FeeStructureResource::make($context['feeStructure']),
            'fees' => $context['fees'],
            'hostelApplicationId' => $context['openApplication']->id,
            'selectedCurrency' => $quote['selectedCurrency'],
            'paymentAmount' => $quote['paymentAmount'],
            'currencyCode' => $quote['currencyCode'],
            'exchangeRate' => $quote['exchangeRate'],
        ]);
    }

    /**
     * @return array{
     *     student: Student,
     *     feeStructure: FeeStructure,
     *     openApplication: HostelApplication,
     *     fees: array<string, mixed>
     * }|RedirectResponse
     *
     * @throws AuthorizationException
     */
    private function resolveAccommodationPaymentOrRedirect(StudentAccommodationFeeService $feeService): array|RedirectResponse
    {
        $this->authorize('manageStudentAccommodationDetails');

        $student = $this->profileStudent();
        $openApplication = $feeService->openAwaitingPaymentApplication($student);

        if ($openApplication === null) {
            return redirect()
                ->route('portal.profile.accommodations')
                ->with('error', __('students.accommodation_payment_application_required'));
        }

        $feeStructure = $feeService->resolveFeeStructureForStudent($student, $openApplication);

        if ($feeStructure === null) {
            return redirect()
                ->route('portal.profile.accommodations')
                ->with('error', __('students.accommodation_fee_payment_unavailable'));
        }

        return [
            'student' => $student,
            'feeStructure' => $feeStructure,
            'openApplication' => $openApplication,
            'fees' => $feeService->summaryForStudent($student),
        ];
    }

    private function syncLedgerPaymentStatus(Ledger $invoice, ?Ledger $receipt, array $check): ?Ledger
    {
        $check = $this->normalizeGatewayCheck($check, $invoice, $receipt);
        $status = $this->gatewayPaymentStatus($check);
        $alreadyPaid = $this->ledgerIsPaid($invoice) || $this->ledgerIsPaid($receipt);

        if ($alreadyPaid && $status !== 'paid') {
            return $receipt;
        }

        if ($status === 'paid' && $receipt !== null) {
            $receipt = PaymentHelper::updateReceiptEntry(
                $receipt,
                PaymentHelper::assembleReceiptUpdateData($check),
            );
            $invoice->update(['payment_status' => 'paid']);
            PaymentHelper::deleteNotPaidLedgerEntries($invoice->system_reference);

            return $receipt;
        }

        if ($alreadyPaid) {
            return $receipt;
        }

        $invoice->update(['payment_status' => $status ?? 'pending']);
        $receipt?->update(['payment_status' => $status ?? 'pending']);

        return $receipt;
    }

    /**
     * @param  array<string, mixed>  $check
     * @return array<string, mixed>
     */
    private function normalizeGatewayCheck(array $check, Ledger $invoice, ?Ledger $receipt): array
    {
        $status = $this->gatewayPaymentStatus($check);

        if ($status !== null) {
            $check['status'] = $status;
        }

        $check['paymentOption'] ??= $receipt?->payment_option ?? 'card';
        $check['createdDate'] ??= now()->toDateString();
        $check['amount'] ??= $receipt?->amount ?? $invoice->amount ?? 0;
        $check['orderReference'] ??= $invoice->system_reference;
        $check['reference'] ??= $receipt?->payment_reference ?? $invoice->payment_reference;
        $check['currency'] ??= 'USD';
        $check['clientFee'] ??= 0;
        $check['merchantFee'] ??= 0;

        return $check;
    }

    private function gatewayPaymentStatus(array $check): ?string
    {
        $status = $check['status'] ?? $check['paymentStatus'] ?? null;

        if (! is_string($status) || $status === '') {
            return null;
        }

        return strtolower($status);
    }

    private function ledgerIsPaid(?Ledger $ledger): bool
    {
        return $ledger !== null && strtolower((string) $ledger->payment_status) === 'paid';
    }

    private function ledgerBelongsToLedgerable(?Ledger $ledger, Model $ledgerable): bool
    {
        if ($ledger === null) {
            return false;
        }

        return $ledger->ledgerable_type === $ledgerable::class
            && (int) $ledger->ledgerable_id === (int) $ledgerable->getKey();
    }

    private function resolveLedgerableForCurrentUserStatusCheck(
        Request $request,
        ?FeeType $feeType,
        ?FeeTypeEnum $feeTypeEnum,
    ): ?Model {
        $user = $request->user();

        if ($feeType === null || $feeTypeEnum === null || $user === null) {
            return null;
        }

        return match ($feeTypeEnum->ledgerableClass()) {
            ApplicationFee::class, HostelApplication::class, StudentApplication::class => $this->paymentContextResolver
                ->resolveForInitiate($request->merge(['feeTypeId' => $feeType->id]))
                ->ledgerable,
            default => $user,
        };
    }

    private function profileStudent(): Student
    {
        return request()->user()->studentProfile;
    }

    private function renderPaymentStatusPage(string $page, Ledger $ledger, bool $useFailureRoute = false): Response
    {
        $ledger->loadMissing('feeType');
        $feeTypeEnum = $ledger->feeType
            ? FeeTypeEnum::fromFeeType($ledger->feeType)
            : null;

        $redirectRoute = $useFailureRoute
            ? $this->paymentContextResolver->postFailurePaymentRouteForLedger($ledger)
            : $this->paymentContextResolver->postPaymentRouteForLedger($ledger);

        return Inertia::render($page, [
            'details' => LedgerResource::make($ledger),
            'redirectRoute' => $redirectRoute,
            'isApplicationFee' => $feeTypeEnum === FeeTypeEnum::APPLICATION_FEE,
            'isAccommodationFee' => $feeTypeEnum?->isAccommodationFee() ?? false,
        ]);
    }

    private function assertValidPaymentWebhook(Request $request): void
    {
        $apiKey = config('custom.payments.payment-gateway.api_key');
        $secret = config('custom.payments.payment-gateway.secret');

        if (empty($apiKey) && empty($secret)) {
            return;
        }

        if ($request->header('x-api-key') !== $apiKey || $request->header('x-api-secret') !== $secret) {
            abort(401, 'Unauthorized payment webhook');
        }
    }

    private function extractFilters(UpdateLedgerRequest $request): array
    {
        $amount = $request->filled('amount') ? $request->amount : null;
        $clientFee = $request->filled('clientFee') ? (float) $request->clientFee : 0.0;
        $createdDate = $request->filled('createdDate') ? $request->createdDate : null;
        $currency = $request->filled('currency') ? $request->currency : null;
        $merchantFee = $request->filled('merchantFee') ? (float) $request->merchantFee : 0.0;
        $orderReference = $request->filled('orderReference') ? $request->orderReference : null;
        $paymentReference = $request->filled('paymentReference') ? $request->paymentReference : null;
        $paymentStatus = $request->filled('paymentStatus') ? $request->paymentStatus : null;
        $paymentOption = $request->filled('paymentOption') ? $request->paymentOption : null;

        return [
            $amount, $clientFee, $createdDate, $currency, $merchantFee, $paymentOption,
            $orderReference, $paymentReference, $paymentStatus,
        ];
    }
}

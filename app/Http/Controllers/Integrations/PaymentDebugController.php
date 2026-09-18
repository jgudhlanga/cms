<?php

declare(strict_types=1);

namespace App\Http\Controllers\Integrations;

use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Integrations\SearchPaymentDebugRequest;
use App\Http\Requests\Integrations\UpdateLedgerRequest;
use App\Services\Integrations\LedgerEmailSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class PaymentDebugController extends Controller
{
    public function __construct(
        private readonly LedgerEmailSearchService $searchService,
        private readonly PaymentController $paymentController,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('integrations/payments-debug/Index', [
            'canUpdate' => (bool) $request->user()?->can('updatePaymentsDebug'),
        ]);
    }

    public function search(SearchPaymentDebugRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            return response()->json(
                $this->searchService->search(
                    $validated['q'],
                    $validated['feeType'] ?? null,
                ),
            );
        } catch (InvalidArgumentException) {
            return response()->json([
                'message' => __('integrations.payments_debug_not_found'),
            ], 404);
        }
    }

    public function check(string $order_reference, Request $request): array
    {
        $reference = $this->searchService->resolveLedgerForStatusCheck(
            $order_reference,
            $request->input('feeType') ?? $request->query('feeType'),
        );

        if ($reference === null || blank($reference->system_reference)) {
            return ['status' => 'not_found'];
        }

        return PaymentHelper::checkTransactionStatus($reference->system_reference);
    }

    public function update(UpdateLedgerRequest $request): void
    {
        $this->paymentController->updateLedgerRecords($request);
    }
}

<?php

namespace App\Observers\Ledgers;

use App\Models\Ledgers\Ledger;
use App\Services\HMS\HostelApplicationPaymentService;
use App\Services\Students\ApplicationFeePaymentService;

class LedgerObserver
{
    public function __construct(
        protected HostelApplicationPaymentService $hostelApplicationPaymentService,
        protected ApplicationFeePaymentService $applicationFeePaymentService,
    ) {}

    public function created(Ledger $ledger): void
    {
        $this->syncReceiptSideEffects($ledger);
    }

    public function updated(Ledger $ledger): void
    {
        if ($ledger->type !== 'receipt') {
            return;
        }

        if (! $ledger->wasChanged(['payment_status', 'amount'])) {
            return;
        }

        $this->syncReceiptSideEffects($ledger);
    }

    private function syncReceiptSideEffects(Ledger $ledger): void
    {
        if ($ledger->type !== 'receipt') {
            return;
        }

        $this->applicationFeePaymentService->syncStatusFromReceipt($ledger);

        if ($ledger->payment_status === 'paid') {
            $this->hostelApplicationPaymentService->syncStatusFromReceipt($ledger);
        }
    }
}

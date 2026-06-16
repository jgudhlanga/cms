<?php

namespace App\Observers\Ledgers;

use App\Models\Ledgers\Ledger;
use App\Services\HMS\HostelApplicationPaymentService;

class LedgerObserver
{
    public function __construct(
        protected HostelApplicationPaymentService $hostelApplicationPaymentService,
    ) {}

    public function created(Ledger $ledger): void
    {
        $this->syncPaidAccommodationReceipt($ledger);
    }

    public function updated(Ledger $ledger): void
    {
        if ($ledger->type !== 'receipt') {
            return;
        }

        if (! $ledger->wasChanged(['payment_status', 'amount'])) {
            return;
        }

        $this->syncPaidAccommodationReceipt($ledger);
    }

    private function syncPaidAccommodationReceipt(Ledger $ledger): void
    {
        if ($ledger->type !== 'receipt' || $ledger->payment_status !== 'paid') {
            return;
        }

        $this->hostelApplicationPaymentService->syncStatusFromReceipt($ledger);
    }
}

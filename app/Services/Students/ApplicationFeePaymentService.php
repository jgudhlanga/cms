<?php

namespace App\Services\Students;

use App\Enums\Shared\FeeTypeEnum;
use App\Enums\Students\ApplicationFeeStatusEnum;
use App\Models\Ledgers\Ledger;
use App\Models\Students\ApplicationFee;

class ApplicationFeePaymentService
{
    public function syncStatusFromReceipt(Ledger $receipt): void
    {
        if ($receipt->type !== 'receipt') {
            return;
        }

        $receipt->loadMissing(['ledgerable', 'feeType']);

        if (! $receipt->ledgerable instanceof ApplicationFee) {
            return;
        }

        $feeTypeEnum = $receipt->feeType
            ? FeeTypeEnum::fromFeeType($receipt->feeType)
            : null;

        if ($feeTypeEnum !== FeeTypeEnum::APPLICATION_FEE) {
            return;
        }

        $applicationFee = $receipt->ledgerable;

        if ($applicationFee->status === ApplicationFeeStatusEnum::SUBMITTED) {
            return;
        }

        $paymentStatus = strtolower((string) ($receipt->payment_status ?? ''));

        if ($paymentStatus === 'paid') {
            $applicationFee->update(['status' => ApplicationFeeStatusEnum::PAID]);

            return;
        }

        if (in_array($paymentStatus, ['failed', 'cancelled', 'pending'], true)) {
            if ($applicationFee->hasPaidReceipt()) {
                return;
            }

            $applicationFee->update(['status' => ApplicationFeeStatusEnum::AWAITING_PAYMENT]);
        }
    }
}

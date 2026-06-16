<?php

namespace App\Services\HMS;

use App\Enums\HMS\HostelApplicationStatusEnum;
use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\PaymentHelper;
use App\Models\HMS\HostelApplication;
use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Support\HMS\HostelApplicationPaymentVerification;

class HostelApplicationPaymentService
{
    public function __construct(
        protected StudentAccommodationFeeService $accommodationFeeService,
    ) {}

    public function syncStatusFromReceipt(Ledger $receipt): void
    {
        if ($receipt->type !== 'receipt' || $receipt->payment_status !== 'paid') {
            return;
        }

        $receipt->loadMissing(['ledgerable', 'feeType']);

        if (! $receipt->ledgerable instanceof HostelApplication) {
            return;
        }

        $feeTypeEnum = $receipt->feeType
            ? FeeTypeEnum::fromFeeType($receipt->feeType)
            : null;

        if ($feeTypeEnum === null || ! $feeTypeEnum->isAccommodationFee()) {
            return;
        }

        $application = $receipt->ledgerable;

        if (! in_array($application->status, [
            HostelApplicationStatusEnum::AWAITING_PAYMENT,
            HostelApplicationStatusEnum::PARTIALLY_PAID,
            HostelApplicationStatusEnum::PAID,
        ], true)) {
            return;
        }

        [$totalInvoiced, $totalPaid] = $this->paymentTotals($application, (int) $receipt->fee_type_id);

        if ($totalPaid <= 0.0) {
            return;
        }

        if ($totalPaid >= $totalInvoiced) {
            $this->applyStatus($application, HostelApplicationStatusEnum::PAID, true);

            return;
        }

        $this->applyStatus($application, HostelApplicationStatusEnum::PARTIALLY_PAID, false);
    }

    public function syncOpenApplicationForStudent(Student $student): void
    {
        $application = HostelApplication::query()
            ->where('student_id', $student->id)
            ->whereIn('status', [
                HostelApplicationStatusEnum::AWAITING_PAYMENT,
                HostelApplicationStatusEnum::PARTIALLY_PAID,
            ])
            ->latest()
            ->first();

        if ($application === null) {
            return;
        }

        if ($application->hasPaidAccommodationFee()) {
            $receipt = $application->ledgerTransactions()
                ->where('type', 'receipt')
                ->where('payment_status', 'paid')
                ->latest()
                ->first();

            if ($receipt !== null) {
                $this->syncStatusFromReceipt($receipt);
                $application->refresh();
            }

            if (in_array($application->status, [
                HostelApplicationStatusEnum::AWAITING_PAYMENT,
                HostelApplicationStatusEnum::PARTIALLY_PAID,
            ], true)) {
                $this->applyStatus($application, HostelApplicationStatusEnum::PAID, true);
            }

            return;
        }

        $student->loadMissing(['user']);

        if ($student->user !== null
            && PaymentHelper::hasPaidReceipt($student->user, FeeTypeEnum::STUDENT_ACCOMMODATION_FEE)) {
            $this->applyStatus($application, HostelApplicationStatusEnum::PAID, true);
        }
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function paymentTotals(HostelApplication $application, int $feeTypeId): array
    {
        $ledgers = $application->ledgerTransactions()
            ->where('fee_type_id', $feeTypeId)
            ->get();

        $totalInvoiced = (float) $ledgers
            ->where('type', 'invoice')
            ->sum(fn (Ledger $ledger) => (float) $ledger->amount);

        $totalPaid = (float) $ledgers
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->sum(fn (Ledger $ledger) => (float) $ledger->amount);

        if ($totalInvoiced <= 0.0) {
            $application->loadMissing('studentEnrolment.studentProgram');
            $feeStructure = $this->accommodationFeeService
                ->feeStructureForStudentProgram($application->studentEnrolment?->studentProgram);

            $totalInvoiced = (float) ($feeStructure?->local_fca_amount ?? 0);
        }

        return [$totalInvoiced, $totalPaid];
    }

    private function applyStatus(
        HostelApplication $application,
        HostelApplicationStatusEnum $status,
        bool $accommodationFeesPaidConfirmed,
    ): void {
        if ($application->status === $status
            && (bool) ($application->payment_verification[HostelApplicationPaymentVerification::KEY_ACCOMMODATION_FEES_PAID] ?? false) === $accommodationFeesPaidConfirmed) {
            return;
        }

        $application->update([
            'status' => $status,
            'payment_verification' => HostelApplicationPaymentVerification::merge(
                $application->payment_verification,
                ['accommodationFeesPaidConfirmed' => $accommodationFeesPaidConfirmed],
            ),
        ]);
    }
}

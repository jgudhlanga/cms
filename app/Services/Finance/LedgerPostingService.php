<?php

namespace App\Services\Finance;

use App\Enums\Finance\LedgerTransactionType;
use App\Models\Finance\Account;
use App\Models\Finance\Invoice;
use App\Models\Finance\Journal;
use App\Models\Finance\LedgerEntry;
use App\Models\Finance\Receipt;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LedgerPostingService
{
    public const ACCOUNT_AR = 'AR';

    public const ACCOUNT_REVENUE_TUITION = 'TUITION_REVENUE';

    public const ACCOUNT_BANK = 'BANK';

    public function postInvoiceRecognized(
        Invoice $invoice,
        string $accountsReceivableCode = self::ACCOUNT_AR,
        string $revenueAccountCode = self::ACCOUNT_REVENUE_TUITION,
        ?string $description = null,
    ): void {
        $this->assertAccountsExist($invoice->tenant_id, [$accountsReceivableCode, $revenueAccountCode]);
        $amount = $this->normalizeMoneyString($invoice->amount);
        $lines = [
            [
                'account_code' => $accountsReceivableCode,
                'debit' => $amount,
                'credit' => '0.00',
                'description' => $description,
            ],
            [
                'account_code' => $revenueAccountCode,
                'debit' => '0.00',
                'credit' => $amount,
                'description' => $description,
            ],
        ];
        $this->insertBalancedLines(
            $invoice->tenant_id,
            LedgerTransactionType::Invoice,
            $invoice,
            $invoice->student_id,
            null,
            $invoice->due_date,
            $lines
        );
    }

    public function postReceiptAgainstAr(
        Receipt $receipt,
        string $bankAccountCode = self::ACCOUNT_BANK,
        string $accountsReceivableCode = self::ACCOUNT_AR,
        ?string $description = null,
    ): void {
        $this->assertAccountsExist($receipt->tenant_id, [$bankAccountCode, $accountsReceivableCode]);
        $amount = $this->normalizeMoneyString($receipt->amount);
        $lines = [
            [
                'account_code' => $bankAccountCode,
                'debit' => $amount,
                'credit' => '0.00',
                'description' => $description,
            ],
            [
                'account_code' => $accountsReceivableCode,
                'debit' => '0.00',
                'credit' => $amount,
                'description' => $description,
            ],
        ];
        $this->insertBalancedLines(
            $receipt->tenant_id,
            LedgerTransactionType::Receipt,
            $receipt,
            $receipt->student_id,
            $receipt->user_id,
            $receipt->payment_date,
            $lines
        );
    }

    /**
     * @param  array<int, array{account_code: string, debit: string|float|int, credit: string|float|int, description?: ?string, student_id?: ?int, user_id?: ?int}>  $lines
     */
    public function postJournalAdjustment(Journal $journal, array $lines): void
    {
        $codes = array_unique(array_column($lines, 'account_code'));
        $this->assertAccountsExist($journal->tenant_id, $codes);
        $this->insertBalancedLines(
            $journal->tenant_id,
            LedgerTransactionType::Adjustment,
            $journal,
            null,
            null,
            $journal->journal_date,
            $lines
        );
    }

    /**
     * @param  array<int, array{account_code: string, debit: string|float|int, credit: string|float|int, description?: ?string, student_id?: ?int, user_id?: ?int}>  $lines
     */
    private function insertBalancedLines(
        int $tenantId,
        LedgerTransactionType $transactionType,
        Model $reference,
        ?int $defaultStudentId,
        ?int $defaultUserId,
        CarbonInterface|string $transactionDate,
        array $lines,
    ): void {
        $this->assertBalanced($lines);
        DB::transaction(function () use ($tenantId, $transactionType, $reference, $defaultStudentId, $defaultUserId, $transactionDate, $lines): void {
            foreach ($lines as $line) {
                LedgerEntry::query()->create([
                    'tenant_id' => $tenantId,
                    'student_id' => $line['student_id'] ?? $defaultStudentId,
                    'user_id' => $line['user_id'] ?? $defaultUserId,
                    'transaction_type' => $transactionType,
                    'reference_type' => $reference->getMorphClass(),
                    'reference_id' => $reference->getKey(),
                    'account_code' => $line['account_code'],
                    'debit' => $this->normalizeMoneyString($line['debit']),
                    'credit' => $this->normalizeMoneyString($line['credit']),
                    'transaction_date' => $transactionDate,
                    'description' => $line['description'] ?? null,
                ]);
            }
        });
    }

    /**
     * @param  array<int, array{account_code: string, debit: string|float|int, credit: string|float|int, description?: ?string, student_id?: ?int, user_id?: ?int}>  $lines
     */
    private function assertBalanced(array $lines): void
    {
        $debit = '0.00';
        $credit = '0.00';
        foreach ($lines as $line) {
            $debit = bcadd($debit, $this->normalizeMoneyString($line['debit']), 2);
            $credit = bcadd($credit, $this->normalizeMoneyString($line['credit']), 2);
        }
        if (bccomp($debit, $credit, 2) !== 0) {
            throw new InvalidArgumentException(
                sprintf('Ledger lines are not balanced: debit %s != credit %s', $debit, $credit)
            );
        }
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function assertAccountsExist(int $tenantId, array $codes): void
    {
        foreach ($codes as $code) {
            $exists = Account::query()
                ->where('tenant_id', $tenantId)
                ->where('code', $code)
                ->exists();
            if (! $exists) {
                throw new InvalidArgumentException(
                    sprintf('Finance account code "%s" is not defined for this tenant.', $code)
                );
            }
        }
    }

    private function normalizeMoneyString(string|float|int $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}

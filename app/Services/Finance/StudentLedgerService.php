<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class StudentLedgerService
{
    /**
     * @return array{
     *     entries: Collection<int, ZBBankStatement>,
     *     summary: array{
     *         totalInvoiced: string,
     *         totalPayments: string,
     *         outstandingBalance: string,
     *         paidPercent: float
     *     }
     * }
     */
    public function build(Student $student): array
    {
        $statements = $this->studentStatementQuery($student)
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $runningBalance = 0.0;
        $totalInvoiced = 0.0;
        $totalPayments = 0.0;

        foreach ($statements as $statement) {
            $debit = (float) ($statement->amountDebitInUsd() ?? $statement->amount_debit ?? 0);
            $credit = (float) ($statement->amountCreditInUsd() ?? $statement->amount_credit ?? 0);

            $totalInvoiced += $debit;
            $totalPayments += $credit;
            $runningBalance += $debit - $credit;

            $statement->setAttribute(
                'computed_running_balance',
                number_format($runningBalance, 2, '.', '')
            );
        }

        $outstandingBalance = $totalInvoiced - $totalPayments;
        $paidPercent = $totalInvoiced > 0
            ? round(($totalPayments / $totalInvoiced) * 100, 1)
            : 0.0;

        return [
            'entries' => $statements,
            'summary' => [
                'totalInvoiced' => number_format($totalInvoiced, 2, '.', ''),
                'totalPayments' => number_format($totalPayments, 2, '.', ''),
                'outstandingBalance' => number_format($outstandingBalance, 2, '.', ''),
                'paidPercent' => $paidPercent,
            ],
        ];
    }

    public function hasRecordedPayments(Student $student): bool
    {
        return (float) $this->build($student)['summary']['totalPayments'] > 0;
    }

    private function studentStatementQuery(Student $student): Builder
    {
        $studentStatementMatchPatterns = StudentBankStatementMatchPatterns::forStudent($student);
        $exactLikePatterns = $studentStatementMatchPatterns['exactLikePatterns'];

        if ($exactLikePatterns === []) {
            return ZBBankStatement::query()->where('id', 0);
        }

        return ZBBankStatement::query()->where(function (Builder $statementQuery) use ($exactLikePatterns): void {
            foreach ($exactLikePatterns as $pattern) {
                $statementQuery->orWhere(function (Builder $fieldQuery) use ($pattern): void {
                    $fieldQuery
                        ->where('narration', 'like', $pattern)
                        ->orWhere('pipe5_details', 'like', $pattern)
                        ->orWhere('pipe10_details', 'like', $pattern)
                        ->orWhere('transaction_details', 'like', $pattern);
                });
            }
        });
    }
}

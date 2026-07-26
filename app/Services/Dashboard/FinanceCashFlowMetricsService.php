<?php

namespace App\Services\Dashboard;

use App\Enums\Finance\FinanceTransactionQueryStatusEnum;
use App\Models\Finance\FinanceTransactionQuery;
use Illuminate\Support\Collection;

class FinanceCashFlowMetricsService
{
    /**
     * @return array{
     *     summary: array{todayTotal: float, todayCount: int, reconciledToday: int},
     *     byDepartment: list<array{departmentId: int, departmentName: string, amount: float, transactionCount: int}>
     * }
     */
    public function build(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $queries = FinanceTransactionQuery::query()
            ->where('status', FinanceTransactionQueryStatusEnum::RECONCILED)
            ->whereBetween('reconciled_at', [$todayStart, $todayEnd])
            ->with([
                'bankStatement',
                'student.latestEnrolment.institutionDepartment.department',
            ])
            ->get();

        if ($queries->isEmpty()) {
            return $this->emptyPayload();
        }

        $byDepartment = $this->groupByDepartment($queries);
        $todayTotal = round((float) collect($byDepartment)->sum('amount'), 2);
        $todayCount = $queries->count();

        return [
            'summary' => [
                'todayTotal' => $todayTotal,
                'todayCount' => $todayCount,
                'reconciledToday' => $todayCount,
            ],
            'byDepartment' => $byDepartment,
        ];
    }

    /**
     * @param  Collection<int, FinanceTransactionQuery>  $queries
     * @return list<array{departmentId: int, departmentName: string, amount: float, transactionCount: int}>
     */
    private function groupByDepartment(Collection $queries): array
    {
        $grouped = [];

        foreach ($queries as $query) {
            $enrolment = $query->student?->latestEnrolment;
            $department = $enrolment?->institutionDepartment?->department;
            $departmentId = (int) ($enrolment?->institution_department_id ?? 0);
            $departmentName = (string) ($department?->name ?? __('dashboard.finance_unknown_department'));
            $amount = $this->resolveAmount($query);

            if (! isset($grouped[$departmentId])) {
                $grouped[$departmentId] = [
                    'departmentId' => $departmentId,
                    'departmentName' => $departmentName,
                    'amount' => 0.0,
                    'transactionCount' => 0,
                ];
            }

            $grouped[$departmentId]['amount'] = round($grouped[$departmentId]['amount'] + $amount, 2);
            $grouped[$departmentId]['transactionCount']++;
        }

        $rows = array_values($grouped);
        usort($rows, fn (array $left, array $right): int => $right['amount'] <=> $left['amount']);

        return $rows;
    }

    private function resolveAmount(FinanceTransactionQuery $query): float
    {
        $statement = $query->bankStatement;

        if ($statement === null) {
            return 0.0;
        }

        $usd = $statement->amountCreditInUsd();

        if ($usd !== null && is_numeric($usd)) {
            return (float) $usd;
        }

        return (float) ($statement->amount_credit ?? 0);
    }

    /**
     * @return array{
     *     summary: array{todayTotal: float, todayCount: int, reconciledToday: int},
     *     byDepartment: list<array{departmentId: int, departmentName: string, amount: float, transactionCount: int}>
     * }
     */
    private function emptyPayload(): array
    {
        return [
            'summary' => [
                'todayTotal' => 0.0,
                'todayCount' => 0,
                'reconciledToday' => 0,
            ],
            'byDepartment' => [],
        ];
    }
}

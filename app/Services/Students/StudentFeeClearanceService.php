<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Shared\FeeTypeEnum;
use App\Helpers\DepartmentHelper;
use App\Models\Institution\FeeStructure;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Ledgers\Ledger;
use App\Models\Shared\FeeType;
use App\Models\Students\Student;
use App\Models\Students\StudentEnrolment;
use App\Services\Finance\StudentBankStatementMatchPatterns;
use Illuminate\Database\Eloquent\Builder;

class StudentFeeClearanceService
{
    /**
     * @return array{
     *     tuition: float,
     *     autoCardFee: float,
     *     partTimeLevy: float,
     *     expectedTotal: float,
     *     paidFromBank: float,
     *     paidFromLedger: float,
     *     paidTotal: float,
     *     outstanding: float,
     *     isFullyPaid: bool,
     *     breakdown: list<array{key: string, label: string, amount: float}>,
     *     hasStudentNumber: bool,
     *     isEnrolled: bool,
     *     source: string|null,
     *     currency: string,
     *     bankConversions: list<array{
     *         originalAmount: float,
     *         originalCurrency: string,
     *         usdAmount: float,
     *         rate: string,
     *         label: string,
     *         date: string
     *     }>
     * }
     */
    public function evaluate(Student $student): array
    {
        $context = $this->resolveFeeContext($student);
        $tuition = $context['tuition'];
        $autoCardFee = $context['autoCardFee'];
        $partTimeLevy = $context['partTimeLevy'];
        $expectedTotal = round($tuition + $autoCardFee + $partTimeLevy, 2);
        $bankPayment = $this->bankCreditsWithConversions($student);
        $paidFromBank = $bankPayment['paidFromBank'];
        $paidFromLedger = $this->sumTuitionLedgerReceipts($student);
        $paidTotal = round($paidFromBank + $paidFromLedger, 2);
        $outstanding = round(max(0, $expectedTotal - $paidTotal), 2);
        $breakdown = $this->buildBreakdown($tuition, $autoCardFee, $partTimeLevy);

        $hasStudentNumber = trim((string) $student->student_number) !== '';
        $isEnrolled = $context['source'] === 'enrolment';

        return [
            'tuition' => $tuition,
            'autoCardFee' => $autoCardFee,
            'partTimeLevy' => $partTimeLevy,
            'expectedTotal' => $expectedTotal,
            'paidFromBank' => $paidFromBank,
            'paidFromLedger' => $paidFromLedger,
            'paidTotal' => $paidTotal,
            'outstanding' => $outstanding,
            'isFullyPaid' => $hasStudentNumber && $expectedTotal > 0 && $paidTotal >= $expectedTotal,
            'breakdown' => $breakdown,
            'hasStudentNumber' => $hasStudentNumber,
            'isEnrolled' => $isEnrolled,
            'source' => $context['source'],
            'currency' => 'USD',
            'bankConversions' => $bankPayment['bankConversions'],
        ];
    }

    /**
     * @return list<array{key: string, label: string, amount: float}>
     */
    public function expectedBreakdown(Student $student): array
    {
        $context = $this->resolveFeeContext($student);

        return $this->buildBreakdown(
            $context['tuition'],
            $context['autoCardFee'],
            $context['partTimeLevy'],
        );
    }

    /**
     * @return list<array{key: string, label: string, amount: float}>
     */
    private function buildBreakdown(float $tuition, float $autoCardFee, float $partTimeLevy): array
    {
        $breakdown = [
            [
                'key' => 'tuition',
                'label' => __('finance.tuition'),
                'amount' => $tuition,
            ],
        ];

        if ($autoCardFee > 0) {
            $breakdown[] = [
                'key' => 'auto_card',
                'label' => __('finance.autocard_fee'),
                'amount' => $autoCardFee,
            ];
        }

        if ($partTimeLevy > 0) {
            $breakdown[] = [
                'key' => 'part_time_levy',
                'label' => __('finance.part_time_levy'),
                'amount' => $partTimeLevy,
            ];
        }

        return $breakdown;
    }

    /**
     * @return array{tuition: float, autoCardFee: float, partTimeLevy: float, source: string|null}
     */
    private function resolveFeeContext(Student $student): array
    {
        $enrolment = $student->latestEnrolment()
            ->with([
                'institutionDepartment.department',
                'departmentLevel.level',
                'modeOfStudy',
            ])
            ->first();

        if ($enrolment instanceof StudentEnrolment) {
            return $this->amountsFromEnrolment($student, $enrolment);
        }

        return [
            'tuition' => 0.0,
            'autoCardFee' => 0.0,
            'partTimeLevy' => 0.0,
            'source' => null,
        ];
    }

    /**
     * @return array{tuition: float, autoCardFee: float, partTimeLevy: float, source: string}
     */
    private function amountsFromEnrolment(Student $student, StudentEnrolment $enrolment): array
    {
        $levelId = $enrolment->departmentLevel?->level?->id;
        $modeOfStudyId = $enrolment->mode_of_study_id;
        $departmentName = $enrolment->institutionDepartment?->department?->name ?? '';
        $modeName = $enrolment->modeOfStudy?->name ?? '';

        return [
            'tuition' => $this->resolveTuition((int) $student->tenant_id, $levelId, $modeOfStudyId),
            'autoCardFee' => (float) (DepartmentHelper::requiredAutoCardFee($departmentName) ?? 0),
            'partTimeLevy' => (float) (DepartmentHelper::partTimeLevy($modeName) ?? 0),
            'source' => 'enrolment',
        ];
    }

    private function resolveTuition(int $tenantId, mixed $levelId, mixed $modeOfStudyId): float
    {
        if ($levelId === null || $modeOfStudyId === null) {
            return 0.0;
        }

        $tuitionFeeType = FeeType::query()
            ->where('name', FeeTypeEnum::TUITION_FEE->name())
            ->first();

        if ($tuitionFeeType === null) {
            return 0.0;
        }

        $feeStructure = FeeStructure::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('level_id', $levelId)
            ->where('mode_of_study_id', $modeOfStudyId)
            ->where('fee_type_id', $tuitionFeeType->id)
            ->first();

        return (float) ($feeStructure->local_fca_amount ?? 0);
    }

    public function sumBankCredits(Student $student): float
    {
        return $this->bankCreditsWithConversions($student)['paidFromBank'];
    }

    /**
     * @return array{
     *     paidFromBank: float,
     *     bankConversions: list<array{
     *         originalAmount: float,
     *         originalCurrency: string,
     *         usdAmount: float,
     *         rate: string,
     *         label: string,
     *         date: string
     *     }>
     * }
     */
    public function bankCreditsWithConversions(Student $student): array
    {
        $patterns = StudentBankStatementMatchPatterns::forStudent($student)['exactLikePatterns'];

        if ($patterns === []) {
            return [
                'paidFromBank' => 0.0,
                'bankConversions' => [],
            ];
        }

        $statements = $this->creditQuery($patterns)->get();
        $total = 0.0;
        /** @var array<string, array{originalAmount: float, originalCurrency: string, usdAmount: float, rate: string, label: string, date: string}> $conversionsByKey */
        $conversionsByKey = [];

        foreach ($statements as $statement) {
            /** @var ZBBankStatement $statement */
            $usdAmount = $statement->amountCreditInUsd();
            $creditUsd = (float) ($usdAmount ?? $statement->amount_credit ?? 0);
            $total += $creditUsd;

            $metadata = $statement->usdConversionRateMetadata();

            if ($metadata === null || $usdAmount === null || ! $statement->hasZwgCurrencyCode()) {
                continue;
            }

            $key = $metadata['rate'].'|'.$metadata['date'];
            $originalAmount = round((float) ($statement->amount_credit ?? 0), 2);
            $convertedUsd = round((float) $usdAmount, 2);

            if (isset($conversionsByKey[$key])) {
                $conversionsByKey[$key]['originalAmount'] = round(
                    $conversionsByKey[$key]['originalAmount'] + $originalAmount,
                    2
                );
                $conversionsByKey[$key]['usdAmount'] = round(
                    $conversionsByKey[$key]['usdAmount'] + $convertedUsd,
                    2
                );

                continue;
            }

            $conversionsByKey[$key] = [
                'originalAmount' => $originalAmount,
                'originalCurrency' => 'ZWG',
                'usdAmount' => $convertedUsd,
                'rate' => $metadata['rate'],
                'label' => $metadata['label'],
                'date' => $metadata['date'],
            ];
        }

        return [
            'paidFromBank' => round($total, 2),
            'bankConversions' => array_values($conversionsByKey),
        ];
    }

    public function sumTuitionLedgerReceipts(Student $student): float
    {
        $tuitionFeeTypeId = FeeType::query()
            ->where('slug', FeeTypeEnum::TUITION_FEE->slug())
            ->value('id');

        if ($tuitionFeeTypeId === null) {
            return 0.0;
        }

        $applicationIds = $student->applications()->pluck('id');

        if ($applicationIds->isEmpty()) {
            return 0.0;
        }

        $total = Ledger::query()
            ->where('fee_type_id', $tuitionFeeTypeId)
            ->where('type', 'receipt')
            ->where('payment_status', 'paid')
            ->where(function (Builder $query) use ($applicationIds): void {
                $query->whereIn('student_application_id', $applicationIds)
                    ->orWhere(function (Builder $ledgerableQuery) use ($applicationIds): void {
                        $ledgerableQuery
                            ->where('ledgerable_type', StudentApplication::class)
                            ->whereIn('ledgerable_id', $applicationIds);
                    });
            })
            ->sum('amount');

        return round((float) $total, 2);
    }

    /**
     * @param  list<string>  $exactLikePatterns
     */
    private function creditQuery(array $exactLikePatterns): Builder
    {
        return ZBBankStatement::query()
            ->where('debit_credit_flag', 'C')
            ->where(function (Builder $statementQuery) use ($exactLikePatterns): void {
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

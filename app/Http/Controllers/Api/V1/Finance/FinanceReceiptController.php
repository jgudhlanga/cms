<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\StudentPaymentReceiptResource;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use App\Services\Finance\StudentBankStatementMatchPatterns;
use App\Services\Finance\StudentLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FinanceReceiptController extends Controller
{
    public function __construct(
        private readonly StudentLedgerService $studentLedgerService,
    ) {}

    public function getStudentReceipts(Student $student): AnonymousResourceCollection
    {
        $query = $this->studentStatementQuery($student)->where('debit_credit_flag', 'C');
        $query->orderByDesc('transaction_date');

        return StudentPaymentReceiptResource::collection($query->paginate());
    }

    public function getStudentLedger(Student $student): AnonymousResourceCollection
    {
        $ledger = $this->studentLedgerService->build($student);

        return StudentPaymentReceiptResource::collection($ledger['entries'])->additional([
            'summary' => $ledger['summary'],
        ]);
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

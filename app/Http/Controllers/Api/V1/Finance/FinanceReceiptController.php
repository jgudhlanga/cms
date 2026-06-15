<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\StudentPaymentReceiptResource;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Services\Finance\StudentBankStatementMatchPatterns;
use App\Services\Finance\StudentLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FinanceReceiptController extends Controller
{
    public function __construct(
        private readonly StudentLedgerService $studentLedgerService,
    ) {}

    public function getStudentReceipts(Student $student): AnonymousResourceCollection
    {
        $this->authorizeStudentFinanceAccess($student);

        $query = $this->studentStatementQuery($student)->where('debit_credit_flag', 'C');
        $query->orderByDesc('transaction_date');

        return StudentPaymentReceiptResource::collection($query->paginate());
    }

    public function getStudentLedger(Student $student): AnonymousResourceCollection
    {
        $this->authorizeStudentFinanceAccess($student);

        $ledger = $this->studentLedgerService->build($student);

        return StudentPaymentReceiptResource::collection($ledger['entries'])->additional([
            'summary' => $ledger['summary'],
        ]);
    }

    private function authorizeStudentFinanceAccess(Student $student): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        abort_if($user === null, Response::HTTP_UNAUTHORIZED);

        $isOwnStudent = $student->exists
            && ($user->studentProfile?->id === $student->id || $user->id === $student->user_id);

        if ($isOwnStudent) {
            abort_unless($user->can('manageOwnStudentFinancialDetails:students'), Response::HTTP_FORBIDDEN);

            return;
        }

        abort_unless(
            $user->can('root:manage')
            || $user->can('view:finances')
            || $user->can('viewAny:finances')
            || $user->can('update:finances'),
            Response::HTTP_FORBIDDEN
        );
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

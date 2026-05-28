<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\Finance\FinanceTransactionQueryStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\DeclineFinanceTransactionQueryRequest;
use App\Http\Requests\Finance\ReconcileFinanceTransactionQueryRequest;
use App\Http\Requests\Finance\StoreFinanceTransactionQueryRequest;
use App\Http\Resources\Finance\FinanceTransactionQueryResource;
use App\Models\Finance\FinanceTransactionQuery;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Services\Finance\FinanceTransactionQueryReconciliationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FinanceTransactionQueryController extends Controller
{
    public function __construct(private readonly FinanceTransactionQueryReconciliationService $reconciliationService)
    {
    }

    public function indexForStudent(Request $request, Student $student): AnonymousResourceCollection
    {
        $user = $this->requireUser($request);
        $this->authorizeStudentContext($user, $student);

        $queries = FinanceTransactionQuery::query()
            ->with('student.user')
            ->where('student_id', $student->id)
            ->latest('id')
            ->paginate();

        return FinanceTransactionQueryResource::collection($queries);
    }

    public function storeForStudent(StoreFinanceTransactionQueryRequest $request, Student $student): FinanceTransactionQueryResource
    {
        $user = $this->requireUser($request);
        $this->authorizeStudentContext($user, $student);

        $transactionQuery = FinanceTransactionQuery::query()->create([
            'student_id' => $student->id,
            'payment_reference' => (string) $request->string('payment_reference'),
            'description' => $request->input('description'),
            'status' => FinanceTransactionQueryStatusEnum::SUBMITTED,
        ]);

        if ($request->hasFile('proof_of_payment')) {
            $transactionQuery
                ->addMediaFromRequest('proof_of_payment')
                ->toMediaCollection('financial-documents');
        }

        return FinanceTransactionQueryResource::make(
            $transactionQuery->load('student.user')
        );
    }

    public function indexForReconciliation(Request $request): AnonymousResourceCollection
    {
        $user = $this->requireUser($request);
        $this->authorizeFinanceUser($user);

        $search = trim((string) $request->query('search', ''));
        $student = trim((string) $request->query('student', ''));
        $reference = trim((string) $request->query('reference', ''));
        $status = trim((string) $request->query('status', ''));
        $pageSize = max(1, (int) $request->query('page_size', 15));

        $queryBuilder = FinanceTransactionQuery::query()
            ->with(['student.user', 'reconciler', 'decliner'])
            ->latest('id');

        if ($search !== '') {
            $likeSearch = '%'.addcslashes($search, '\%_').'%';

            $queryBuilder->where(function ($query) use ($likeSearch): void {
                $query
                    ->whereHas('student.user', function ($userQuery) use ($likeSearch): void {
                        $userQuery
                            ->where('first_name', 'like', $likeSearch)
                            ->orWhere('middle_name', 'like', $likeSearch)
                            ->orWhere('last_name', 'like', $likeSearch);
                    })
                    ->orWhere('payment_reference', 'like', $likeSearch);
            });
        }

        if ($student !== '') {
            $likeStudent = '%'.addcslashes($student, '\%_').'%';

            $queryBuilder->whereHas('student.user', function ($userQuery) use ($likeStudent): void {
                $userQuery
                    ->where('first_name', 'like', $likeStudent)
                    ->orWhere('middle_name', 'like', $likeStudent)
                    ->orWhere('last_name', 'like', $likeStudent);
            });
        }

        if ($reference !== '') {
            $queryBuilder->where('payment_reference', 'like', '%'.addcslashes($reference, '\%_').'%');
        }

        if ($status !== '') {
            $queryBuilder->where('status', $status);
        }

        return FinanceTransactionQueryResource::collection($queryBuilder->paginate($pageSize));
    }

    public function markUnderReview(Request $request, FinanceTransactionQuery $transactionQuery): FinanceTransactionQueryResource
    {
        $user = $this->requireUser($request);
        $this->authorizeFinanceUser($user);

        return FinanceTransactionQueryResource::make(
            $this->reconciliationService->markUnderReview($transactionQuery->load('student.user'))
        );
    }

    public function markNeedsInfo(Request $request, FinanceTransactionQuery $transactionQuery): FinanceTransactionQueryResource
    {
        $user = $this->requireUser($request);
        $this->authorizeFinanceUser($user);

        return FinanceTransactionQueryResource::make(
            $this->reconciliationService->markNeedsInfo($transactionQuery->load('student.user'))
        );
    }

    public function previewMatch(Request $request, FinanceTransactionQuery $transactionQuery): JsonResponse
    {
        $user = $this->requireUser($request);
        $this->authorizeFinanceUser($user);

        $statement = $this->reconciliationService->findStatementCandidateByReference($transactionQuery);

        return response()->json([
            'data' => $statement ? [
                'id' => $statement->id,
                'transactionDate' => (string) $statement->transaction_date,
                'reference' => (string) $statement->reference,
                'narration' => (string) $statement->narration,
                'pipe5Details' => (string) $statement->pipe5_details,
                'amountCredit' => (string) ($statement->amount_credit ?? ''),
                'amountDebit' => (string) ($statement->amount_debit ?? ''),
                'isoCurrencyCode' => (string) $statement->iso_currency_code,
            ] : null,
        ]);
    }

    public function reconcile(
        ReconcileFinanceTransactionQueryRequest $request,
        FinanceTransactionQuery $transactionQuery
    ): FinanceTransactionQueryResource {
        $user = $this->requireUser($request);
        $this->authorizeFinanceUser($user);

        return FinanceTransactionQueryResource::make(
            $this->reconciliationService->reconcile(
                $transactionQuery->load('student.user'),
                $user,
                $request->integer('bank_statement_id') ?: null
            )
        );
    }

    public function decline(
        DeclineFinanceTransactionQueryRequest $request,
        FinanceTransactionQuery $transactionQuery
    ): FinanceTransactionQueryResource {
        $user = $this->requireUser($request);
        $this->authorizeFinanceUser($user);

        return FinanceTransactionQueryResource::make(
            $this->reconciliationService->decline(
                $transactionQuery->load('student.user'),
                $user,
                (string) $request->string('reason')
            )
        );
    }

    private function authorizeStudentContext(User $user, Student $student): void
    {
        $isOwnStudentRecord = $user->studentProfile?->id === $student->id || $user->id === $student->user_id;

        if ($isOwnStudentRecord) {

            return;
        }

        $this->authorizeFinanceUser($user);
    }

    private function authorizeFinanceUser(User $user): void
    {
        abort_unless(
            $user->can('root:manage')
            || $user->can('view:finances')
            || $user->can('viewAny:finances')
            || $user->can('update:finances'),
            Response::HTTP_FORBIDDEN
        );
    }

    private function requireUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if($user === null, Response::HTTP_UNAUTHORIZED);

        return $user;
    }
}

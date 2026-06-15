<?php

namespace App\Services\Finance;

use App\Enums\Finance\FinanceTransactionQueryStatusEnum;
use App\Models\Finance\FinanceTransactionQuery;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;
use App\Models\Users\User;
use App\Notifications\Finance\FinanceTransactionQueryStatusUpdated;
use Illuminate\Support\Facades\DB;

class FinanceTransactionQueryReconciliationService
{
    public function findStatementCandidateByReference(FinanceTransactionQuery $transactionQuery): ?ZBBankStatement
    {
        return ZBBankStatement::query()
            ->where(function ($query) use ($transactionQuery): void {
                $query->where('reference', $transactionQuery->payment_reference)
                    ->orWhere('narration', 'like', '%'.addcslashes($transactionQuery->payment_reference, '\%_').'%');
            })
            ->latest('id')
            ->first();
    }

    public function markNeedsInfo(FinanceTransactionQuery $transactionQuery): FinanceTransactionQuery
    {
        $this->assertStatusTransitionAllowed($transactionQuery, [
            FinanceTransactionQueryStatusEnum::SUBMITTED,
            FinanceTransactionQueryStatusEnum::UNDER_REVIEW,
        ]);

        $transactionQuery->update([
            'status' => FinanceTransactionQueryStatusEnum::NEEDS_INFO,
        ]);

        $this->notifyStudent($transactionQuery);

        return $transactionQuery->refresh();
    }

    public function markUnderReview(FinanceTransactionQuery $transactionQuery): FinanceTransactionQuery
    {
        $this->assertStatusTransitionAllowed($transactionQuery, [
            FinanceTransactionQueryStatusEnum::SUBMITTED,
            FinanceTransactionQueryStatusEnum::NEEDS_INFO,
        ]);

        $transactionQuery->update([
            'status' => FinanceTransactionQueryStatusEnum::UNDER_REVIEW,
        ]);

        $this->notifyStudent($transactionQuery);

        return $transactionQuery->refresh();
    }

    public function decline(FinanceTransactionQuery $transactionQuery, User $actor, string $reason): FinanceTransactionQuery
    {
        $this->assertStatusTransitionAllowed($transactionQuery, [
            FinanceTransactionQueryStatusEnum::SUBMITTED,
            FinanceTransactionQueryStatusEnum::NEEDS_INFO,
            FinanceTransactionQueryStatusEnum::UNDER_REVIEW,
        ]);

        $transactionQuery->update([
            'status' => FinanceTransactionQueryStatusEnum::DECLINED,
            'declined_by' => $actor->id,
            'declined_at' => now(),
            'decline_reason' => $reason,
        ]);

        $this->notifyStudent($transactionQuery);

        return $transactionQuery->refresh();
    }

    public function reconcile(FinanceTransactionQuery $transactionQuery, User $actor, ?int $bankStatementId = null): FinanceTransactionQuery
    {
        return DB::transaction(function () use ($transactionQuery, $actor, $bankStatementId): FinanceTransactionQuery {
            $this->assertStatusTransitionAllowed($transactionQuery, [
                FinanceTransactionQueryStatusEnum::SUBMITTED,
                FinanceTransactionQueryStatusEnum::NEEDS_INFO,
                FinanceTransactionQueryStatusEnum::UNDER_REVIEW,
            ]);

            $statement = $this->resolveStatement($transactionQuery, $bankStatementId);

            $this->assertCanReconcile($transactionQuery, $statement);

            $studentNumber = trim((string) (DB::table('students')
                ->where('id', $transactionQuery->student_id)
                ->selectRaw("COALESCE(student_number, student_number_generated, '') as student_number_match")
                ->value('student_number_match')
                ?? ''));
            $narration = trim((string) $statement->narration);
            $pipe5Details = trim((string) $statement->pipe5_details);

            if ($studentNumber !== '') {
                $updatedNarration = str_contains($narration, $studentNumber)
                    ? $narration
                    : trim($narration.' '.$studentNumber);
                $updatedPipe5Details = str_contains($pipe5Details, $studentNumber)
                    ? $pipe5Details
                    : trim($pipe5Details.' '.$studentNumber);

                DB::table('zb_bank_statements')
                    ->where('id', $statement->id)
                    ->update([
                        'narration' => $updatedNarration,
                        'pipe5_details' => $updatedPipe5Details,
                    ]);
            }

            $transactionQuery->update([
                'status' => FinanceTransactionQueryStatusEnum::RECONCILED,
                'bank_statement_id' => $statement->id,
                'reconciled_by' => $actor->id,
                'reconciled_at' => now(),
            ]);

            $this->notifyStudent($transactionQuery);

            return $transactionQuery->refresh();
        });
    }

    private function resolveStatement(FinanceTransactionQuery $transactionQuery, ?int $bankStatementId): ZBBankStatement
    {
        if ($bankStatementId !== null) {
            return ZBBankStatement::query()->findOrFail($bankStatementId);
        }

        $statement = $this->findStatementCandidateByReference($transactionQuery);
        abort_if($statement === null, 404, 'No bank statement match found for this payment reference.');

        return $statement;
    }

    private function assertCanReconcile(FinanceTransactionQuery $transactionQuery, ZBBankStatement $statement): void
    {
        $hasReferenceMatch = trim((string) $transactionQuery->payment_reference) !== ''
            && (
                trim((string) $statement->reference) === trim((string) $transactionQuery->payment_reference)
                || str_contains(
                    mb_strtolower((string) $statement->narration),
                    mb_strtolower((string) $transactionQuery->payment_reference)
                )
            );

        abort_unless($hasReferenceMatch, 422, 'Transaction query does not meet reconciliation requirements.');
    }

    private function notifyStudent(FinanceTransactionQuery $transactionQuery): void
    {
        $user = Student::query()
            ->with('user')
            ->whereKey($transactionQuery->student_id)
            ->first()?->user;

        if ($user === null) {
            return;
        }

        $user->notify(new FinanceTransactionQueryStatusUpdated($transactionQuery));
    }

    /**
     * @param array<int, FinanceTransactionQueryStatusEnum> $allowedCurrentStatuses
     */
    private function assertStatusTransitionAllowed(
        FinanceTransactionQuery $transactionQuery,
        array $allowedCurrentStatuses
    ): void {
        $currentStatus = $transactionQuery->status;

        abort_if(
            $currentStatus === null || ! in_array($currentStatus, $allowedCurrentStatuses, true),
            422,
            'Current query status cannot transition to the requested state.'
        );
    }
}

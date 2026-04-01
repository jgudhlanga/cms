<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\StudentPaymentReceiptResource;
use App\Models\Integrations\Banks\ZBBankStatement;
use App\Models\Students\Student;

class FinanceReceiptController extends Controller
{
    public function getStudentReceipts(Student $student)
    {
        $escapedStudentNumber = addcslashes($student->student_number, '\%_');
        $studentNumberPattern = "%{$escapedStudentNumber}%";

        $query = ZBBankStatement::query()->where('debit_credit_flag', 'C');
        $query->where(function ($statementQuery) use ($studentNumberPattern) {
            $statementQuery
                ->where('narration', 'like', $studentNumberPattern)
                ->orWhere('pipe5_details', 'like', $studentNumberPattern)
                ->orWhere('pipe10_details', 'like', $studentNumberPattern)
                ->orWhere('transaction_details', 'like', $studentNumberPattern);
        })->orderByDesc('transaction_date');
        
        $receipts = $query->paginate();

        return StudentPaymentReceiptResource::collection($receipts);
    }
}

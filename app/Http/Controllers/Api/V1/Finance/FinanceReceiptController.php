<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Finance\StudentPaymentReceiptResource;
use App\Models\Integrations\Banks\BankPayment;
use App\Models\Students\Student;

class FinanceReceiptController extends Controller
{
    public function getStudentReceipts(Student $student)
    {
        $query = BankPayment::query();
        $query->where('nr3', $student->student_number);
        $receipts = $query->get();

        return StudentPaymentReceiptResource::collection($receipts);
    }
}

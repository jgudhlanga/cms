<?php

namespace App\Http\Controllers\Documents;

use App\Helpers\DocumentHelper;
use App\Http\Controllers\Controller;
use App\Models\Students\Student;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use App\Services\Finance\StudentFinancialStatementPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function __construct(
        private readonly StudentFinancialStatementPdfService $studentFinancialStatementPdfService,
    ) {}

    public function previewOfferLetter(StudentProgram $studentProgram)
    {
        // Get the StudentProgram only if it has a verified class list
        [$documentTemplate, $studentName, $studentIdNumber, $studentNumber, $intakePeriod, $department,
            $level, $course, $modeOfStudy, $tuition] = DocumentHelper::assembleOfferLetter($studentProgram);
        // PDF Filename
        $fileName = Str::slug($studentName).'-offer-letter-'.time().'.pdf';
        // Generate PDF
        $pdf = Pdf::loadView('students.offer-letter', compact(
            'documentTemplate',
            'studentName',
            'studentIdNumber',
            'studentNumber',
            'intakePeriod',
            'department',
            'level',
            'course',
            'modeOfStudy',
            'tuition'
        ));

        return $pdf->stream($fileName);
    }

    public function exportTransactionStatement(Request $request, Student $student)
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_UNAUTHORIZED);

        $this->authorizeTransactionStatement($user, $student);

        $payload = $this->studentFinancialStatementPdfService->assemble($student);
        $fileName = Str::slug((string) $payload['studentName']).'-transaction-statement-'.time().'.pdf';

        return Pdf::loadView('students.transaction-statement', $payload)->stream($fileName);
    }

    private function authorizeTransactionStatement(User $user, Student $student): void
    {
        $isOwnStudentRecord = $user->studentProfile?->id === $student->id || $user->id === $student->user_id;

        if ($isOwnStudentRecord) {
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
}

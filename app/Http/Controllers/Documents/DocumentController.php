<?php

namespace App\Http\Controllers\Documents;

use App\Helpers\DocumentHelper;
use App\Http\Controllers\Controller;
use App\Models\Students\StudentProgram;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function previewOfferLetter(StudentProgram $studentProgram)
    {
        // Get the StudentProgram only if it has a verified class list
        [$documentTemplate, $studentName, $studentIdNumber, $studentNumber, $intakePeriod, $department,
            $level, $course, $modeOfStudy, $tuition] = DocumentHelper::assembleOfferLetter($studentProgram);
        // PDF Filename
        $fileName = Str::slug($studentName) . '-offer-letter-' . time() . '.pdf';
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
}

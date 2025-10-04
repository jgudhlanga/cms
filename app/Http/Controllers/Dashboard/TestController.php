<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\Acl\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\Students\StudentProgram;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class TestController extends Controller
{

    public function debug(string $item)
    {
        //
    }

    /**
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function pdf(StudentProgram $studentProgram)
    {
        $fileName = 'offer-letter-' . $studentProgram->id . '.pdf';

        $pdfPath = storage_path("app/students/{$fileName}");

        // Generate and save the PDF into the students disk
        Pdf::view('students.offer-letter', compact('studentProgram'))->save($pdfPath);

        // Add to media library
        $studentProgram->addMedia($pdfPath)->usingFileName($fileName)->toMediaCollection('offer-letter');

        // Retrieve it back (just like with proof_of_payment)
        $file = $studentProgram->getFirstMedia('offer-letter');

        // Optional: delete the temporary file
        return back()->with('success', 'Offer letter generated successfully.');
    }
}

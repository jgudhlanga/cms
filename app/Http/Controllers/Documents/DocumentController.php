<?php

namespace App\Http\Controllers\Documents;

use App\Actions\Documents\GenerateAndStoreOfferLetterAction;
use App\Http\Controllers\Controller;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Users\User;
use App\Services\Finance\StudentFinancialStatementPdfService;
use App\Services\Students\StudentOfferLetterService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        private readonly StudentFinancialStatementPdfService $studentFinancialStatementPdfService,
        private readonly StudentOfferLetterService $studentOfferLetterService,
        private readonly GenerateAndStoreOfferLetterAction $generateAndStoreOfferLetterAction,
    ) {}

    public function previewOfferLetter(Request $request, StudentApplication $studentApplication): BinaryFileResponse|Response|RedirectResponse
    {
        $actor = $request->user() instanceof User ? $request->user() : null;

        if (! $this->studentOfferLetterService->canAccess($studentApplication, $actor, $request)) {
            abort_if($actor !== null, Response::HTTP_FORBIDDEN);

            return redirect()->guest(route('login'));
        }

        abort_unless(
            $this->studentOfferLetterService->isDownloadable($studentApplication, $actor),
            Response::HTTP_NOT_FOUND,
        );

        $media = $this->generateAndStoreOfferLetterAction->execute(
            $studentApplication,
            ! $this->studentOfferLetterService->canBypassDownloadGates($actor),
        );

        $studentApplication->loadMissing('student.user');
        $studentName = (string) ($studentApplication->student?->user?->full_name ?? 'student');
        $studentNumber = (string) ($studentApplication->student?->student_number ?? '');
        $fileName = ($studentNumber !== '' ? Str::slug($studentNumber) : Str::slug($studentName)).'-offer-letter.pdf';

        return response()->download($media->getPath(), $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
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

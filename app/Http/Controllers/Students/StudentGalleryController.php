<?php

declare(strict_types=1);

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentGalleryPhotoRequest;
use App\Http\Resources\Students\StudentResource;
use App\Models\Students\Student;
use App\Services\Students\StudentIdCardPhotoService;
use App\Services\Students\IntakePeriodResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentGalleryController extends Controller
{
    public function __construct(
        private readonly StudentIdCardPhotoService $photoService,
        private readonly IntakePeriodResolver $intakePeriodResolver,
    ) {}

    public function index(Request $request): Response
    {
        $student = $this->authorizedStudent($request);
        $latestPhoto = $student->latestIdPhoto();

        return Inertia::render('portal/student/gallery/Index', [
            'student' => StudentResource::make($student),
            'hasIdentity' => $this->photoService->identityStem($student) !== null,
            'idPhotoUrl' => $latestPhoto?->getFullUrl('card') ?? $latestPhoto?->getFullUrl(),
            'idPhotoThumbUrl' => $latestPhoto?->getFullUrl('thumb') ?? $latestPhoto?->getFullUrl(),
            'photoMinWidth' => (int) config('id_cards.photo.min_width', 200),
            'photoMinHeight' => (int) config('id_cards.photo.min_height', 240),
            'photoMaxKilobytes' => (int) config('id_cards.photo.max_kilobytes', 2048),
            'offerLetterIntakePeriodIds' => $this->intakePeriodResolver->offerLetterIntakePeriodIds(),
        ]);
    }

    public function storeIdPhoto(StoreStudentGalleryPhotoRequest $request): RedirectResponse
    {
        $this->photoService->uploadIdPhoto($this->student($request), $request->file('photo'));

        return back()->with('success', __('students.id_card_photo_uploaded'));
    }

    public function store(StoreStudentGalleryPhotoRequest $request): RedirectResponse
    {
        $this->photoService->uploadGalleryPhoto($this->student($request), $request->file('photo'));

        return back()->with('success', __('students.id_card_gallery_photo_uploaded'));
    }

    public function destroy(Request $request, Media $media): RedirectResponse
    {
        $student = $this->authorizedStudent($request);
        $this->photoService->deleteGalleryPhoto($student, $media);

        return back()->with('success', __('students.id_card_gallery_photo_deleted'));
    }

    private function authorizedStudent(Request $request): Student
    {
        $student = $this->student($request);
        $this->authorize('manageGallery', $student);

        return $student;
    }

    private function student(Request $request): Student
    {
        $student = $request->user()?->studentProfile;
        abort_if($student === null, 404);

        return $student;
    }
}

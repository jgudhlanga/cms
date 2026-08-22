<?php

namespace App\Http\Controllers\Students;

use App\DTO\Students\UpdateStudentDto;
use App\Exceptions\Maintenance\StudentIdNumberConflictException;
use App\Exports\Students\StudentListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\ExportStudentListRequest;
use App\Http\Requests\Students\FixStudentIdNumberRequest;
use App\Http\Requests\Students\PurgeStudentAccountRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Http\Requests\Students\UploadStudentIdCardPhotoRequest;
use App\Http\Resources\Students\StudentResource;
use App\Http\Resources\Users\UserResource;
use App\Models\Students\Student;
use App\Repositories\Shared\interface\IAddressRepository;
use App\Repositories\Shared\interface\IContactRepository;
use App\Repositories\Shared\interface\INextOfKinRepository;
use App\Repositories\Students\interface\IStudentApplicationRepository;
use App\Repositories\Students\interface\IStudentRepository;
use App\Repositories\Users\interface\IUserRepository;
use App\Services\AccountPurge\StudentAccountPurgeService;
use App\Services\Maintenance\Students\FixStudentIdNumberService;
use App\Services\Students\IntakePeriodResolver;
use App\Services\Students\StudentIdCardPhotoService;
use App\Services\Students\StudentListExportService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentController extends Controller
{
    public function __construct(
        protected IStudentRepository $repository,
        protected IUserRepository $userRepository,
        protected IContactRepository $contactRepository,
        protected IAddressRepository $addressRepository,
        protected INextOfKinRepository $nextOfKinRepository,
        protected IStudentApplicationRepository $studentApplicationRepository,
    ) {}

    /**
     * @throws AuthorizationException
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Student::class);

        return Inertia::render('students/Index');
    }

    public function export(ExportStudentListRequest $request, StudentListExportService $exportService): BinaryFileResponse
    {
        $this->authorize('export', Student::class);

        $fileName = 'students-'.now()->format('Y-m-d_His').'.xlsx';

        return Excel::download(
            new StudentListExport($exportService->rows($request->validated())),
            $fileName,
        );
    }

    public function show(Student $student)
    {
        $this->authorize('view', $student);
        $student->loadMissing([
            'apprentices',
            'studentSponsors',
            'transfers.studentApplication',
            'latestApplication.intakePeriod',
            'latestApplication.transfer',
            'latestEnrolment.academicCalendar',
            'latestEnrolment.studentApplication.transfer',
        ]);
        $user = UserResource::make($student->user);
        $student = StudentResource::make($student);
        $intakePeriodResolver = app(IntakePeriodResolver::class);
        $activeIntakePeriodIds = $intakePeriodResolver->activeIntakePeriodIds();
        $offerLetterIntakePeriodIds = $intakePeriodResolver->offerLetterIntakePeriodIds();

        return Inertia::render('students/Show', compact(
            'user',
            'student',
            'activeIntakePeriodIds',
            'offerLetterIntakePeriodIds',
        ));
    }

    public function edit(string $id)
    {
        //
    }

    public function update(UpdateStudentRequest $request, Student $student): void
    {
        $this->repository->update($student, UpdateStudentDto::fromUpdateStudentRequest($request));
    }

    public function updateIdNumber(
        FixStudentIdNumberRequest $request,
        Student $student,
        FixStudentIdNumberService $fixService,
    ): JsonResponse {
        try {
            $student = $fixService->fix($student, (string) $request->validated('id_number'));
        } catch (StudentIdNumberConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [
                    'id_number' => [$exception->getMessage()],
                ],
            ], 409);
        }

        return response()->json([
            'message' => __('trans.item_saved', ['item' => __('trans.id_number')]),
            'data' => StudentResource::make($student),
        ]);
    }

    public function uploadIdPhoto(
        UploadStudentIdCardPhotoRequest $request,
        Student $student,
        StudentIdCardPhotoService $photoService,
    ): RedirectResponse {
        $this->authorize('uploadIdPhoto', $student);
        $photoService->uploadIdPhoto($student, $request->file('photo'));

        return back()->with('success', __('students.id_card_photo_uploaded'));
    }

    public function destroy(string $id)
    {
        //
    }

    public function purge(
        PurgeStudentAccountRequest $request,
        Student $student,
        StudentAccountPurgeService $purgeService,
    ): RedirectResponse {
        $authUser = Auth::user();

        abort_if($authUser === null, 403);

        $purgeService->purge(
            $student,
            $authUser,
            $request->validated('reason'),
            (int) $authUser->tenant_id,
        );

        $redirectRoute = match ($request->query('from')) {
            'users' => route('users.index'),
            'maintenance' => route('maintenance.index'),
            default => route('students.index'),
        };

        return redirect($redirectRoute)->with('success', __('trans.student_account_purge_success'));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Students;

use App\Enums\Students\StudentExamResultComment;
use App\Models\Examinations\ExaminationResult;
use App\Models\Students\Student;
use App\Models\Students\StudentExamResult;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class StudentExamResultLookupService
{
    public function __construct(
        private readonly StudentExamResultAccessService $accessService,
    ) {}

    /**
     * @return Collection<int, StudentExamResult>
     */
    public function listForStudent(Student $student): Collection
    {
        return StudentExamResult::query()
            ->where('student_id', $student->id)
            ->orderByDesc('calendar_year')
            ->orderByDesc('session')
            ->get();
    }

    /**
     * True when examination_results has a session for this student
     * that is not yet recorded in student_exam_results.
     */
    public function hasUnclaimedSession(Student $student): bool
    {
        $candidateNumbers = $this->candidateNumbersForStudent($student);

        if ($candidateNumbers === []) {
            return false;
        }

        $availableSessions = ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $student->tenant_id)
            ->where(function ($query) use ($student, $candidateNumbers): void {
                $query->whereIn('candidate_number', $candidateNumbers)
                    ->orWhere('student_id', $student->id);
            })
            ->distinct()
            ->pluck('session')
            ->filter(fn ($session) => is_string($session) && $session !== '')
            ->unique()
            ->values();

        if ($availableSessions->isEmpty()) {
            return false;
        }

        $recordedSessions = StudentExamResult::query()
            ->where('student_id', $student->id)
            ->pluck('session')
            ->filter(fn ($session) => is_string($session) && $session !== '')
            ->unique();

        return $availableSessions->diff($recordedSessions)->isNotEmpty();
    }

    /**
     * @return list<string>
     */
    private function candidateNumbersForStudent(Student $student): array
    {
        $candidates = StudentExamResult::query()
            ->where('student_id', $student->id)
            ->pluck('candidate_number')
            ->all();

        $studentNumber = trim((string) $student->student_number);
        if ($studentNumber !== '') {
            $candidates[] = $studentNumber;
        }

        $linked = ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $student->tenant_id)
            ->where('student_id', $student->id)
            ->pluck('candidate_number')
            ->all();

        return collect($candidates)
            ->merge($linked)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     allowed: bool,
     *     subjects: list<array<string, mixed>>,
     *     summary: array<string, mixed>,
     *     access: array<string, mixed>
     * }
     */
    public function showForStudent(Student $student, StudentExamResult $result): array
    {
        if ((int) $result->student_id !== (int) $student->id) {
            abort(404);
        }

        $access = $this->accessService->evaluate($student);

        if (! $access['canViewResults']) {
            return [
                'allowed' => false,
                'subjects' => [],
                'summary' => [
                    'id' => $result->id,
                    'candidateNumber' => $result->candidate_number,
                    'calendarYear' => $result->calendar_year,
                    'session' => $result->session,
                    'comment' => $result->comment?->value,
                    'rawCourseComment' => $result->raw_course_comment,
                    'commentNeedsReview' => $result->comment_needs_review,
                ],
                'access' => $access,
            ];
        }

        $subjects = $this->subjectsForSession(
            (int) $student->tenant_id,
            (string) $result->candidate_number,
            (string) $result->session,
        );

        return [
            'allowed' => true,
            'subjects' => $subjects,
            'summary' => [
                'id' => $result->id,
                'candidateNumber' => $result->candidate_number,
                'calendarYear' => $result->calendar_year,
                'session' => $result->session,
                'comment' => $result->comment?->value,
                'rawCourseComment' => $result->raw_course_comment,
                'commentNeedsReview' => $result->comment_needs_review,
            ],
            'access' => $access,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function subjectsForSession(int $tenantId, string $candidateNumber, string $session): array
    {
        return ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('candidate_number', $candidateNumber)
            ->where('session', $session)
            ->orderBy('subject_code')
            ->get()
            ->map(fn (ExaminationResult $row): array => $this->mapSubjectRow($row))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSubjectRow(ExaminationResult $row): array
    {
        return [
            'id' => $row->id,
            'discipline' => $row->discipline,
            'courseCode' => $row->course_code,
            'candidateNumber' => $row->candidate_number,
            'surname' => $row->surname,
            'firstNames' => $row->first_names,
            'subjectCode' => $row->subject_code,
            'subject' => $row->subject,
            'grade' => $row->grade,
            'session' => $row->session,
            'sessionDate' => $row->session_date?->toDateString(),
            'courseComment' => $row->course_comment,
        ];
    }

    /**
     * @return array{
     *     found: bool,
     *     candidateNumber: string,
     *     subjects: list<array<string, mixed>>,
     *     summary: array<string, mixed>|null,
     *     studentExamResult: StudentExamResult|null,
     *     studentIdMismatch: bool,
     *     message: string|null
     * }
     */
    public function lookup(Student $student, string $candidateNumber): array
    {
        $access = $this->accessService->evaluate($student);

        if (! $access['canViewResults']) {
            throw ValidationException::withMessages([
                'candidate_number' => [__('trans.exam_results_access_denied')],
            ]);
        }

        $candidateNumber = trim($candidateNumber);

        if ($candidateNumber === '') {
            throw ValidationException::withMessages([
                'candidate_number' => [__('trans.exam_results_candidate_required')],
            ]);
        }

        /** @var Collection<int, ExaminationResult> $results */
        $results = ExaminationResult::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $student->tenant_id)
            ->where('candidate_number', $candidateNumber)
            ->orderByDesc('session_date')
            ->orderBy('subject_code')
            ->get();

        if ($results->isEmpty()) {
            Log::info('exam_results.candidate_not_found', [
                'student_id' => $student->id,
                'tenant_id' => $student->tenant_id,
                'candidate_number' => $candidateNumber,
            ]);

            return [
                'found' => false,
                'candidateNumber' => $candidateNumber,
                'subjects' => [],
                'summary' => null,
                'studentExamResult' => null,
                'studentIdMismatch' => false,
                'message' => __('trans.exam_results_not_found'),
            ];
        }

        $linkedStudentIds = $results->pluck('student_id')->filter()->unique();
        $studentIdMismatch = $linkedStudentIds->isNotEmpty()
            && ! $linkedStudentIds->contains($student->id);

        if ($studentIdMismatch) {
            Log::warning('exam_results.candidate_student_mismatch', [
                'student_id' => $student->id,
                'candidate_number' => $candidateNumber,
                'linked_student_ids' => $linkedStudentIds->values()->all(),
            ]);

            throw ValidationException::withMessages([
                'candidate_number' => [__('trans.exam_results_candidate_mismatch')],
            ]);
        }

        $latestSession = (string) $results->first()?->session;
        $sessionResults = $results->where('session', $latestSession)->values();
        $summary = $this->upsertSummary($student, $candidateNumber, $sessionResults);

        return [
            'found' => true,
            'candidateNumber' => $candidateNumber,
            'subjects' => $sessionResults->map(fn (ExaminationResult $row): array => $this->mapSubjectRow($row))->all(),
            'summary' => [
                'calendarYear' => $summary->calendar_year,
                'session' => $summary->session,
                'comment' => $summary->comment?->value,
                'rawCourseComment' => $summary->raw_course_comment,
                'commentNeedsReview' => $summary->comment_needs_review,
            ],
            'studentExamResult' => $summary,
            'studentIdMismatch' => false,
            'message' => null,
        ];
    }

    /**
     * @param  Collection<int, ExaminationResult>  $sessionResults
     */
    private function upsertSummary(Student $student, string $candidateNumber, Collection $sessionResults): StudentExamResult
    {
        $first = $sessionResults->first();
        $session = (string) $first?->session;
        $calendarYear = $this->resolveCalendarYear($first);
        $rawComment = $sessionResults
            ->pluck('course_comment')
            ->filter(fn ($comment) => is_string($comment) && trim($comment) !== '')
            ->first();
        $comment = StudentExamResultComment::tryFromCourseComment(is_string($rawComment) ? $rawComment : null);
        $enrolment = $this->accessService->resolveEnrolmentContext($student);

        return StudentExamResult::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'calendar_year' => $calendarYear,
                'session' => $session,
            ],
            [
                'tenant_id' => $student->tenant_id,
                'candidate_number' => $candidateNumber,
                'id_number' => $student->id_number ?? $student->passport_number,
                'institution_department_id' => $enrolment?->institution_department_id,
                'department_level_id' => $enrolment?->department_level_id,
                'department_course_id' => $enrolment?->department_course_id,
                'mode_of_study_id' => $enrolment?->mode_of_study_id,
                'comment' => $comment,
                'raw_course_comment' => is_string($rawComment) ? $rawComment : null,
                'comment_needs_review' => $rawComment !== null && $comment === null,
            ]
        );
    }

    private function resolveCalendarYear(?ExaminationResult $result): int
    {
        if ($result?->session_date !== null) {
            return (int) $result->session_date->year;
        }

        $session = (string) ($result?->session ?? '');

        if (preg_match('/(20\d{2})/', $session, $matches) === 1) {
            return (int) $matches[1];
        }

        return (int) Carbon::now()->year;
    }
}

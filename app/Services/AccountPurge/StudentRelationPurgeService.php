<?php

declare(strict_types=1);

namespace App\Services\AccountPurge;

use App\Models\Ledgers\Ledger;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Models\Students\StudentIdCardRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StudentRelationPurgeService
{
    public function purge(Student $student, ?int $preserveNoteId = null): void
    {
        $applicationIds = $student->applications()->withTrashed()->pluck('id');
        $enrolmentIds = $student->enrolments()->withTrashed()->pluck('id');

        $this->purgeEnrolmentDependents($enrolmentIds);
        $student->enrolments()->withTrashed()->forceDelete();

        $this->purgeApplicationDependents($applicationIds);
        $this->purgeApplications($applicationIds);

        DB::table('hostel_notice_student')->where('student_id', $student->id)->delete();

        $this->purgeIdCardRequests($student);

        $student->hostelApplications()->withTrashed()->forceDelete();
        $student->hostelRoomAllocations()->withTrashed()->forceDelete();
        $student->hostelQueries()->withTrashed()->forceDelete();
        $student->hostelLeaves()->withTrashed()->forceDelete();
        $student->sponsors()->withTrashed()->forceDelete();
        $student->academicRecord()->withTrashed()->forceDelete();
        $student->oLevelResults()->withTrashed()->forceDelete();

        DB::table('student_apprentices')->where('student_id', $student->id)->delete();
        DB::table('student_sponsors')->where('student_id', $student->id)->delete();
        $student->financeTransactionQueries()->delete();
        $student->clearances()->delete();
        $student->examResults()->delete();
        DB::table('examination_results')->where('student_id', $student->id)->delete();

        $student->contacts()->withTrashed()->forceDelete();
        $student->addresses()->withTrashed()->forceDelete();
        $student->nextOfKins()->withTrashed()->forceDelete();

        $notesQuery = $student->notes()->withTrashed();
        if ($preserveNoteId !== null) {
            $notesQuery->whereKeyNot($preserveNoteId);
        }
        $notesQuery->forceDelete();
    }

    private function purgeIdCardRequests(Student $student): void
    {
        $student->idCardRequests()
            ->withTrashed()
            ->get()
            ->each(function (StudentIdCardRequest $request): void {
                $request->ledgerTransactions()
                    ->withTrashed()
                    ->get()
                    ->each(function (Ledger $ledger): void {
                        if ($ledger->proof_of_payment_id) {
                            Media::query()->whereKey($ledger->proof_of_payment_id)->delete();
                        }

                        $ledger->clearMediaCollection('receipts');
                        $ledger->forceDelete();
                    });

                $request->clearMediaCollection(StudentIdCardRequest::MEDIA_COLLECTION);
                $request->forceDelete();
            });

        $student->clearMediaCollection(Student::ID_PHOTO_COLLECTION);
    }

    /**
     * @param  Collection<int, int>  $enrolmentIds
     */
    private function purgeEnrolmentDependents($enrolmentIds): void
    {
        if ($enrolmentIds->isEmpty()) {
            return;
        }

        DB::table('course_work_audit_logs')->whereIn('student_enrolment_id', $enrolmentIds)->delete();
        DB::table('course_work_marks')->whereIn('student_enrolment_id', $enrolmentIds)->delete();
        DB::table('academic_calendar_student_enrolments')->whereIn('student_enrolment_id', $enrolmentIds)->delete();
    }

    /**
     * @param  Collection<int, int>  $applicationIds
     */
    private function purgeApplicationDependents($applicationIds): void
    {
        if ($applicationIds->isEmpty()) {
            return;
        }

        DB::table('bulk_finalise_enrolment_audit_logs')
            ->whereIn('student_application_id', $applicationIds)
            ->delete();
    }

    /**
     * @param  Collection<int, int>  $applicationIds
     */
    private function purgeApplications($applicationIds): void
    {
        if ($applicationIds->isEmpty()) {
            return;
        }

        DB::table('class_lists')->whereIn('student_application_id', $applicationIds)->delete();

        StudentApplication::query()
            ->withTrashed()
            ->whereIn('id', $applicationIds)
            ->get()
            ->each(function (StudentApplication $application): void {
                $application->notes()->withTrashed()->forceDelete();

                $application->ledgerTransactions()
                    ->withTrashed()
                    ->get()
                    ->each(function (Ledger $ledger): void {
                        if ($ledger->proof_of_payment_id) {
                            Media::query()->whereKey($ledger->proof_of_payment_id)->delete();
                        }

                        $ledger->clearMediaCollection('receipts');
                        $ledger->forceDelete();
                    });

                $application->clearMediaCollection('offer-letter');
                $application->forceDelete();
            });
    }
}

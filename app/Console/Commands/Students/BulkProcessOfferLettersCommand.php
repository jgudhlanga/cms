<?php

namespace App\Console\Commands\Students;

use App\Enums\Institution\CourseEnum;
use App\Enums\Institution\LevelEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\EnrolmentHelper;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentProgram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BulkProcessOfferLettersCommand extends Command
{
    protected $signature = 'app:bulk-process-offer-letters-command';

    protected $description = 'Bulk generate offer letters for students';

    public function handle(): void
    {
        $successCount = 0;
        $failedCount = 0;

        $acceptedStep = WorkflowStep::where('slug', WorkflowStepEnum::ACCEPTED->slug())->first();

        if (!$acceptedStep) {
            $this->error('Accepted workflow step not found.');
            return;
        }

        StudentProgram::with(['departmentWorkflowStep.workflowStep', 'student.user'])
            ->whereHas('departmentLevel.level', function ($query) {
                $query->whereIn('name', [LevelEnum::ND, LevelEnum::HND]);
            })
            ->whereHas('departmentCourse.course', function ($query) {
                $query->whereNotIn('name', [CourseEnum::PHARMACEUTICAL_TECHNOLOGY]);
            })
            ->whereHas('departmentWorkflowStep.workflowStep', function ($query) {
                $query->whereNotIn('name', [
                    WorkflowStepEnum::ENROLLED,
                    WorkflowStepEnum::REJECTED,
                    WorkflowStepEnum::ACCEPTED,
                ]);
            })
            ->chunkById(100, function ($programs) use ($acceptedStep, &$successCount, &$failedCount) {

                foreach ($programs as $program) {
                    try {
                        DB::transaction(function () use ($program, $acceptedStep) {

                            $classList = ClassList::firstOrNew([
                                'student_program_id' => $program->id
                            ]);

                            $classList->fill([
                                'tenant_id' => $program->tenant_id,
                                'type' => 'verified',
                                'attributes' => array_merge(
                                    $classList->attributes ?? [],
                                    [
                                        'identity_confirmed' => false,
                                        'disability_confirmed' => false,
                                        'names_confirmed' => false,
                                        'o_level_confirmed' => false,
                                        'previous_level_confirmed' => false,
                                    ]
                                ),
                            ]);

                            $classList->save();

                            $studentNumber = EnrolmentHelper::resolveStudentNumber($program);

                            $program->student->update([
                                'student_number' => $studentNumber,
                                'student_number_generated' => true,
                            ]);

                            $departmentStep = DepartmentApplicationStep::where(
                                'institution_department_id',
                                $program->institution_department_id
                            )->where(
                                'workflow_step_id',
                                $acceptedStep->id
                            )->first();

                            if (!$departmentStep) {
                                throw new \Exception('Department step not found.');
                            }

                            $program->update([
                                'department_application_step_id' => $departmentStep->id
                            ]);

                            $user = $program->student->user;

                            SendOfferLetterJob::dispatch(
                                $user->full_name,
                                $user->email,
                                $program->id
                            )->withoutDelay();
                        });

                        $successCount++;
                        $this->info("Offer letter generated successfully. {$program->id}");

                    } catch (Throwable $e) {
                        $failedCount++;
                        $this->error("Offer letter failed for {$program->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->line('');
        $this->info("Bulk processing completed.");
        $this->info("Successful: {$successCount}");
        $this->error("Failed: {$failedCount}");
    }
}

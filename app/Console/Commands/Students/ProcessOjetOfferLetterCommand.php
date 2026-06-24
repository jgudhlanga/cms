<?php

namespace App\Console\Commands\Students;

use App\Enums\Institution\ModeOfStudyEnum;
use App\Enums\Shared\WorkflowStepEnum;
use App\Helpers\EnrolmentHelper;
use App\Jobs\Enrolments\SendOfferLetterJob;
use App\Models\Enrolments\ClassList;
use App\Models\Institution\DepartmentApplicationStep;
use App\Models\Institution\ModeOfStudy;
use App\Models\Shared\WorkflowStep;
use App\Models\Students\StudentApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessOjetOfferLetterCommand extends Command
{
    protected $signature = 'app:process-ojet-offer-letters-command';

    protected $description = 'Generate OJET offer letters for students';

    public function handle(): void
    {
        $ojet = ModeOfStudy::where('name', ModeOfStudyEnum::OJET)->first();
        if (!$ojet) {
            return;
        }

        $acceptedStep = WorkflowStep::where(
            'slug',
            WorkflowStepEnum::ACCEPTED->slug()
        )->first();

        if (!$acceptedStep) {
            $this->error('Accepted workflow step not found.');
            return;
        }

        StudentApplication::with(['departmentWorkflowStep.workflowStep', 'student.user'])
            ->where('mode_of_study_id', $ojet->id)
            ->whereHas('departmentWorkflowStep.workflowStep', function ($query) {
                $query->whereNotIn('name', [
                    WorkflowStepEnum::ENROLLED,
                    WorkflowStepEnum::REJECTED,
                    WorkflowStepEnum::ACCEPTED,
                ]);
            })
            ->chunkById(100, function ($programs) use ($acceptedStep) {
                foreach ($programs as $program) {

                    try {
                        DB::transaction(function () use ($program, $acceptedStep) {

                            $classList = ClassList::firstOrNew([
                                'student_application_id' => $program->id,
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
                            SendOfferLetterJob::dispatch($user->full_name, $user->email, $program->id)->withoutDelay();
                            $this->info("Offer letter generated successfully. {$program->id}");
                        });
                    } catch (Throwable $e) {
                        $this->error("Offer letter failed for {$program->id}: {$e->getMessage()}");
                    }
                }
            });
    }
}

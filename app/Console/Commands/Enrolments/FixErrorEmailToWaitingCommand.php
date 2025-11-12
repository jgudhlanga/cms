<?php

namespace App\Console\Commands\Enrolments;

use App\Jobs\Enrolments\SendEnrolmentProgressJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixErrorEmailToWaitingCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:email-to-waiting-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $classes = DB::table('class_lists as cl')
            ->join('student_programs as sp', 'sp.id', '=', 'cl.student_program_id')
            ->join('students as st', 'st.id', '=', 'sp.student_id')
            ->join('institution_departments as idp', 'idp.id', '=', 'sp.institution_department_id')
            ->join('departments as dp', 'dp.id', '=', 'idp.department_id')
            ->join('department_levels as dl', 'dl.id', '=', 'sp.department_level_id')
            ->join('levels as lv', 'lv.id', '=', 'dl.level_id')
            ->join('department_courses as dc', 'dc.id', '=', 'sp.department_course_id')
            ->join('courses as cs', 'cs.id', '=', 'dc.course_id')
            ->join('users as us', 'us.id', '=', 'st.user_id')
            ->where('cl.type', 'waiting')
            ->select([
                'cl.id',
                'us.first_name',
                'us.last_name',
                'us.email',
                'sp.institution_department_id',
                'dp.name as department',
                'lv.name as level',
                'cs.name as course',
            ])
            ->get();
        foreach ($classes as $class) {
            SendEnrolmentProgressJob::dispatch(
                $class->id,
                'waiting',
                $class->institution_department_id,
                $class->department,
                $class->level,
                $class->course)
                ->withoutDelay();
        }
    }
}

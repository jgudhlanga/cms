<?php

namespace App\Console\Commands\Students;

use App\Models\Students\Student;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateBulkDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-bulk-data-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bulk data for students';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating bulk data for students started');
        $nextYear = Carbon::now()->addYear()->format('y'); // 2-digit next year

        Student::chunk(100, function($students) use ($nextYear) {
            foreach ($students as $student) {
                $studentNumber = $student->student_number;

                // Replace first two characters with next year
                $newStudentNumber = $nextYear . substr($studentNumber, 2);

                // Update the student record
                $student->update(['student_number' => $newStudentNumber]);
            }
        });
        $this->info('Done!');
    }
}

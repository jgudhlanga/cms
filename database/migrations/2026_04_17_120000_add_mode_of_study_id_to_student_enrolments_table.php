<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table) {
            $table->foreignId('mode_of_study_id')
                ->nullable()
                ->after('academic_calendar_id')
                ->constrained('mode_of_studies');
            $table->index('mode_of_study_id');
        });

        $rows = DB::table('student_enrolments')
            ->join('student_programs', 'student_enrolments.student_program_id', '=', 'student_programs.id')
            ->whereNull('student_enrolments.mode_of_study_id')
            ->whereNotNull('student_programs.mode_of_study_id')
            ->select('student_enrolments.id', 'student_programs.mode_of_study_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('student_enrolments')
                ->where('id', $row->id)
                ->update(['mode_of_study_id' => $row->mode_of_study_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrolments', function (Blueprint $table) {
            $table->dropForeign(['mode_of_study_id']);
            $table->dropColumn('mode_of_study_id');
        });
    }
};

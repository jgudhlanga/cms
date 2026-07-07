<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_work_audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['assessment_type_id']);
        });

        Schema::table('course_work_audit_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('assessment_type_id')->nullable()->change();
            $table->foreign('assessment_type_id')->references('id')->on('assessment_types');
        });
    }

    public function down(): void
    {
        Schema::table('course_work_audit_logs', function (Blueprint $table): void {
            $table->dropForeign(['assessment_type_id']);
        });

        DB::table('course_work_audit_logs')->whereNull('assessment_type_id')->delete();

        Schema::table('course_work_audit_logs', function (Blueprint $table): void {
            $table->unsignedBigInteger('assessment_type_id')->nullable(false)->change();
            $table->foreign('assessment_type_id')->references('id')->on('assessment_types');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('course_syllabuses')) {
            return;
        }

        Schema::table('course_syllabuses', function (Blueprint $table) {
            if (! Schema::hasColumn('course_syllabuses', 'syllabus_document_id')) {
                $table->unsignedBigInteger('syllabus_document_id')->nullable()->after('implementation_year');
            }
            if (! Schema::hasColumn('course_syllabuses', 'status')) {
                $table->enum('status', ['active', 'terminated'])->default('active')->after('syllabus_document_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('course_syllabuses')) {
            return;
        }

        Schema::table('course_syllabuses', function (Blueprint $table) {
            if (Schema::hasColumn('course_syllabuses', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

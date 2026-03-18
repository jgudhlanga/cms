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
        Schema::table('class_configs', function (Blueprint $table) {
            $table->foreignId('mode_of_study_id')->after('department_level_id')->constrained('mode_of_studies');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_configs', function (Blueprint $table) {
            $table->dropForeign(['mode_of_study_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_courses', function (Blueprint $table) {
            $table->boolean('coursework_capture_enabled')->nullable()->default(true)->after('show_on_current_application_period');
        });
    }

    public function down(): void
    {
        Schema::table('department_courses', function (Blueprint $table) {
            $table->dropColumn('coursework_capture_enabled');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hms_settings', function (Blueprint $table) {
            $table->boolean('applications_open')->default(false)->after('allow_guests');
            $table->date('application_start_date')->nullable()->after('applications_open');
            $table->date('application_end_date')->nullable()->after('application_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('hms_settings', function (Blueprint $table) {
            $table->dropColumn([
                'applications_open',
                'application_start_date',
                'application_end_date',
            ]);
        });
    }
};

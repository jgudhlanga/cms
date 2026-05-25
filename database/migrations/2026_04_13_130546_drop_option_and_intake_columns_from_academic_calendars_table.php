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
        if (! Schema::hasTable('academic_calendars')) {
            return;
        }

        Schema::table('academic_calendars', function (Blueprint $table): void {
            if (Schema::hasColumn('academic_calendars', 'academic_calendar_option_id')) {
                $table->dropForeign(['academic_calendar_option_id']);
                $table->dropColumn('academic_calendar_option_id');
            }

            if (Schema::hasColumn('academic_calendars', 'intake_period_ids')) {
                $table->dropColumn('intake_period_ids');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_calendars', function (Blueprint $table) {
            $table->foreignId('academic_calendar_option_id')->after('id')->constrained();
            $table->json('intake_period_ids')->nullable()->after('closing_date');
        });
    }
};

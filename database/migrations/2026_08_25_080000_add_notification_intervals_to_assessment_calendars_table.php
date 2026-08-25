<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_calendars', function (Blueprint $table) {
            $table->unsignedSmallInteger('first_notification_days_before')->default(10)->after('end_date');
            $table->unsignedSmallInteger('second_notification_days_before')->default(5)->after('first_notification_days_before');
            $table->unsignedSmallInteger('due_notification_days_before')->default(0)->after('second_notification_days_before');
        });
    }

    public function down(): void
    {
        Schema::table('assessment_calendars', function (Blueprint $table) {
            $table->dropColumn([
                'first_notification_days_before',
                'second_notification_days_before',
                'due_notification_days_before',
            ]);
        });
    }
};

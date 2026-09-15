<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reminder tiers are now sent per department (lecturers and HOD, on the department's own dates) as
        // well as college-wide (VP Academics, on the global dates), so a tier is unique per calendar and scope.
        Schema::table('assessment_calendar_notification_dispatches', function (Blueprint $table) {
            $table->foreignId('institution_department_id')
                ->nullable()
                ->after('assessment_calendar_id')
                ->constrained(indexName: 'acnd_inst_dept_fk')
                ->nullOnDelete();
            $table->string('scope_key', 40)->default('global')->after('institution_department_id');
        });

        Schema::table('assessment_calendar_notification_dispatches', function (Blueprint $table) {
            // Add the new index before dropping the old one: the calendar foreign key needs an index to remain.
            $table->unique(['assessment_calendar_id', 'scope_key', 'tier'], 'acnd_calendar_scope_tier_unique');
            $table->dropUnique('acnd_calendar_tier_unique');
        });

        Schema::create('assessment_window_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
            $table->foreignId('assessment_calendar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_department_id')->constrained(indexName: 'awn_inst_dept_fk')->cascadeOnDelete();
            $table->string('event', 20);
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(
                ['assessment_calendar_id', 'institution_department_id', 'event'],
                'awn_calendar_department_event_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_window_notifications');

        Schema::table('assessment_calendar_notification_dispatches', function (Blueprint $table) {
            $table->unique(['assessment_calendar_id', 'tier'], 'acnd_calendar_tier_unique');
            $table->dropUnique('acnd_calendar_scope_tier_unique');
        });

        Schema::table('assessment_calendar_notification_dispatches', function (Blueprint $table) {
            $table->dropForeign('acnd_inst_dept_fk');
            $table->dropColumn(['institution_department_id', 'scope_key']);
        });
    }
};

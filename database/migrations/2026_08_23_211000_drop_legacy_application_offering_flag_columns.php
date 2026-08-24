<?php

declare(strict_types=1);

use App\Services\Applications\ApplicationOfferingBackfillService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasColumn('department_levels', 'show_on_current_application_period')
            || Schema::hasColumn('department_courses', 'show_on_current_application_period')
            || Schema::hasColumn('institution_departments', 'has_apprentice_courses')
        ) {
            app(ApplicationOfferingBackfillService::class)->backfill(
                dryRun: false,
                fresh: false,
                snapshot: true,
            );
        }

        if (Schema::hasColumn('department_levels', 'show_on_current_application_period')) {
            Schema::table('department_levels', function (Blueprint $table) {
                $table->dropColumn('show_on_current_application_period');
            });
        }

        if (Schema::hasColumn('department_courses', 'show_on_current_application_period')) {
            Schema::table('department_courses', function (Blueprint $table) {
                $table->dropColumn('show_on_current_application_period');
            });
        }

        if (Schema::hasColumn('institution_departments', 'has_apprentice_courses')) {
            Schema::table('institution_departments', function (Blueprint $table) {
                $table->dropColumn('has_apprentice_courses');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('department_levels', 'show_on_current_application_period')) {
            Schema::table('department_levels', function (Blueprint $table) {
                $table->boolean('show_on_current_application_period')->default(false);
            });
        }

        if (! Schema::hasColumn('department_courses', 'show_on_current_application_period')) {
            Schema::table('department_courses', function (Blueprint $table) {
                $table->boolean('show_on_current_application_period')->default(false);
            });
        }

        if (! Schema::hasColumn('institution_departments', 'has_apprentice_courses')) {
            Schema::table('institution_departments', function (Blueprint $table) {
                $table->boolean('has_apprentice_courses')->default(false);
            });
        }
    }
};

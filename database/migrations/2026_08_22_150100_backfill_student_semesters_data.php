<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Artisan::call('enrolments:backfill-student-semesters');
    }

    public function down(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        Artisan::call('enrolments:rollback-student-semesters');
    }
};

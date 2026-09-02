<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('department_level_courses', function (Blueprint $table): void {
            $table->decimal('duration_years', 3, 1)->default(1)->change();
        });

        Schema::table('programme_structure_rollback_dlcs', function (Blueprint $table): void {
            $table->decimal('duration_years', 3, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('department_level_courses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('duration_years')->default(1)->change();
        });

        Schema::table('programme_structure_rollback_dlcs', function (Blueprint $table): void {
            $table->unsignedTinyInteger('duration_years')->nullable()->change();
        });
    }
};

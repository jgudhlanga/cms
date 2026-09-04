<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_results', function (Blueprint $table): void {
            $table->string('course_level', 64)->nullable()->after('course_code');
        });
    }

    public function down(): void
    {
        Schema::table('examination_results', function (Blueprint $table): void {
            $table->dropColumn('course_level');
        });
    }
};

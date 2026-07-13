<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_notes', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('body')->constrained('users');
            $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('student_notes', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};

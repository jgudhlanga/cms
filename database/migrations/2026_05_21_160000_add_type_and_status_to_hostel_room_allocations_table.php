<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->string('type')->default('direct')->after('student_id');
            $table->string('status')->default('active')->after('type');

            $table->index('status');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['type']);
            $table->dropColumn(['type', 'status']);
        });
    }
};

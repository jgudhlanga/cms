<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->foreignId('hostel_room_section_id')
                ->nullable()
                ->after('hostel_room_id')
                ->constrained('hostel_room_sections')
                ->nullOnDelete();

            $table->index('hostel_room_section_id');
        });
    }

    public function down(): void
    {
        Schema::table('hostel_room_allocations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('hostel_room_section_id');
        });
    }
};

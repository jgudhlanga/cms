<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_room_allocations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('hostel_room_id')->constrained();
            $table->foreignId('student_id')->constrained();
            $table->date('check_in')->nullable();
            $table->date('check_out')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['hostel_room_id', 'deleted_at']);
            $table->index(['student_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_room_allocations');
    }
};

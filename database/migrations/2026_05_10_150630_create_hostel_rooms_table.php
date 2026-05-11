<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hostel_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('hostel_id')->constrained();
            $table->string('name');
            $table->enum('room_type', ['single', 'double', 'triple', 'suite'])->default('single');
            $table->integer('capacity')->default(1);
            $table->enum('status', ['vacant', 'occupied', 'maintenance'])->default('vacant');
            $table->integer('max_occupancy');
            $table->integer('floor_number')->nullable();
            $table->text('description')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hostel_rooms');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_room_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('hostel_room_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['hostel_room_id', 'name']);
        });

        Schema::create('amenityables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hostel_amenity_id')->constrained()->cascadeOnDelete();
            $table->morphs('amenityable');
            $table->timestamps();

            $table->unique(['hostel_amenity_id', 'amenityable_id', 'amenityable_type'], 'amenityables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenityables');
        Schema::dropIfExists('hostel_room_sections');
    }
};

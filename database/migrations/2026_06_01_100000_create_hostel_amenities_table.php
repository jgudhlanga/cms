<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hostel_amenities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('hostel_room_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hostel_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hostel_amenity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['hostel_room_id', 'hostel_amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hostel_room_amenity');
        Schema::dropIfExists('hostel_amenities');
    }
};

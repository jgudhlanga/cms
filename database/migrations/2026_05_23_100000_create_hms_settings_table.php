<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->unique();
            $table->boolean('require_full_time_study')->default(true);
            $table->string('full_time_mode_name')->default('Full Time');
            $table->boolean('require_tuition_paid')->default(true);
            $table->boolean('require_accommodation_paid')->default(true);
            $table->boolean('require_address_outside_campus')->default(true);
            $table->string('campus_city')->default('Harare');
            $table->boolean('allow_guests')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_settings');
    }
};

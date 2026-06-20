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
        if (Schema::hasTable('academic_calandar_classes')) {
            if (Schema::hasTable('academic_calendar_classes')) {
                Schema::drop('academic_calendar_classes');
            }

            Schema::rename('academic_calandar_classes', 'academic_calendar_classes');

            return;
        }

        if (Schema::hasTable('academic_calendar_classes')) {
            return;
        }

        Schema::create('academic_calendar_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('class_config_id')->constrained();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_classes');
    }
};

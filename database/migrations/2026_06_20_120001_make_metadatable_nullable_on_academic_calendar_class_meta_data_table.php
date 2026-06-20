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
        Schema::table('academic_calendar_class_meta_data', function (Blueprint $table) {
            $table->string('metadatable_type')->nullable()->change();
            $table->unsignedBigInteger('metadatable_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_calendar_class_meta_data', function (Blueprint $table) {
            $table->string('metadatable_type')->nullable(false)->change();
            $table->unsignedBigInteger('metadatable_id')->nullable(false)->change();
        });
    }
};

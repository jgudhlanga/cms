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
        Schema::create('academic_calendar_class_meta_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->morphs('metadatable', 'acc_cal_class_meta_metadatable_idx');
            $table->foreignId('class_metadata_type_id')
                ->constrained('class_meta_data_types', 'id', 'acc_cal_class_meta_type_fk');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_calendar_class_meta_data');
    }
};

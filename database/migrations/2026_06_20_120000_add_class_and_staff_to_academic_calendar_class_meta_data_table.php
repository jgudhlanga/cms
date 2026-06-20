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
            $table->foreignId('academic_calendar_class_id')
                ->nullable()
                ->after('tenant_id')
                ->constrained('academic_calendar_classes', 'id', 'acc_cal_class_meta_class_fk');
            $table->foreignId('staff_id')
                ->nullable()
                ->after('academic_calendar_class_id')
                ->constrained('staff', 'id', 'acc_cal_class_meta_staff_fk');

            $table->unique(
                ['academic_calendar_class_id', 'class_metadata_type_id'],
                'acc_cal_class_meta_class_type_unique',
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('academic_calendar_class_meta_data', function (Blueprint $table) {
            $table->dropUnique('acc_cal_class_meta_class_type_unique');
            $table->dropForeign('acc_cal_class_meta_class_fk');
            $table->dropForeign('acc_cal_class_meta_staff_fk');
            $table->dropColumn(['academic_calendar_class_id', 'staff_id']);
        });
    }
};

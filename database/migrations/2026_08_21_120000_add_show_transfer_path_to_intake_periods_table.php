<?php

use App\Enums\Institution\IntakePeriodStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intake_periods', function (Blueprint $table) {
            $table->boolean('show_transfer_path')->default(false)->after('is_continuous');
        });

        DB::table('intake_periods')
            ->where('is_continuous', false)
            ->where('is_active', true)
            ->where('status', IntakePeriodStatusEnum::Open->value)
            ->update(['show_transfer_path' => true]);
    }

    public function down(): void
    {
        Schema::table('intake_periods', function (Blueprint $table) {
            $table->dropColumn('show_transfer_path');
        });
    }
};

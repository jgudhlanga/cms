<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intake_periods', function (Blueprint $table) {
            $table->string('status')->default('open')->after('is_active');
        });

        DB::table('intake_periods')
            ->where('is_active', true)
            ->update(['status' => 'open']);

        DB::table('intake_periods')
            ->where('is_active', false)
            ->update(['status' => 'closed']);
    }

    public function down(): void
    {
        Schema::table('intake_periods', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

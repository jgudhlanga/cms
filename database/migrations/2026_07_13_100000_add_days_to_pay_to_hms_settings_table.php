<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hms_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('days_to_pay')->default(7)->after('auto_allocate_rooms');
        });
    }

    public function down(): void
    {
        Schema::table('hms_settings', function (Blueprint $table): void {
            $table->dropColumn('days_to_pay');
        });
    }
};

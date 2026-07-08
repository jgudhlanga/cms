<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hms_settings', function (Blueprint $table): void {
            $table->boolean('auto_allocate_rooms')->default(false)->after('allow_guests');
        });
    }

    public function down(): void
    {
        Schema::table('hms_settings', function (Blueprint $table): void {
            $table->dropColumn('auto_allocate_rooms');
        });
    }
};

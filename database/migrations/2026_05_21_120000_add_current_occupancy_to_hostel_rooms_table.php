<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_rooms', function (Blueprint $table): void {
            $table->unsignedSmallInteger('current_occupancy')->default(0)->after('max_occupancy');
        });
    }

    public function down(): void
    {
        Schema::table('hostel_rooms', function (Blueprint $table): void {
            $table->dropColumn('current_occupancy');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_applications', function (Blueprint $table): void {
            $table->boolean('address_outside_campus_priority')->default(false)->after('eligibility_results');
        });
    }

    public function down(): void
    {
        Schema::table('hostel_applications', function (Blueprint $table): void {
            $table->dropColumn('address_outside_campus_priority');
        });
    }
};

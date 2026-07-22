<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intake_periods', function (Blueprint $table) {
            $table->boolean('is_continuous')->default(false)->after('status');
            $table->index(['is_continuous', 'is_active', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('intake_periods', function (Blueprint $table) {
            $table->dropIndex(['is_continuous', 'is_active', 'status']);
            $table->dropColumn('is_continuous');
        });
    }
};

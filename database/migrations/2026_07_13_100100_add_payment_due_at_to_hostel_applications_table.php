<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hostel_applications', function (Blueprint $table): void {
            $table->dateTime('payment_due_at')->nullable()->after('check_out');
        });
    }

    public function down(): void
    {
        Schema::table('hostel_applications', function (Blueprint $table): void {
            $table->dropColumn('payment_due_at');
        });
    }
};

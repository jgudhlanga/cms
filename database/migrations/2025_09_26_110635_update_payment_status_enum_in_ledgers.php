<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ledgers MODIFY payment_status ENUM('cancelled', 'failed', 'pending', 'paid', 'expired') NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE ledgers MODIFY payment_status ENUM('cancelled', 'failed', 'pending', 'paid') NULL");
        }
    }
};

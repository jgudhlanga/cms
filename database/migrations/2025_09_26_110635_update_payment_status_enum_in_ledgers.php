<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE ledgers MODIFY payment_status ENUM('cancelled', 'failed', 'pending', 'paid', 'expired') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ledgers MODIFY payment_status ENUM('cancelled', 'failed', 'pending', 'paid') NULL");
    }
};

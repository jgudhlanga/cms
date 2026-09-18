<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('gateway_name')->nullable();
            $table->string('gateway_base_url')->nullable();
            $table->text('gateway_api_key')->nullable();
            $table->text('gateway_secret')->nullable();
            $table->string('bank_statements_base_url')->nullable();
            $table->string('usd_account_number')->nullable();
            $table->text('usd_password')->nullable();
            $table->string('zwg_account_number')->nullable();
            $table->text('zwg_password')->nullable();
            $table->string('income_gen_account_number')->nullable();
            $table->text('income_gen_password')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};

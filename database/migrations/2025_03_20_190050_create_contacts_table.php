<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
			$table->morphs('contactable');
			$table->string('name')->nullable();
			$table->string('phone_number')->nullable();
			$table->string('alt_phone_number')->nullable();
			$table->string('email_address')->nullable();
			$table->string('alt_email_address')->nullable();
			$table->boolean('contact_is_main')->default(0);
			$table->json('meta')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};

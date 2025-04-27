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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index()->constrained();
			$table->morphs('addressable');
			$table->string('address_1')->nullable();
			$table->string('address_2')->nullable();
			$table->string('address_3')->nullable();
			$table->string('address_4')->nullable();
			$table->string('address_5')->nullable();
			$table->string('address_6')->nullable();
			$table->boolean('address_is_main')->default(0);
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
        Schema::dropIfExists('addresses');
    }
};

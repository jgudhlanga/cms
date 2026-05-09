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
        Schema::create('hostels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
			$table->string('name');
			$table->foreignId('warden_id')->nullable()->constrained('staff');
			$table->string('location')->nullable(); // North, East,West,South Campus
			$table->integer('floor_count');
			$table->integer('rooms_count');
			$table->integer('capacity');
			$table->enum('status', ['active', 'inactive'])->default('active');
			$table->enum('type', ['male', 'female', 'mixed'])->nullable()->default(null);
			$table->text('description')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hostels');
    }
};

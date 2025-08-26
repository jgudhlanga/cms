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
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('name')->unique();
            $table->string('header_line_1')->nullable();
            $table->string('header_line_2')->nullable();
            $table->string('header_address_line_1')->nullable();
            $table->string('header_address_line_2')->nullable();
            $table->string('header_telephone')->nullable();
            $table->string('header_email')->nullable();
            $table->string('header_website')->nullable();
            $table->string('header_logo_1')->nullable();
            $table->string('header_logo_2')->nullable();
            $table->text('body')->nullable();
			$table->timestamps();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};

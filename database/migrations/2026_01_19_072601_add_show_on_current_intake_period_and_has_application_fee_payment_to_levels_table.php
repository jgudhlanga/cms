<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->boolean('show_on_current_application_period')
                ->default(false)
                ->after('allowed_applications_per_level');
            $table->boolean('has_application_fee_payment')
                ->default(false)
                ->after('show_on_current_application_period');
        });
    }

    public function down(): void
    {
        Schema::table('levels', function (Blueprint $table) {
            $table->dropColumn(['show_on_current_application_period', 'has_application_fee_payment']);
        });
    }

};

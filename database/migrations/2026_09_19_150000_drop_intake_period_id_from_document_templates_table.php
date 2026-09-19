<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('document_templates', 'intake_period_id')) {
            return;
        }

        Schema::table('document_templates', function (Blueprint $table): void {
            $table->dropForeign(['intake_period_id']);
            $table->dropColumn('intake_period_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('document_templates', 'intake_period_id')) {
            return;
        }

        Schema::table('document_templates', function (Blueprint $table): void {
            $table->foreignId('intake_period_id')
                ->nullable()
                ->after('document_type_id')
                ->constrained('intake_periods')
                ->nullOnDelete();
        });
    }
};

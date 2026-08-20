<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('examination_results', function (Blueprint $table): void {
            $table->index(['tenant_id', 'session'], 'examination_results_tenant_session_index');
            $table->index(
                ['tenant_id', 'session', 'discipline', 'subject_code'],
                'examination_results_tenant_session_filters_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('examination_results', function (Blueprint $table): void {
            $table->dropIndex('examination_results_tenant_session_index');
            $table->dropIndex('examination_results_tenant_session_filters_index');
        });
    }
};

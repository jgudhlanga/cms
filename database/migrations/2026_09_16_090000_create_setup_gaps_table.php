<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per open setup problem. The nightly scan re-detects the same problem under the same
        // fingerprint, so a gap is raised once, kept fresh while it persists, and closed when it clears.
        Schema::create('setup_gaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->index();
            $table->string('check_key', 64);
            $table->foreignId('institution_department_id')
                ->nullable()
                ->constrained(indexName: 'setup_gaps_dept_fk')
                ->nullOnDelete();
            $table->string('severity', 16);
            $table->string('fingerprint', 64);
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique('fingerprint', 'setup_gaps_fingerprint_unique');
            $table->index(['tenant_id', 'resolved_at'], 'setup_gaps_tenant_open_idx');
            $table->index(['institution_department_id', 'resolved_at'], 'setup_gaps_dept_open_idx');
            $table->index(['check_key', 'resolved_at'], 'setup_gaps_check_open_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('setup_gaps');
    }
};

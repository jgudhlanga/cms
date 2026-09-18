<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('console_command_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained();
            $table->uuid('uuid')->unique();
            $table->string('command_key');
            $table->string('signature');
            $table->json('parameters')->nullable();
            $table->string('status')->index();
            $table->foreignId('queued_by_user_id')->nullable()->constrained('users');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->integer('exit_code')->nullable();
            $table->longText('output')->nullable();
            $table->boolean('output_truncated')->default(false);
            $table->text('error')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at'], 'console_runs_tenant_created_idx');
            $table->index(['tenant_id', 'status'], 'console_runs_tenant_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('console_command_runs');
    }
};

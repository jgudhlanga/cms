<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_purge_archives', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->string('purge_type');
            $table->foreignId('purged_by')->constrained('users');
            $table->foreignId('student_note_id')->nullable()->constrained('student_notes');
            $table->unsignedBigInteger('original_user_id')->nullable();
            $table->unsignedBigInteger('original_student_id')->nullable();
            $table->json('summary');
            $table->json('payload');
            $table->unsignedSmallInteger('payload_version')->default(1);
            $table->timestamp('purged_at');
            $table->timestamp('flush_after');
            $table->timestamp('flushed_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('flush_after');
            $table->index('purge_type');
            $table->index('original_student_id');
            $table->index('original_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_purge_archives');
    }
};

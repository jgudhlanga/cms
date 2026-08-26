<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(
            config('activitylog.table_name'),
            function (Blueprint $table): void {
                $table->index(['causer_type', 'causer_id', 'created_at'], 'activity_causer_created_idx');
                $table->index(['causer_type', 'causer_id', 'log_name', 'created_at'], 'activity_causer_log_created_idx');
                $table->index(['subject_type', 'subject_id', 'created_at'], 'activity_subject_created_idx');
                $table->index('event', 'activity_event_idx');
            },
        );
    }

    public function down(): void
    {
        Schema::connection(config('activitylog.database_connection'))->table(
            config('activitylog.table_name'),
            function (Blueprint $table): void {
                $table->dropIndex('activity_causer_created_idx');
                $table->dropIndex('activity_causer_log_created_idx');
                $table->dropIndex('activity_subject_created_idx');
                $table->dropIndex('activity_event_idx');
            },
        );
    }
};

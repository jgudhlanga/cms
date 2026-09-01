<?php

declare(strict_types=1);

use App\Actions\Institution\DeduplicateInstitutionDepartmentsAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $action = app(DeduplicateInstitutionDepartmentsAction::class);
        $plan = $action->plan();

        if ($plan !== []) {
            $action->execute($plan);
        }

        Schema::table('institution_departments', function (Blueprint $table): void {
            if (! Schema::hasColumn('institution_departments', 'active_department_id')) {
                $table->unsignedBigInteger('active_department_id')
                    ->nullable()
                    ->storedAs('CASE WHEN `deleted_at` IS NULL THEN `department_id` ELSE NULL END')
                    ->after('department_id');
            }

            $table->unique(
                ['tenant_id', 'active_department_id'],
                'inst_dept_tenant_active_dept_unq',
            );
        });
    }

    public function down(): void
    {
        Schema::table('institution_departments', function (Blueprint $table): void {
            $table->dropUnique('inst_dept_tenant_active_dept_unq');
            $table->dropColumn('active_department_id');
        });
    }
};

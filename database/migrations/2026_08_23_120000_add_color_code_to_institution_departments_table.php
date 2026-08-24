<?php

use App\Models\Institution\InstitutionDepartment;
use App\Support\Institution\DepartmentColorPalette;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_departments', function (Blueprint $table) {
            $table->char('color_code', 7)->nullable()->after('department_code');
        });

        InstitutionDepartment::query()
            ->withTrashed()
            ->orderBy('id')
            ->get()
            ->groupBy('tenant_id')
            ->each(function ($departments, $tenantId): void {
                $usedColors = [];

                foreach ($departments as $department) {
                    if (filled($department->color_code)) {
                        $usedColors[] = strtoupper((string) $department->color_code);

                        continue;
                    }

                    $color = DepartmentColorPalette::nextColor($usedColors);
                    $usedColors[] = $color;

                    $department->forceFill(['color_code' => $color])->saveQuietly();
                }
            });
    }

    public function down(): void
    {
        Schema::table('institution_departments', function (Blueprint $table) {
            $table->dropColumn('color_code');
        });
    }
};

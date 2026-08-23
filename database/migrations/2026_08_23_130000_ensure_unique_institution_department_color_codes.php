<?php

use App\Models\Institution\InstitutionDepartment;
use App\Support\Institution\DepartmentColorPalette;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        InstitutionDepartment::query()
            ->withTrashed()
            ->orderBy('id')
            ->get()
            ->groupBy('tenant_id')
            ->each(function ($departments): void {
                $usedColors = [];

                foreach ($departments as $department) {
                    $current = DepartmentColorPalette::normalize((string) $department->color_code);

                    if ($current !== '' && ! in_array($current, $usedColors, true)) {
                        $usedColors[] = $current;

                        if ($department->color_code !== $current) {
                            $department->forceFill(['color_code' => $current])->saveQuietly();
                        }

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
        //
    }
};

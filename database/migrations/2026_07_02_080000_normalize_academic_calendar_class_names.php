<?php

use App\Models\AcademicCalendars\AcademicCalendarClass;
use App\Services\AcademicCalendars\AcademicCalendarClassNameFormatter;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $formatter = new AcademicCalendarClassNameFormatter;

        AcademicCalendarClass::query()
            ->with([
                'classConfig.departmentLevel.level',
                'classConfig.modeOfStudy',
            ])
            ->orderBy('id')
            ->each(function (AcademicCalendarClass $class) use ($formatter): void {
                $levelName = trim((string) ($class->classConfig?->departmentLevel?->level?->name ?? ''));

                if ($levelName === '') {
                    return;
                }

                $modeName = $class->classConfig?->modeOfStudy?->name;
                $classNumber = $formatter->extractClassNumber((string) $class->name, $levelName, $modeName);

                if ($classNumber === null) {
                    return;
                }

                $newName = $formatter->format($levelName, $modeName, $classNumber);

                if ($newName !== $class->name) {
                    $class->update(['name' => $newName]);
                }
            });
    }

    public function down(): void
    {
        // Names are not reversible without storing the previous values.
    }
};

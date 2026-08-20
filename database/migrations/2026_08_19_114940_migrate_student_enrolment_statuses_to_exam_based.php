<?php

declare(strict_types=1);

use App\Models\Students\StudentEnrolmentStatus;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $renames = [
            'Completed' => ['name' => 'Award', 'slug' => 'award', 'description' => 'The student has been awarded the qualification for this semester/phase.'],
            'Repeat/Re-write' => ['name' => 'Referred', 'slug' => 'referred', 'description' => 'The student has been referred and must retake one or more modules.'],
            'Deferred/Postponed' => ['name' => 'Deferred', 'slug' => 'deferred', 'description' => 'The student has deferred their studies to a later session.'],
        ];

        foreach ($renames as $oldName => $newData) {
            StudentEnrolmentStatus::withTrashed()
                ->where('name', $oldName)
                ->update($newData);
        }

        $newStatuses = [
            ['name' => 'Absent', 'slug' => 'absent', 'description' => 'The student was absent from the examination session.'],
            ['name' => 'Disqualified', 'slug' => 'disqualified', 'description' => 'The student has been disqualified from this examination session.'],
            ['name' => 'Proceed', 'slug' => 'proceed', 'description' => 'The student has passed and may proceed to the next semester/phase.'],
        ];

        foreach ($newStatuses as $status) {
            StudentEnrolmentStatus::query()->firstOrCreate(
                ['slug' => $status['slug']],
                ['name' => $status['name'], 'description' => $status['description']],
            );
        }
    }

    public function down(): void
    {
        $restores = [
            'Award' => ['name' => 'Completed', 'slug' => 'completed', 'description' => 'The student has finished the last phase of this level (full award).'],
            'Referred' => ['name' => 'Repeat/Re-write', 'slug' => 'repeatre-write', 'description' => 'The student failed one or more modules in a previous session and is retaking them.'],
            'Deferred' => ['name' => 'Deferred/Postponed', 'slug' => 'deferredpostponed', 'description' => 'The student has postponed their studies to a later session.'],
        ];

        foreach ($restores as $currentName => $oldData) {
            StudentEnrolmentStatus::withTrashed()
                ->where('name', $currentName)
                ->update($oldData);
        }

        StudentEnrolmentStatus::withTrashed()
            ->whereIn('slug', ['absent', 'disqualified', 'proceed'])
            ->forceDelete();
    }
};

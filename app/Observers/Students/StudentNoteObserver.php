<?php

declare(strict_types=1);

namespace App\Observers\Students;

use App\Models\Students\StudentNote;
use Illuminate\Support\Facades\Auth;

class StudentNoteObserver
{
    public function creating(StudentNote $note): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $note->created_by ??= $userId;
        $note->updated_by ??= $userId;
    }

    public function updating(StudentNote $note): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $note->updated_by = $userId;
    }
}

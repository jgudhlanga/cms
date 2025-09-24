<?php

namespace App\Services;


use App\Enums\Shared\GenderEnum;
use App\Models\Shared\Gender;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;

class ApplicationMetricsService
{
    protected int $intakePeriodId;

    public function __construct(int $intakePeriodId)
    {
        $this->intakePeriodId = $intakePeriodId;
    }

    /**
     * Count total applications (with optional gender filter).
     */
    public function applications(?GenderEnum $gender = null): int
    {
        $query = StudentProgram::query()
            ->where('student_programs.intake_period_id', $this->intakePeriodId);

        if ($gender) {
            $genderId = Gender::where('title', $gender->label())->value('id');

            $query->whereHas('student', fn($q) => $q->where('gender_id', $genderId)
            );
        }

        return $query->count();
    }

    public function users(): int
    {
        $query = User::query()->role('student');
        return $query->count();
    }

    /**
     * Shortcut: total applications.
     */
    public function total(): int
    {
        return $this->applications();
    }

    /**
     * Shortcut: male applications.
     */
    public function male(): int
    {
        return $this->applications(GenderEnum::MALE);
    }

    /**
     * Shortcut: female applications.
     */
    public function female(): int
    {
        return $this->applications(GenderEnum::FEMALE);
    }
}

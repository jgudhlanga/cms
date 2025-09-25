<?php

namespace App\Services;

use App\Enums\Shared\GenderEnum;
use App\Models\Shared\Gender;
use App\Models\Students\StudentProgram;
use App\Models\Users\User;
use Carbon\Carbon;

class ApplicationMetricsService
{
    protected Carbon $startDate;
    protected Carbon $endDate;

    public function __construct(string $startDate, ?string $endDate = null)
    {
        $this->startDate = Carbon::parse($startDate)->startOfDay();
        $this->endDate = $endDate
            ? Carbon::parse($endDate)->endOfDay()
            : $this->startDate->copy()->endOfDay();
    }

    /**
     * Count total applications (with optional gender filter).
     */
    public function applications(?GenderEnum $gender = null): int
    {
        $query = StudentProgram::query()
            ->whereBetween('student_programs.created_at', [$this->startDate, $this->endDate]);

        if ($gender) {
            $genderId = Gender::where('title', $gender->label())->value('id');

            $query->whereHas('student', fn($q) => $q->where('gender_id', $genderId)
            );
        }

        return $query->count();
    }

    /**
     * Count total users (students only).
     */
    public function users(): int
    {
        return User::query()
            ->role('student')
            ->whereBetween('users.created_at', [$this->startDate, $this->endDate])
            ->count();
    }

    public function total(): int
    {
        return $this->applications();
    }

    public function male(): int
    {
        return $this->applications(GenderEnum::MALE);
    }

    public function female(): int
    {
        return $this->applications(GenderEnum::FEMALE);
    }
}

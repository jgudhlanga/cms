<?php

declare(strict_types=1);

namespace App\Support\Maintenance;

use App\Enums\Shared\DisabilityStatusEnum;
use App\Enums\Shared\GenderEnum;
use Illuminate\Database\Eloquent\Builder;

/**
 * Applies the student-level filters used on the student index to any query
 * scoped to the `students` table.
 */
final class StudentAttributeFilterApplier
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function apply(Builder $query, array $filters): Builder
    {
        $this->applySearch($query, $filters['search'] ?? null);
        $this->applyGender($query, $filters['gender'] ?? null);
        $this->applyStudentType($query, $filters['student_type'] ?? null);
        $this->applySponsored($query, $filters['sponsored'] ?? null);
        $this->applyDisability($query, $filters['disability'] ?? null);

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function hasFilters(array $filters): bool
    {
        foreach (['search', 'gender', 'student_type', 'sponsored', 'disability'] as $key) {
            if (($filters[$key] ?? null) !== null) {
                return true;
            }
        }

        return false;
    }

    private function applySearch(Builder $query, mixed $search): void
    {
        if (! is_string($search) || trim($search) === '') {
            return;
        }

        $term = '%'.trim($search).'%';

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('students.student_number', 'like', $term)
                ->orWhere('students.id_number', 'like', $term)
                ->orWhere('students.passport_number', 'like', $term)
                ->orWhereHas('user', function (Builder $user) use ($term): void {
                    $user->where('first_name', 'like', $term)
                        ->orWhere('middle_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
        });
    }

    private function applyGender(Builder $query, mixed $gender): void
    {
        $gender = is_string($gender) ? strtolower(trim($gender)) : '';

        if (! in_array($gender, ['male', 'female'], true)) {
            return;
        }

        $title = $gender === 'male' ? GenderEnum::MALE->value : GenderEnum::FEMALE->value;

        $query->whereHas('gender', fn (Builder $genderQuery) => $genderQuery->where('title', $title));
    }

    private function applyStudentType(Builder $query, mixed $studentType): void
    {
        $studentType = is_string($studentType) ? strtolower(trim($studentType)) : '';

        if ($studentType === 'apprentice') {
            $query->whereHas('apprentices');
        } elseif ($studentType === 'direct') {
            $query->whereDoesntHave('apprentices');
        }
    }

    private function applySponsored(Builder $query, mixed $sponsored): void
    {
        $sponsored = is_string($sponsored) ? strtolower(trim($sponsored)) : '';

        if ($sponsored === 'sponsored') {
            $query->whereHas('studentSponsors');
        } elseif ($sponsored === 'not_sponsored') {
            $query->whereDoesntHave('studentSponsors');
        }
    }

    private function applyDisability(Builder $query, mixed $disability): void
    {
        $disability = is_string($disability) ? strtolower(trim($disability)) : '';

        if ($disability === 'yes') {
            $query->where('students.disability_status', DisabilityStatusEnum::YES->value);
        } elseif ($disability === 'no') {
            $query->where(function (Builder $builder): void {
                $builder->whereNull('students.disability_status')
                    ->orWhere('students.disability_status', '!=', DisabilityStatusEnum::YES->value);
            });
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Queries\Maintenance;

use App\Models\Students\Student;
use App\Rules\ZimbabweanIdNumber;
use Illuminate\Database\Eloquent\Builder;

class FaultyStudentIdNumbersQuery
{
    public function baseQuery(): Builder
    {
        $query = Student::query()
            ->whereNotNull('id_number')
            ->with('user');

        return $this->applyInvalidIdNumberConstraint($query)->orderBy('id');
    }

    public function lightweightBaseQuery(): Builder
    {
        $query = Student::query()->whereNotNull('id_number');

        return $this->applyInvalidIdNumberConstraint($query);
    }

    public function count(): int
    {
        return $this->baseQuery()->count();
    }

    public function applySearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = '%'.trim($search).'%';

        return $query->where(function (Builder $builder) use ($term): void {
            $builder->where('student_number', 'like', $term)
                ->orWhere('id_number', 'like', $term)
                ->orWhereHas('user', function (Builder $user) use ($term): void {
                    $user->where('first_name', 'like', $term)
                        ->orWhere('middle_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
        });
    }

    private function applyInvalidIdNumberConstraint(Builder $query): Builder
    {
        if ($query->getConnection()->getDriverName() === 'mysql') {
            return $query->whereRaw("TRIM(id_number) NOT REGEXP '^[0-9]{2}-[0-9]{5,7}[A-Za-z][0-9]{2}$'");
        }

        $invalidStudentIds = Student::query()
            ->whereNotNull('id_number')
            ->pluck('id_number', 'id')
            ->filter(static fn (?string $idNumber): bool => ! ZimbabweanIdNumber::isValid($idNumber))
            ->keys()
            ->all();

        if ($invalidStudentIds === []) {
            return $query->whereRaw('0 = 1');
        }

        return $query->whereIn('id', $invalidStudentIds);
    }
}

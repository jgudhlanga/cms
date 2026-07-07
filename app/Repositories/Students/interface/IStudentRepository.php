<?php

namespace App\Repositories\Students\interface;

use App\DTO\Students\CreateApplicationDto;
use App\DTO\Students\CreateStudentApplicationDto;
use App\DTO\Students\UpdateStudentDto;
use App\Http\Filters\Students\StudentFilter;
use App\Models\Students\Student;
use App\Models\Students\StudentApplication;
use App\Repositories\Base\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

interface IStudentRepository extends IBaseRepository
{
    public function create(CreateApplicationDto|CreateStudentApplicationDto $dto);

    public function applyReturningApplication(Student $student, CreateApplicationDto $dto): StudentApplication;

    public function update(Student $student, UpdateStudentDto $dto);

    public function allFilter($columns = ['*'], ?StudentFilter $filters = null);

    public function paginateForIndex(array $filters = []): LengthAwarePaginator;

    /**
     * @return array{global: array{total: int, male: int, female: int, byLevel: list<array{id: int, name: string, count: int}>, byModeOfStudy: list<array{id: int, name: string, count: int}>}, filtered: array{total: int}}
     */
    public function statsForIndex(array $filters = []): array;

    public function queryForExport(array $filters = []): Builder;
}

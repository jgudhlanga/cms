<?php

declare(strict_types=1);

namespace App\Services\Maintenance\Students;

use App\Models\Students\Student;
use App\Queries\Maintenance\FaultyStudentIdNumbersQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class FaultyStudentIdNumbersService
{
    public function __construct(
        protected FaultyStudentIdNumbersQuery $query,
        protected FaultyStudentIdNumberAnalysis $analysis,
    ) {}

    /**
     * @param  array{search?: string|null}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $builder = $this->query->lightweightBaseQuery();

        $search = $filters['search'] ?? null;
        if (is_string($search)) {
            $builder = $this->query->applySearch($builder, $search);
        }

        /** @var Collection<int, Student> $faultyStudents */
        $faultyStudents = $builder
            ->select(['students.id', 'students.id_number'])
            ->toBase()
            ->get()
            ->map(static fn ($row): Student => (new Student)->forceFill([
                'id' => (int) $row->id,
                'id_number' => (string) $row->id_number,
            ]));

        $ownerMap = $this->analysis->buildConflictOwnerMap($faultyStudents);

        $ranked = $faultyStudents
            ->map(function (Student $student) use ($ownerMap): array {
                $analysis = $this->analysis->analyze($student, $ownerMap);

                return [
                    'id' => $student->id,
                    'analysis' => $analysis,
                    'priority' => $analysis['rectificationPriority'],
                ];
            })
            ->sortBy([
                ['priority', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        $page = max(1, (int) request()->input('page', 1));
        $perPage = $this->resolvePerPage();
        $total = $ranked->count();
        $pageRows = $ranked->forPage($page, $perPage)->values();
        $pageIds = $pageRows->pluck('id')->all();

        if ($pageIds === []) {
            return (new Paginator(
                collect(),
                $total,
                $perPage,
                $page,
                [
                    'path' => request()->url(),
                    'query' => request()->query(),
                ],
            ))->withQueryString();
        }

        /** @var Collection<int, Student> $students */
        $students = Student::query()
            ->with('user')
            ->whereIn('id', $pageIds)
            ->get()
            ->keyBy('id');

        $items = $pageRows
            ->map(function (array $row) use ($students): ?Student {
                $student = $students->get($row['id']);

                if ($student === null) {
                    return null;
                }

                $student->faultyIdAnalysis = $row['analysis'];

                return $student;
            })
            ->filter()
            ->values();

        return (new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ],
        ))->withQueryString();
    }

    private function resolvePerPage(): int
    {
        $pageSize = request()->input('page_size', config('custom.system.pagination_items_per_page', 15));

        if ($pageSize === 'all') {
            return (int) config('custom.system.pagination_max_limit', 1000);
        }

        return max(1, min((int) $pageSize, (int) config('custom.system.pagination_max_limit', 1000)));
    }
}

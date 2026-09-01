<?php

namespace App\Services\Dashboard;

use App\Repositories\Students\interface\IStudentRepository;

class StudentDashboardMetricsService
{
    /** @var array<string, mixed>|null */
    private ?array $cachedBreakdown = null;

    public function __construct(
        private readonly IStudentRepository $studentRepository,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     male: int,
     *     female: int,
     *     byLevel: list<array{id: int, name: string, count: int}>,
     *     byModeOfStudy: list<array{id: int, name: string, count: int}>,
     *     byStudentType: list<array{id: string, name: string, count: int}>,
     *     bySponsored: list<array{id: string, name: string, count: int}>,
     *     byDisability: list<array{id: string, name: string, count: int}>
     * }
     */
    public function breakdown(): array
    {
        if ($this->cachedBreakdown !== null) {
            return $this->cachedBreakdown;
        }

        $stats = $this->studentRepository->statsForIndex([]);

        return $this->cachedBreakdown = $stats['global'];
    }
}

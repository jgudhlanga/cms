<?php

namespace App\Services\Integrations\Banks\ZB;

use Carbon\CarbonImmutable;

class StatementFetchWindowGenerator
{
    /**
     * Build non-overlapping inclusive date windows from start through end (calendar dates in app timezone).
     *
     * @return list<array{start:string,end:string}> Y-m-d bounds, inclusive for API requests
     */
    public function windowsBetween(CarbonImmutable $startInclusive, CarbonImmutable $endInclusive, ?int $chunkDays = null): array
    {
        $chunkDays = $chunkDays ?? max(1, (int) config('custom.bank-statements.chunk_days', 14));
        $timezone = (string) config('app.timezone');

        $start = $startInclusive->setTimezone($timezone)->startOfDay();
        $end = $endInclusive->setTimezone($timezone)->startOfDay();

        if ($end->lt($start)) {
            return [];
        }

        $windows = [];
        $cursor = $start;

        while ($cursor->lte($end)) {
            $windowEnd = $cursor->addDays($chunkDays - 1);
            if ($windowEnd->gt($end)) {
                $windowEnd = $end;
            }

            $windows[] = [
                'start' => $cursor->toDateString(),
                'end' => $windowEnd->toDateString(),
            ];

            $cursor = $windowEnd->addDay();
        }

        return $windows;
    }
}

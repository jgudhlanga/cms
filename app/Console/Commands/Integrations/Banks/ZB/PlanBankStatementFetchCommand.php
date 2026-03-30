<?php

namespace App\Console\Commands\Integrations\Banks\ZB;

use App\Enums\Integrations\Banks\ZBBankStatementFetchWindowStatus;
use App\Services\Integrations\Banks\ZB\StatementFetchWindowGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlanBankStatementFetchCommand extends Command
{
    protected $signature = 'statements:plan-fetch-windows';

    protected $description = 'Insert pending bank statement fetch windows from the plan anchor through today (chunk_days slices); skips existing rows (insertOrIgnore)';

    public function handle(StatementFetchWindowGenerator $generator): int
    {
        $timezone = (string) config('app.timezone');
        $anchor = (string) config('custom.bank-statements.plan_anchor_start', '2025-11-01');
        $start = CarbonImmutable::parse($anchor, $timezone)->startOfDay();
        $end = CarbonImmutable::now($timezone)->startOfDay();

        $windows = $generator->windowsBetween($start, $end);
        if ($windows === []) {
            $this->warn('No windows generated (check plan anchor vs today in app timezone).');

            return self::SUCCESS;
        }

        $chunkDays = max(1, (int) config('custom.bank-statements.chunk_days', 14));
        $windows = $this->extendFinalWindowIfShorterThanChunk($windows, $timezone, $chunkDays);

        $accountTypes = $this->accountTypes();
        $chunkSize = max(100, (int) config('custom.bank-statements.plan_insert_chunk', 500));
        $now = now();
        $rows = [];

        foreach ($accountTypes as $accountType) {
            foreach ($windows as $window) {
                $rows[] = [
                    'account_type' => $accountType,
                    'window_start' => Carbon::parse($window['start'], $timezone)->startOfDay()->toDateTimeString(),
                    'window_end' => Carbon::parse($window['end'], $timezone)->startOfDay()->toDateTimeString(),
                    'status' => ZBBankStatementFetchWindowStatus::Pending->value,
                    'attempt_count' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            DB::table('zb_bank_statement_fetch_windows')->insertOrIgnore($chunk);
        }

        $this->info(sprintf(
            'Planned up to %d window row(s) across %d account type(s) (%d slice(s) each); existing rows were left unchanged (insertOrIgnore).',
            count($rows),
            count($accountTypes),
            count($windows),
        ));

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function accountTypes(): array
    {
        /** @var list<string>|mixed $types */
        $types = config('custom.bank-statements.account_types', ['usd', 'zwg', 'income-gen']);

        if (! is_array($types)) {
            return ['usd', 'zwg', 'income-gen'];
        }

        return array_values(array_map(static fn (mixed $t): string => (string) $t, $types));
    }

    /**
     * @param  list<array{start:string,end:string}>  $windows
     * @return list<array{start:string,end:string}>
     */
    private function extendFinalWindowIfShorterThanChunk(array $windows, string $timezone, int $chunkDays): array
    {
        $lastIndex = count($windows) - 1;
        $last = $windows[$lastIndex];
        $start = CarbonImmutable::parse($last['start'], $timezone)->startOfDay();
        $end = CarbonImmutable::parse($last['end'], $timezone)->startOfDay();
        $inclusiveDays = (int) $start->diffInDays($end) + 1;

        if ($inclusiveDays < $chunkDays) {
            $windows[$lastIndex] = [
                'start' => $last['start'],
                'end' => $end->toDateString(),
            ];
        }

        return $windows;
    }
}

<?php

namespace App\Console\Commands\Performance;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

class PerformanceDiagnoseCommand extends Command
{
    protected $signature = 'performance:diagnose
        {--json : Output raw JSON instead of a table}';

    protected $description = 'Report production performance readiness (cache/session/queue, OPcache, debug flags, scale signals)';

    public function handle(): int
    {
        $report = [
            'app_env' => config('app.env'),
            'app_debug' => (bool) config('app.debug'),
            'log_level' => config('logging.channels.single.level', config('logging.default')),
            'cache_store' => config('cache.default'),
            'session_driver' => config('session.driver'),
            'queue_connection' => config('queue.default'),
            'opcache_enabled' => $this->opcacheEnabled(),
            'config_cached' => app()->configurationIsCached(),
            'routes_cached' => app()->routesAreCached(),
            'events_cached' => app()->eventsAreCached(),
            'redis_reachable' => $this->redisReachable(),
            'database_reachable' => $this->databaseReachable(),
            'php_memory_limit' => ini_get('memory_limit'),
            'recommendations' => [],
            'scale_gate' => [],
        ];

        $report['recommendations'] = $this->recommendations($report);
        $report['scale_gate'] = $this->scaleGateGuidance($report);

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info('Performance diagnose');
        $this->newLine();
        $this->table(
            ['Check', 'Value'],
            collect($report)
                ->except(['recommendations', 'scale_gate'])
                ->map(fn (mixed $value, string $key): array => [
                    $key,
                    is_bool($value) ? ($value ? 'yes' : 'no') : (string) $value,
                ])
                ->values()
                ->all()
        );

        if ($report['recommendations'] !== []) {
            $this->newLine();
            $this->warn('Recommendations:');
            foreach ($report['recommendations'] as $recommendation) {
                $this->line(' - '.$recommendation);
            }
        }

        $this->newLine();
        $this->info('Scale gate (after query + Redis fixes):');
        foreach ($report['scale_gate'] as $line) {
            $this->line(' - '.$line);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<string>
     */
    private function recommendations(array $report): array
    {
        $items = [];

        if ($report['app_debug']) {
            $items[] = 'Set APP_DEBUG=false in production.';
        }

        if (in_array($report['cache_store'], ['database', 'file', 'array'], true)) {
            $items[] = 'Prefer CACHE_STORE=redis so cache does not compete with app MySQL traffic.';
        }

        if (in_array($report['session_driver'], ['database', 'file'], true)) {
            $items[] = 'Prefer SESSION_DRIVER=redis for lower MySQL contention.';
        }

        if ($report['queue_connection'] === 'database') {
            $items[] = 'Consider QUEUE_CONNECTION=redis once Redis is available (optional after cache/session).';
        }

        if (! $report['opcache_enabled']) {
            $items[] = 'Enable PHP OPcache in PHP-FPM.';
        }

        if (! $report['config_cached'] || ! $report['routes_cached'] || ! $report['events_cached']) {
            $items[] = 'Run: php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache';
        }

        if (! $report['redis_reachable'] && in_array($report['cache_store'], ['redis'], true)) {
            $items[] = 'CACHE_STORE is redis but Redis is unreachable — fix REDIS_* or install Redis.';
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $report
     * @return list<string>
     */
    private function scaleGateGuidance(array $report): array
    {
        return [
            'Stay on Linode 8GB if dashboards are fast after Redis + query fixes and CPU/RAM stay moderate.',
            'Upgrade RAM / split MySQL when PHP and MySQL fight for memory after Redis is in use.',
            'Add vCPU when PHP-FPM is pegged but slow-query log is clean.',
            'Current stack signals: cache='.$report['cache_store'].', session='.$report['session_driver'].', debug='.($report['app_debug'] ? 'on' : 'off').'.',
            'Do not scale first — finish application query optimizations and Redis before upsizing.',
        ];
    }

    private function opcacheEnabled(): bool
    {
        return function_exists('opcache_get_status')
            && is_array(opcache_get_status(false))
            && (bool) (opcache_get_status(false)['opcache_enabled'] ?? false);
    }

    private function redisReachable(): bool
    {
        try {
            Redis::connection()->ping();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

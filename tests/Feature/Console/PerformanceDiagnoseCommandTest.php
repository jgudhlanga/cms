<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

it('reports performance diagnose checks', function (): void {
    $exitCode = Artisan::call('performance:diagnose', ['--json' => true]);
    $output = Artisan::output();

    expect($exitCode)->toBe(0)
        ->and($output)->toContain('cache_store')
        ->and($output)->toContain('scale_gate')
        ->and($output)->toContain('recommendations');
});

it('recommends redis and disabling debug when using database cache with debug on', function (): void {
    config([
        'app.debug' => true,
        'cache.default' => 'database',
        'session.driver' => 'database',
        'queue.default' => 'database',
    ]);

    Artisan::call('performance:diagnose', ['--json' => true]);
    $payload = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

    expect($payload['app_debug'])->toBeTrue()
        ->and($payload['cache_store'])->toBe('database')
        ->and($payload['recommendations'])->toContain('Set APP_DEBUG=false in production.')
        ->and(collect($payload['recommendations'])->first(
            fn (string $line): bool => str_contains($line, 'CACHE_STORE=redis')
        ))->not->toBeNull()
        ->and(collect($payload['recommendations'])->first(
            fn (string $line): bool => str_contains($line, 'SESSION_DRIVER=redis')
        ))->not->toBeNull()
        ->and($payload['scale_gate'])->not->toBeEmpty();
});

it('has performance composite indexes after migrations', function (): void {
    if (Schema::getConnection()->getDriverName() === 'sqlite') {
        // SQLite still creates the indexes; assert via schema manager when available.
        $enrolmentIndexes = collect(Schema::getIndexes('student_enrolments'))->pluck('name');
        $applicationIndexes = collect(Schema::getIndexes('student_applications'))->pluck('name');

        expect($enrolmentIndexes)->toContain('student_enrolments_calendar_deleted_index')
            ->and($enrolmentIndexes)->toContain('student_enrolments_calendar_mode_index')
            ->and($enrolmentIndexes)->toContain('student_enrolments_student_calendar_index')
            ->and($applicationIndexes)->toContain('student_applications_class_list_filter_index');

        return;
    }

    $enrolmentIndexes = collect(Schema::getIndexes('student_enrolments'))->pluck('name');
    $applicationIndexes = collect(Schema::getIndexes('student_applications'))->pluck('name');

    expect($enrolmentIndexes)->toContain('student_enrolments_calendar_deleted_index')
        ->and($applicationIndexes)->toContain('student_applications_class_list_filter_index');
});

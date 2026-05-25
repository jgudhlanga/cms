<?php

use Illuminate\Support\Facades\DB;

it('passes when pending jobs are on expected queues', function () {
    DB::table('jobs')->insert([
        [
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ],
        [
            'queue' => 'bank-statements',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => now()->timestamp,
            'created_at' => now()->timestamp,
        ],
    ]);

    $this->artisan('queue:health')
        ->expectsOutputToContain('Queue health check passed.')
        ->assertSuccessful();
});

it('fails when pending jobs are on unmonitored queues', function () {
    DB::table('jobs')->insert([
        'queue' => 'payments',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    $this->artisan('queue:health')
        ->expectsOutputToContain('Pending jobs exist on queue(s) not listed in expected worker queues: payments')
        ->assertFailed();
});

it('accepts expected queues via option', function () {
    DB::table('jobs')->insert([
        'queue' => 'payments',
        'payload' => '{}',
        'attempts' => 0,
        'reserved_at' => null,
        'available_at' => now()->timestamp,
        'created_at' => now()->timestamp,
    ]);

    $this->artisan('queue:health', [
        '--queues' => 'default,payments',
    ])->expectsOutputToContain('Queue health check passed.')
        ->assertSuccessful();
});

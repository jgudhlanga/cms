<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;

/**
 * @mixin MakesHttpRequests
 * @mixin InteractsWithDatabase
 *
 * @property string $password
 *
 * @method \Illuminate\Testing\TestResponse get(string $uri, array $headers = [])
 * @method \Illuminate\Testing\TestResponse postJson(string $uri, array $data = [], array $headers = [])
 * @method void assertDatabaseHas(string $table, array $data)
 */
abstract class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests;
}

<?php

use App\Enums\Acl\RoleEnum;
use App\Models\Acl\Role;
use App\Models\Users\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

$password = 'password';

test('api login returns token and user on success', function () use ($password) {
    /** @var TestCase $this */
    $user = User::factory()->create(['password' => $password]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => $password,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'invalidCredentials' => false,
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'user' => [
                    'type',
                    'id',
                    'attributes' => [
                        'email',
                        'name',
                    ],
                ],
            ],
        ]);

    expect($response->json('data.token'))->not->toBeNull();
});

test('api login returns invalid credentials on failure', function () use ($password) {
    /** @var TestCase $this */
    $user = User::factory()->create(['password' => $password]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => false,
            'data' => [
                'token' => null,
                'invalidCredentials' => true,
                'user' => null,
            ],
        ]);
});

test('api register returns token and user on success', function () use ($password) {
    /** @var TestCase $this */
    Role::query()->firstOrCreate(
        ['name' => RoleEnum::STUDENT->value],
        [
            'slug' => Str::slug(RoleEnum::STUDENT->value),
            'guard_name' => 'web',
        ]
    );

    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'newuser@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'data' => [
                'invalidCredentials' => false,
            ],
        ])
        ->assertJsonPath('data.user.attributes.email', 'newuser@example.com')
        ->assertJsonStructure([
            'data' => [
                'token',
                'user',
            ],
        ]);

    expect($response->json('data.token'))->not->toBeNull();
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});

test('api register returns validation error for duplicate email', function () use ($password) {
    /** @var TestCase $this */
    User::factory()->create(['email' => 'existing@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'existing@example.com',
        'password' => $password,
        'password_confirmation' => $password,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('api register returns validation error for missing required fields', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/auth/register', [
        'first_name' => 'Test',
        // missing last_name, email, password
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['last_name', 'email', 'password']);
});

test('api logout returns success when authenticated', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertOk()
        ->assertJson(['success' => true]);
});

test('api logout returns 401 when unauthenticated', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});

test('api forgot password returns success when email exists', function () {
    /** @var TestCase $this */
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure(['message']);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('api forgot password returns validation error for invalid email', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('api forgot password returns json response for non-existent email', function () {
    /** @var TestCase $this */
    Notification::fake();

    $response = $this->postJson('/api/v1/auth/forgot-password', [
        'email' => 'doesnotexist@example.com',
    ]);

    // Laravel returns 422 with validation message for invalid user (does not leak existence)
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

<?php

use App\Models\Users\User;
use Illuminate\Support\Str;

/**
 * @param  array<string, mixed>  $data
 */
function bellNotification(User $user, array $data, ?string $readAt = null): string
{
    $id = (string) Str::uuid();

    $user->notifications()->create([
        'id' => $id,
        'type' => 'App\\Notifications\\Assessments\\MissingMarksNotification',
        'data' => $data,
        'read_at' => $readAt,
    ]);

    return $id;
}

test('the bell lists the signed-in user notifications and drops links to other sites', function () {
    $user = User::factory()->create();
    bellNotification($user, ['title' => 'Safe link', 'body' => 'Body', 'url' => '/institution/course-work-extensions']);
    bellNotification($user, ['title' => 'Unsafe link', 'url' => 'https://phishing.example/login']);
    bellNotification($user, ['title' => 'Protocol relative', 'url' => '//phishing.example/login'], now()->toDateTimeString());

    $response = $this->actingAs($user)->getJson(route('notifications.index'))->assertSuccessful();

    $byTitle = collect($response->json('notifications'))->keyBy('title');

    expect($response->json('unreadCount'))->toBe(2)
        ->and($byTitle['Safe link']['url'])->toBe('/institution/course-work-extensions')
        ->and($byTitle['Unsafe link']['url'])->toBeNull()
        ->and($byTitle['Protocol relative']['url'])->toBeNull()
        ->and($byTitle['Protocol relative']['readAt'])->not->toBeNull();
});

test('the bell can poll the unread count alone', function () {
    $user = User::factory()->create();
    bellNotification($user, ['title' => 'One']);

    $this->actingAs($user)
        ->getJson(route('notifications.index', ['count_only' => 1]))
        ->assertSuccessful()
        ->assertExactJson(['unreadCount' => 1]);
});

test('notifications can be marked read one at a time or all together', function () {
    $user = User::factory()->create();
    $first = bellNotification($user, ['title' => 'First']);
    bellNotification($user, ['title' => 'Second']);

    $this->actingAs($user)
        ->postJson(route('notifications.read', ['notification' => $first]))
        ->assertSuccessful()
        ->assertJsonPath('unreadCount', 1);

    $this->actingAs($user)
        ->postJson(route('notifications.read-all'))
        ->assertSuccessful()
        ->assertJsonPath('unreadCount', 0);

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('another user notification cannot be marked read', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $notificationId = bellNotification($owner, ['title' => 'Private']);

    $this->actingAs($intruder)
        ->postJson(route('notifications.read', ['notification' => $notificationId]))
        ->assertNotFound();

    expect($owner->unreadNotifications()->count())->toBe(1);
});

test('guests cannot read notifications', function () {
    $this->getJson(route('notifications.index'))->assertUnauthorized();
});

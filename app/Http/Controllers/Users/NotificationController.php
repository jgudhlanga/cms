<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

/**
 * The signed-in user's in-app notifications (Laravel's database channel), for the header bell.
 */
class NotificationController extends Controller
{
    private const int LIST_LIMIT = 20;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $unreadCount = $user->unreadNotifications()->count();

        if ($request->boolean('count_only')) {
            return response()->json(['unreadCount' => $unreadCount]);
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit(self::LIST_LIMIT)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => [
                'id' => (string) $notification->id,
                'kind' => (string) ($notification->data['kind'] ?? ''),
                'title' => (string) ($notification->data['title'] ?? Str::headline(class_basename((string) $notification->type))),
                'body' => (string) ($notification->data['body'] ?? ''),
                'url' => $this->sameSiteUrl($notification->data['url'] ?? null),
                'readAt' => $notification->read_at?->toIso8601String(),
                'createdAt' => $notification->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return response()->json([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, string $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->notifications()->whereKey($notification)->firstOrFail()->markAsRead();

        return response()->json(['unreadCount' => $user->unreadNotifications()->count()]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['unreadCount' => 0]);
    }

    /**
     * Notification links are only followed within the application, never to another host.
     */
    private function sameSiteUrl(mixed $url): ?string
    {
        if (! is_string($url) || $url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        $applicationUrl = rtrim(url('/'), '/');

        return $url === $applicationUrl || str_starts_with($url, $applicationUrl.'/') ? $url : null;
    }
}

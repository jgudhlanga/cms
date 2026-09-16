<?php

declare(strict_types=1);

namespace App\Notifications\Setup;

use App\Models\Setup\SetupGap;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * In-app only, in every environment including production: setup problems are chased through the header
 * alert, never by email.
 *
 * Do not add 'mail' to via(). Email is planned to become a per-institution setting on a future
 * notification config page; until that setting exists, the database channel is the whole delivery.
 */
class SetupGapsDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Collection<int, SetupGap>  $gaps
     */
    public function __construct(public Collection $gaps) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $count = $this->gaps->count();
        $first = $this->gaps->first();

        return [
            'kind' => 'setup_gap',
            'severity' => $this->gaps
                ->sortByDesc(fn (SetupGap $gap): int => $gap->severity->weight())
                ->first()?->severity->value,
            'gapIds' => $this->gaps->pluck('id')->all(),
            'checks' => $this->gaps->pluck('check_key')->map(fn ($check): string => $check->value)->unique()->values()->all(),
            'title' => $count === 1
                ? __('setup_gaps.notification_title', ['check' => $first?->check_key->label() ?? ''])
                : __('setup_gaps.notification_title_many', ['count' => $count]),
            'body' => $count === 1
                ? __('setup_gaps.notification_body', ['title' => $first?->title ?? ''])
                : __('setup_gaps.notification_body_many', ['count' => $count]),
            // One gap links straight to the screen that fixes it; a batch has no single destination, so
            // the header alert is where it gets opened.
            'url' => $count === 1 && is_string($first?->url) && $first->url !== '' ? $first->url : null,
        ];
    }
}

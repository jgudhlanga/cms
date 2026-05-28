<?php

namespace App\Notifications\Finance;

use App\Models\Finance\FinanceTransactionQuery;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinanceTransactionQueryStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(private readonly FinanceTransactionQuery $transactionQuery)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = $this->transactionQuery->status?->label() ?? 'Updated';

        return (new MailMessage)
            ->subject('Transaction Query Status Updated')
            ->greeting("Hello {$notifiable->full_name},")
            ->line('Your finance transaction query has been updated.')
            ->line("Payment reference: {$this->transactionQuery->payment_reference}")
            ->line("Current status: {$statusLabel}")
            ->when(
                filled($this->transactionQuery->decline_reason),
                fn (MailMessage $message) => $message->line("Reason: {$this->transactionQuery->decline_reason}")
            )
            ->line('Please contact finance office if you need more assistance.');
    }
}

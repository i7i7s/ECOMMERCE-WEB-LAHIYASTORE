<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminOperationalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $subject, public string $message, public string $link)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable->is_active && $notifiable->isAdmin();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject($this->subject)->greeting('Halo, tim Lahiya')
            ->line($this->message)->action('Buka panel admin', $this->link);
    }
}

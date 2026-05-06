<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DomainActivityNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{title: string, body: string, action_url?: string|null, module?: string, actor_name?: string}  $payload
     */
    public function __construct(private array $payload) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{title: string, body: string, action_url?: string|null, module?: string, actor_name?: string}
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->payload;
    }
}

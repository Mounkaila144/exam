<?php

namespace App\Notifications\Channels;

use App\Services\Notification\WebPushService;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function __construct(private readonly WebPushService $webPush)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $payload = $notification->toWebPush($notifiable);

        $subscriptions = $notifiable->routeNotificationFor('WebPush', $notification);
        if (! $subscriptions) {
            $subscriptions = $notifiable->pushSubscriptions ?? [];
        }

        foreach ($subscriptions as $subscription) {
            $this->webPush->send($subscription, $payload);
        }
    }
}

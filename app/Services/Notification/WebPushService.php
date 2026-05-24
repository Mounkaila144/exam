<?php

namespace App\Services\Notification;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function send(PushSubscription $subscription, array $payload): void
    {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.subject'),
                'publicKey' => config('webpush.public_key'),
                'privateKey' => config('webpush.private_key'),
            ],
        ]);

        $report = $webPush->sendOneNotification(
            Subscription::create([
                'endpoint' => $subscription->endpoint,
                'keys' => [
                    'p256dh' => $subscription->p256dh_key,
                    'auth' => $subscription->auth_token,
                ],
            ]),
            json_encode($payload),
            ['TTL' => config('webpush.ttl'), 'urgency' => config('webpush.urgency')],
        );

        if (! $report->isSuccess()) {
            $statusCode = $report->getResponse()?->getStatusCode();
            Log::warning('webpush.failed', [
                'endpoint' => $subscription->endpoint,
                'status' => $statusCode,
                'reason' => $report->getReason(),
            ]);

            if (in_array($statusCode, [404, 410], true)) {
                $subscription->delete();
            }
        }
    }
}

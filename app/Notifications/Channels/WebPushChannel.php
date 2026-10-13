<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Sends a notification to every browser/phone the user enabled notifications on.
 *
 * The notification provides toWebPush($notifiable): array{title, body, url?, tag?};
 * without it, toArray()['message'] is used as the body.
 */
class WebPushChannel
{
    public static function isConfigured(): bool
    {
        return (bool) config('services.webpush.public_key') && (bool) config('services.webpush.private_key');
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! self::isConfigured() || ! method_exists($notifiable, 'pushSubscriptions')) {
            return;
        }

        $subscriptions = $notifiable->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = method_exists($notification, 'toWebPush')
            ? $notification->toWebPush($notifiable)
            : ['title' => config('app.name'), 'body' => $notification->toArray($notifiable)['message'] ?? ''];

        $payload += ['url' => url('/dashboard'), 'icon' => '/images/icon-192.png', 'badge' => '/images/icon-192.png'];

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject'    => config('services.webpush.subject'),
                'publicKey'  => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ]], ['TTL' => 86400]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(Subscription::create([
                    'endpoint'        => $sub->endpoint,
                    'publicKey'       => $sub->public_key,
                    'authToken'       => $sub->auth_token,
                    'contentEncoding' => $sub->content_encoding,
                ]), json_encode($payload));
            }

            foreach ($webPush->flush() as $report) {
                // The user uninstalled the app or revoked permission — forget that device.
                if ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', hash('sha256', $report->getEndpoint()))->delete();
                } elseif (! $report->isSuccess()) {
                    Log::warning('Web push failed', ['reason' => $report->getReason()]);
                }
            }
        } catch (\Throwable $e) {
            // A push problem must never break the action that triggered it (approval, payroll, …).
            Log::error('Web push exception', ['error' => $e->getMessage()]);
        }
    }
}

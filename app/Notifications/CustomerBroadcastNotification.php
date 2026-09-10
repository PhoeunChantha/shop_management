<?php

namespace App\Notifications;

use App\Models\CustomerNotificationCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * In-app inbox entry for a bulk campaign — database channel only. The email
 * itself is sent separately via CustomerBroadcastMail (queued directly with
 * Mail::to(), since a guest recipient has no Notifiable/User to route
 * through), so this class never touches the mail channel.
 */
class CustomerBroadcastNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CustomerNotificationCampaign $campaign,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'promo',
            'icon' => 'tag',
            'title' => $this->campaign->title,
            'body' => $this->campaign->message,
            'url' => $this->campaign->url,
        ];
    }
}

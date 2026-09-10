<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Mail\CustomerBroadcastMail;
use App\Models\CustomerNotificationCampaign;
use App\Models\CustomerProfile;
use App\Models\CustomerTag;
use App\Models\User;
use App\Notifications\CustomerBroadcastNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Mail;

final class CustomerNotificationService
{
    public function __construct(
        private readonly CustomerService $customers,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return CustomerNotificationCampaign::query()
            ->with('sentBy:id,name')
            ->search($filters['search'] ?? null)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        return [
            'campaigns' => CustomerNotificationCampaign::count(),
            'recipients' => (int) CustomerNotificationCampaign::sum('recipient_count'),
            'lastSentAgo' => CustomerNotificationCampaign::latest()->value('created_at'),
        ];
    }

    /**
     * The distinct customer emails a given audience selection would reach —
     * used both for the live "N recipients" preview and the actual send.
     * Excludes anyone who has unsubscribed from bulk notifications.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    public function targetEmails(array $data): array
    {
        $emails = ($data['audience_type'] ?? 'all') === 'all'
            ? $this->customers->targetEmails([])
            : $this->customers->targetEmails($this->segmentFilters($data));

        if ($emails === []) {
            return $emails;
        }

        $optedOut = CustomerProfile::query()
            ->whereIn('email', $emails)
            ->where('marketing_opt_out', true)
            ->pluck('email')
            ->map(fn (string $email): string => mb_strtolower($email))
            ->all();

        if ($optedOut === []) {
            return $emails;
        }

        return array_values(array_filter(
            $emails,
            fn (string $email): bool => ! in_array(mb_strtolower($email), $optedOut, true),
        ));
    }

    /**
     * Compose, persist, and send a bulk notification. Every recipient gets
     * the email; a recipient who also has a registered account additionally
     * gets an in-app inbox entry (database notification).
     *
     * @param  array<string, mixed>  $data
     */
    public function send(array $data, ?int $sentByUserId): CustomerNotificationCampaign
    {
        $emails = $this->targetEmails($data);

        $campaign = CustomerNotificationCampaign::create([
            'title' => $data['title'],
            'message' => $data['message'],
            'url' => $data['url'] ?? null,
            'audience_type' => $data['audience_type'] ?? 'all',
            'audience_summary' => $this->summarize($data),
            'recipient_count' => count($emails),
            'sent_by' => $sentByUserId,
        ]);

        if ($emails === []) {
            return $campaign;
        }

        $usersByEmail = User::query()
            ->whereIn('email', $emails)
            ->get(['id', 'name', 'email'])
            ->keyBy(fn (User $user): string => mb_strtolower($user->email));

        $registeredCount = 0;

        foreach ($emails as $email) {
            $user = $usersByEmail->get(mb_strtolower($email));

            if ($user) {
                $user->notify(new CustomerBroadcastNotification($campaign));
                $registeredCount++;
            }

            Mail::to($email)->queue(new CustomerBroadcastMail($campaign, $email, $user?->name));
        }

        $campaign->update(['registered_recipient_count' => $registeredCount]);

        return $campaign;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function segmentFilters(array $data): array
    {
        return array_filter([
            'search' => $data['search'] ?? null,
            'tag_id' => $data['tag_id'] ?? null,
            'spend' => $data['spend'] ?? null,
        ]);
    }

    /**
     * Human-readable audience label for the campaign history list.
     *
     * @param  array<string, mixed>  $data
     */
    private function summarize(array $data): string
    {
        if (($data['audience_type'] ?? 'all') === 'all') {
            return 'All customers';
        }

        $parts = [];

        if (filled($data['search'] ?? null)) {
            $parts[] = 'Search: "'.$data['search'].'"';
        }

        if (filled($data['tag_id'] ?? null)) {
            $tag = CustomerTag::find($data['tag_id']);

            if ($tag) {
                $parts[] = 'Tag: '.$tag->name;
            }
        }

        if (filled($data['spend'] ?? null) && $data['spend'] !== 'all') {
            $parts[] = ucfirst((string) $data['spend']).' customers';
        }

        return $parts === [] ? 'Filtered segment' : implode(', ', $parts);
    }
}

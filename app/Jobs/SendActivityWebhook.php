<?php

namespace App\Jobs;

use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes one activity to the configured automation webhook (n8n). The body is signed so
 * the receiver can verify it: X-Trainingcamp-Signature: sha256=HMAC(body, secret).
 */
class SendActivityWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public function __construct(public int $activityId)
    {
    }

    public function handle(): void
    {
        $url = config('services.n8n.webhook_url');
        $activity = Activity::with(['team', 'actor'])->find($this->activityId);
        if (!$url || !$activity) {
            return;
        }

        $body = json_encode(self::payload($activity), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $body, (string) config('services.n8n.webhook_secret'));

        $response = Http::timeout(config('services.n8n.timeout', 5))
            ->withHeaders([
                'X-Trainingcamp-Event' => $activity->action,
                'X-Trainingcamp-Signature' => 'sha256=' . $signature,
            ])
            ->withBody($body, 'application/json')
            ->post($url);

        if ($response->failed()) {
            Log::warning('Activity webhook failed', ['status' => $response->status(), 'activity' => $activity->id]);
            $response->throw();
        }
    }

    public static function payload(Activity $activity): array
    {
        return [
            'event'       => $activity->action,
            'description' => $activity->description,
            'occurred_at' => $activity->created_at?->toIso8601String(),
            'team'        => ['id' => $activity->team_id, 'name' => $activity->team?->name],
            'actor'       => $activity->user_id
                ? ['id' => $activity->user_id, 'name' => $activity->actor?->full_name]
                : null,
            'subject'     => $activity->subject_type
                ? ['type' => class_basename($activity->subject_type), 'id' => $activity->subject_id]
                : null,
            'url'         => $activity->url,
        ];
    }
}

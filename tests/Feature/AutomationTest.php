<?php

namespace Tests\Feature;

use App\Jobs\SendActivityWebhook;
use App\Mail\DailyDigest;
use App\Models\Activity;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutomationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $team;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-30 06:59:00');
        $this->user = User::factory()->create(['email' => 'coach@example.com', 'first_name' => 'Vera']);
        $this->team = Team::factory()->create(['user_id' => $this->user->id, 'name' => 'Alpha']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_activities_are_pushed_to_the_webhook_with_a_valid_signature()
    {
        config(['services.n8n.webhook_url' => 'https://n8n.test/webhook/trainingcamp', 'services.n8n.webhook_secret' => 'shh']);
        Http::fake(['n8n.test/*' => Http::response(['ok' => true])]);

        $this->actingAs($this->user)->post(route('tasks.store'), ['title' => 'Wrap hands']);

        Http::assertSent(function (Request $request) {
            $payload = json_decode($request->body(), true);
            return $request->url() === 'https://n8n.test/webhook/trainingcamp'
                && $request->header('X-Trainingcamp-Event')[0] === 'task.created'
                && $request->header('X-Trainingcamp-Signature')[0] === 'sha256=' . hash_hmac('sha256', $request->body(), 'shh')
                && $payload['description'] === 'created task “Wrap hands”'
                && $payload['team'] === ['id' => $this->team->id, 'name' => 'Alpha']
                && $payload['actor']['id'] === $this->user->id
                && $payload['subject']['type'] === 'Task'
                && str_contains($payload['url'], '/tasks/');
        });
    }

    public function test_nothing_is_sent_without_a_configured_webhook()
    {
        config(['services.n8n.webhook_url' => null]);
        Http::fake();

        $this->actingAs($this->user)->post(route('tasks.store'), ['title' => 'Quiet task']);

        Http::assertNothingSent();
        $this->assertSame(1, Activity::where('action', 'task.created')->count());
    }

    public function test_a_failing_webhook_is_reported_for_retry()
    {
        config(['services.n8n.webhook_url' => 'https://n8n.test/down']);
        Http::fake(['n8n.test/*' => Http::response('down', 503)]);
        Activity::withoutEvents(fn () => Activity::create([
            'team_id' => $this->team->id, 'action' => 'task.created', 'description' => 'created task “X”',
        ]));

        $this->expectException(RequestException::class);
        (new SendActivityWebhook(Activity::first()->id))->handle();
    }

    public function test_daily_digest_goes_only_to_opted_in_account_holders_with_something_to_do()
    {
        Mail::fake();
        $this->user->update(['notification_preferences' => ['daily_digest' => true]]);
        Task::factory()->create(['team_id' => $this->team->id, 'title' => 'Overdue drill', 'due_date' => '2026-09-28']);
        Schedule::factory()->create(['team_id' => $this->team->id, 'title' => 'Morning bout', 'date' => '2026-09-30', 'start' => '08:00', 'end' => '09:00']);

        $optedOut = User::factory()->create();
        Task::factory()->create(['team_id' => Team::factory()->create(['user_id' => $optedOut->id])->id, 'due_date' => '2026-09-28']);
        $idle = User::factory()->create(['notification_preferences' => ['daily_digest' => true]]);
        Team::factory()->create(['user_id' => $idle->id]);
        $member = User::factory()->member()->create(['email' => 'member@example.com', 'notification_preferences' => ['daily_digest' => true]]);

        $this->artisan('trainingcamp:daily-digest')->expectsOutput('Daily digest sent to 1 account.')->assertSuccessful();

        Mail::assertSent(DailyDigest::class, 1);
        Mail::assertSent(DailyDigest::class, function (DailyDigest $mail) {
            $html = $mail->render();
            return $mail->hasTo('coach@example.com')
                && str_contains($html, 'Alpha')
                && str_contains($html, 'Overdue drill')
                && str_contains($html, 'Morning bout')
                && $mail->envelope()->subject === 'Trainingcamp today: 2 items';
        });
    }

    public function test_digest_can_be_switched_on_in_the_settings_and_is_off_by_default()
    {
        $this->assertFalse($this->user->wantsDailyDigest());

        $this->actingAs($this->user)
            ->put(route('user.notifications.put'), ['reminders' => ['overdue_tasks' => 1], 'daily_digest' => 1])
            ->assertRedirect(route('user.notifications'));

        $this->assertTrue($this->user->fresh()->wantsDailyDigest());
        $this->get(route('user.notifications'))->assertSee('Daily summary at 07:00 to coach@example.com');
    }

    public function test_digest_is_scheduled_every_morning()
    {
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command, 'trainingcamp:daily-digest'));

        $this->assertNotNull($event);
        $this->assertSame('0 7 * * *', $event->expression);
    }
}

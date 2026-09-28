<?php

namespace App\Console\Commands;

use App\Mail\DailyDigest;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Reminders;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends the morning summary to every account holder who switched it on.
 * Runs daily at 07:00 via the scheduler (see App\Console\Kernel).
 */
class SendDailyDigest extends Command
{
    protected $signature = 'trainingcamp:daily-digest';

    protected $description = 'Email each account holder who opted in a summary of today\'s reminders and sparrings';

    public function handle(Reminders $reminders): int
    {
        $sent = 0;
        $accounts = User::where('login_enabled', true)->whereNotNull('email')->with('teams');
        $accounts->each(function (User $user) use ($reminders, &$sent) {
            if (!$user->wantsDailyDigest()) {
                return;
            }
            $sections = $user->teams->map(fn ($team) => [
                'team'      => $team,
                // Sparrings are listed on their own below, so only task reminders here
                'reminders' => $reminders->for($user, $team, ['overdue_tasks', 'due_today']),
                'sparrings' => Schedule::with('participants')->where('team_id', $team->id)->active()
                    ->whereDate('date', Carbon::today()->toDateString())->orderBy('start')->get(),
            ])
                ->filter(fn ($section) => $section['reminders']['count'] || $section['sparrings']->isNotEmpty())
                ->values()->all();

            // Nothing to report means no email
            if (!$sections) {
                return;
            }
            try {
                Mail::to($user->email)->send(new DailyDigest($user, $sections));
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $this->warn("Could not send the digest to user {$user->id}");
            }
        });

        $this->info("Daily digest sent to {$sent} " . ($sent === 1 ? 'account' : 'accounts') . '.');
        return self::SUCCESS;
    }
}

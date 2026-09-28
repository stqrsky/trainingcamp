<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Task;
use Carbon\Carbon;

class HomeController extends Controller
{
    private const FOCUS_TASK_LIMIT = 6;
    private const UPCOMING_SPARRING_LIMIT = 3;

    public function index()
    {
        $team = $this->currentTeam();
        $notifications = Notification::with([
            'user',
            'user.userDetail',
            'user.userDetail.image',
            'image'
        ])->where('user_id', Auth::id())
            ->where(function ($query) use ($team) {
                $query->where('team_id', $team?->id)->orWhereNull('team_id');
            })
            ->orderByDesc('created_at')->paginate(12);

        $stats = null;
        $focusTasks = $upcomingSparrings = $teamOverview = collect();
        if ($team) {
            $stats = $this->stats($team);
            $focusTasks = $this->focusTasks($team);
            $upcomingSparrings = $this->upcomingSparrings($team);
            $teamOverview = $this->teamOverview();
        }

        return view('frontend.home', compact(
            'team',
            'notifications',
            'stats',
            'focusTasks',
            'upcomingSparrings',
            'teamOverview'
        ));
    }

    private function stats($team): array
    {
        $open = Task::where('team_id', $team->id)->open();
        return [
            'open'     => (clone $open)->count(),
            'overdue'  => (clone $open)->whereNotNull('due_date')->whereDate('due_date', '<', Carbon::today())->count(),
            'mine'     => (clone $open)->where('assignee_id', Auth::id())->count(),
            'sparrings_this_week' => Schedule::where('team_id', $team->id)->active()
                ->whereBetween('date', [
                    Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString(),
                    Carbon::now()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                ])->count(),
        ];
    }

    /**
     * Overdue tasks first, then today's, most urgent on top.
     */
    private function focusTasks($team)
    {
        return Task::with('assignee')->where('team_id', $team->id)->open()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', Carbon::today())
            ->orderBy('due_date')->orderByPriority()->orderBy('due_time')
            ->limit(self::FOCUS_TASK_LIMIT)->get();
    }

    private function upcomingSparrings($team)
    {
        $now = Carbon::now();
        return Schedule::with('participants')->where('team_id', $team->id)->active()
            ->where('status', '!=', 'completed')
            ->where(function ($query) use ($now) {
                $query->whereDate('date', '>', $now->toDateString())
                    ->orWhere(function ($today) use ($now) {
                        $today->whereDate('date', $now->toDateString())->where('end', '>=', $now->format('H:i'));
                    });
            })
            ->orderBy('date')->orderBy('start')
            ->limit(self::UPCOMING_SPARRING_LIMIT)->get();
    }

    /**
     * Per-team open and overdue task counts, shown only when the account manages several teams.
     */
    private function teamOverview()
    {
        $teams = Auth::user()->teams()->orderBy('name')->get(['id', 'name']);
        if ($teams->count() < 2) {
            return collect();
        }
        $counts = Task::open()->whereIn('team_id', $teams->pluck('id'))
            ->selectRaw('team_id, COUNT(*) as open_count')
            ->selectRaw(
                'SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue_count',
                [Carbon::today()->toDateString()]
            )
            ->groupBy('team_id')->get()->keyBy('team_id');
        return $teams->map(function ($team) use ($counts) {
            $team->open_count = (int) ($counts[$team->id]->open_count ?? 0);
            $team->overdue_count = (int) ($counts[$team->id]->overdue_count ?? 0);
            return $team;
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Schedule;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Global search behind the command palette. Every group is limited to the active team.
 */
class SearchController extends Controller
{
    private const MIN_LENGTH = 2;
    private const PER_GROUP = 5;

    public function __invoke(Request $request)
    {
        $this->validate($request, ['q' => 'nullable|string|max:100']);
        $query = trim((string) $request->query('q', ''));
        $team = $this->currentTeam();
        if (!$team || mb_strlen($query) < self::MIN_LENGTH) {
            return response()->json(['groups' => []]);
        }
        $like = "%$query%";

        $members = $team->coaches()->matchingName($query)->limit(self::PER_GROUP)->get()
            ->map(fn ($user) => $this->memberItem($user, 'Coach'))
            ->concat($team->athletes()->matchingName($query)->limit(self::PER_GROUP)->get()
                ->map(fn ($user) => $this->memberItem($user, 'Athlete')))
            ->unique('url')->take(self::PER_GROUP)->values();

        $tasks = Task::where('team_id', $team->id)
            ->where(fn ($task) => $task->where('title', 'like', $like)
                ->orWhere('notes', 'like', $like)
                ->orWhere('label', 'like', $like))
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderByDesc('updated_at')
            ->limit(self::PER_GROUP)->get()
            ->map(fn ($task) => [
                'title'    => $task->title,
                'subtitle' => $task->status_label . ($task->due_date ? ' · due ' . $task->dueLabel : ''),
                'url'      => route('tasks.edit', $task),
                'icon'     => 'task_alt',
            ]);

        $sparrings = Schedule::with('participants')->where('team_id', $team->id)
            ->where(fn ($schedule) => $schedule->where('title', 'like', $like)
                ->orWhere('location', 'like', $like)
                ->orWhere('goal', 'like', $like)
                ->orWhereHas('participants', fn ($participants) => $participants->matchingName($query)))
            ->orderByDesc('date')
            ->limit(self::PER_GROUP)->get()
            ->map(fn ($schedule) => [
                'title'    => $schedule->title ?: 'Sparring',
                'subtitle' => Carbon::parse($schedule->date)->format('D, j M Y') . ' · '
                    . $schedule->participants->pluck('full_name')->implode(' vs '),
                'url'      => route('schedules.index', ['date' => $schedule->date_format]),
                'icon'     => 'sports_kabaddi',
            ]);

        $posts = Notification::where('user_id', Auth::id())
            ->where(fn ($post) => $post->where('team_id', $team->id)->orWhereNull('team_id'))
            ->where(fn ($post) => $post->where('title', 'like', $like)->orWhere('description', 'like', $like))
            ->orderByDesc('created_at')
            ->limit(self::PER_GROUP)->get()
            ->map(fn ($post) => [
                'title'    => $post->title,
                'subtitle' => 'Announcement · ' . $post->time,
                'url'      => route('notification.edit', $post),
                'icon'     => 'campaign',
            ]);

        return response()->json(['groups' => collect([
            ['label' => 'Members', 'items' => $members],
            ['label' => 'Tasks', 'items' => $tasks],
            ['label' => 'Sparrings', 'items' => $sparrings],
            ['label' => 'Announcements', 'items' => $posts],
        ])->filter(fn ($group) => $group['items']->isNotEmpty())->values()]);
    }

    private function memberItem($user, string $role): array
    {
        return [
            'title'    => $user->full_name,
            'subtitle' => $role . ($user->pivot->active ? '' : ' · inactive'),
            'url'      => route('user.athletes.detail', ['id' => $user->id]),
            'icon'     => 'person',
        ];
    }
}

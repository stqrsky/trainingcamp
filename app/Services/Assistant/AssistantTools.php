<?php

namespace App\Services\Assistant;

use App\Models\Project;
use App\Models\Schedule;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Services\SparringMatcher;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The tools the assistant may call. Every lookup is scoped to one team and only returns what
 * the account holder already sees in the app; contact data, birth dates and body measurements
 * are never included. Nothing here writes to the database: draft_tasks only collects drafts
 * that the user reviews and creates through the normal task form.
 */
class AssistantTools
{
    private const LIST_LIMIT = 25;
    private const MAX_DRAFTS = 10;
    private const TASK_FILTERS = ['open', 'overdue', 'due_next_7_days', 'completed_last_14_days'];
    private const SPARRING_RANGES = ['today', 'next_7_days', 'next_30_days', 'last_30_days'];
    private const MEMBER_ROLES = ['all', 'athlete', 'coach'];

    /** @var array<int, array> */
    private array $drafts = [];

    private ?Collection $people = null;

    public function __construct(private User $manager, private Team $team)
    {
    }

    /**
     * Tool definitions for the Messages API (SDK camelCase keys).
     */
    public static function definitions(): array
    {
        $object = fn (array $properties, array $required = []) => [
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
            'additionalProperties' => false,
        ];
        $tool = fn (string $name, string $description, array $schema) => [
            'name' => $name,
            'description' => $description,
            'inputSchema' => $schema,
            'strict' => true,
        ];
        $person = fn (string $description) => ['type' => 'string', 'description' => $description];

        return [
            $tool(
                'find_tasks',
                'List tasks of the team. Call this for any question about tasks, to-dos, deadlines, '
                    . 'workload or who is working on what.',
                $object([
                    'filter' => ['type' => 'string', 'enum' => self::TASK_FILTERS],
                    'assignee' => $person('Only tasks assigned to this member (first name, last name or nickname)'),
                    'project' => ['type' => 'string', 'description' => 'Only tasks of this project (name or part)'],
                ], ['filter'])
            ),
            $tool(
                'find_sparrings',
                'List sparring sessions of the team with date, time, status and participants. Call this for '
                    . 'questions about the schedule, sessions or who sparred with whom.',
                $object([
                    'range' => ['type' => 'string', 'enum' => self::SPARRING_RANGES],
                    'athlete' => $person('Only sessions this member takes part in (name)'),
                ], ['range'])
            ),
            $tool(
                'suggest_sparring_partners',
                'Rank the best sparring partners for one athlete with the app\'s rule-based matching (level, '
                    . 'shared skills, common free time, weight, recent pairings). Call this when the user asks '
                    . 'who should spar with someone.',
                $object(['athlete' => $person('The athlete to find partners for (name)')], ['athlete'])
            ),
            $tool(
                'project_status',
                'Show projects with status, deadline and progress. Without a project name it lists all open '
                    . 'projects; with a name it adds the project\'s open tasks and members.',
                $object(['project' => ['type' => 'string', 'description' => 'Project name or part of it']])
            ),
            $tool(
                'find_members',
                'List active team members with role, experience level, skills and weekly availability. Call '
                    . 'this for questions about who is in the team or who can do what.',
                $object([
                    'role' => ['type' => 'string', 'enum' => self::MEMBER_ROLES],
                    'name' => $person('Only members matching this first name, last name or nickname'),
                ], ['role'])
            ),
            $tool(
                'draft_tasks',
                'Propose new tasks, e.g. from meeting notes. The drafts are shown to the user, who reviews and '
                    . 'creates each one. Nothing is created by this tool. Use at most 10 drafts per call.',
                $object(['tasks' => ['type' => 'array', 'items' => $object([
                    'title' => ['type' => 'string', 'description' => 'Short task title'],
                    'notes' => ['type' => 'string', 'description' => 'Optional details'],
                    'assignee' => $person('Member to assign (name), if clear from the context'),
                    'due_date' => [
                        'type' => 'string',
                        'format' => 'date',
                        'description' => 'Deadline as YYYY-MM-DD, if one was mentioned',
                    ],
                    'priority' => ['type' => 'string', 'enum' => array_keys(Task::PRIORITIES)],
                ], ['title'])]], ['tasks'])
            ),
        ];
    }

    /**
     * Run one tool call. Returns the JSON result for the model.
     *
     * @throws InvalidArgumentException for unknown tools or unusable input
     */
    public function run(string $name, array $input): string
    {
        $result = match ($name) {
            'find_tasks' => $this->findTasks($input),
            'find_sparrings' => $this->findSparrings($input),
            'suggest_sparring_partners' => $this->suggestPartners($input),
            'project_status' => $this->projectStatus($input),
            'find_members' => $this->findMembers($input),
            'draft_tasks' => $this->draftTasks($input),
            default => throw new InvalidArgumentException("Unknown tool: {$name}"),
        };
        return json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Drafts collected by draft_tasks during this answer.
     */
    public function drafts(): array
    {
        return $this->drafts;
    }

    private function findTasks(array $input): array
    {
        $query = Task::with(['assignee', 'project'])->where('team_id', $this->team->id);
        $today = Carbon::today();
        match ($this->choice($input, 'filter', self::TASK_FILTERS)) {
            'open' => $query->open(),
            'overdue' => $query->open()->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today->toDateString()),
            'due_next_7_days' => $query->open()->dueBetween($today, $today->copy()->addDays(7)),
            'completed_last_14_days' => $query->where('status', 'done')
                ->where('completed_at', '>=', $today->copy()->subDays(14)),
        };
        if ($name = $this->text($input, 'assignee')) {
            $query->whereIn('assignee_id', $this->person($name)->pluck('id'));
        }
        if ($projectName = $this->text($input, 'project')) {
            $projects = $this->team->projects()->where('name', 'like', "%{$projectName}%");
            $query->whereIn('project_id', $projects->select('id'));
        }

        $total = (clone $query)->count();
        $tasks = $query->orderByPriority()->orderByRaw('due_date IS NULL')->orderBy('due_date')
            ->limit(self::LIST_LIMIT)->get()
            ->map(fn (Task $task) => [
                'title' => $task->title,
                'status' => $task->status_label,
                'priority' => $task->priority_label,
                'due_date' => $task->due_date ? Carbon::parse($task->due_date)->toDateString() : null,
                'overdue' => $task->isOverdue(),
                'assignee' => $task->assignee?->full_name,
                'project' => $task->project?->name,
            ]);

        return ['total' => $total, 'shown' => $tasks->count(), 'tasks' => $tasks];
    }

    private function findSparrings(array $input): array
    {
        $today = Carbon::today();
        [$from, $to] = match ($this->choice($input, 'range', self::SPARRING_RANGES)) {
            'today' => [$today, $today],
            'next_7_days' => [$today, $today->copy()->addDays(7)],
            'next_30_days' => [$today, $today->copy()->addDays(30)],
            'last_30_days' => [$today->copy()->subDays(30), $today],
        };
        $query = Schedule::with('participants')->where('team_id', $this->team->id)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString());
        if ($name = $this->text($input, 'athlete')) {
            $ids = $this->person($name)->pluck('id');
            $query->whereHas('participants', fn ($participants) => $participants->whereIn('users.id', $ids));
        }

        $sessions = $query->orderBy('date')->orderBy('start')->limit(self::LIST_LIMIT)->get()
            ->map(fn (Schedule $schedule) => [
                'title' => $schedule->title,
                'date' => Carbon::parse($schedule->date)->toDateString(),
                'weekday' => Carbon::parse($schedule->date)->format('l'),
                'time' => "{$schedule->start}–{$schedule->end}",
                'status' => $schedule->status_label,
                'participants' => $schedule->participants->pluck('full_name')->values(),
            ]);

        return ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'sessions' => $sessions];
    }

    private function suggestPartners(array $input): array
    {
        $name = $this->text($input, 'athlete') ?? throw new InvalidArgumentException('Name the athlete.');
        $athletes = $this->filterByName($this->team->activeAthletes()->with('userDetail')->get(), $name);
        if ($athletes->count() !== 1) {
            return $this->nameProblem($name, $athletes, 'active athlete');
        }
        $athlete = $athletes->first();

        $suggestions = app(SparringMatcher::class)->suggestionsFor($athlete, $this->team)
            ->map(fn (array $match) => [
                'partner' => $match['partner']->full_name,
                'points' => $match['points'],
                'max_points' => SparringMatcher::MAX_POINTS,
                'reasons' => array_column($match['reasons'], 'label'),
                'next_common_slot' => $match['slot'] ? [
                    'date' => $match['slot']['date']->toDateString(),
                    'time' => "{$match['slot']['start']}–{$match['slot']['end']}",
                ] : null,
            ]);

        return ['athlete' => $athlete->full_name, 'suggestions' => $suggestions];
    }

    private function projectStatus(array $input): array
    {
        $query = $this->team->projects()->with('owner')->withCount([
            'tasks',
            'tasks as done_tasks_count' => fn ($tasks) => $tasks->where('status', 'done'),
        ]);
        $name = $this->text($input, 'project');
        $name ? $query->where('name', 'like', "%{$name}%") : $query->open();
        $projects = $query->orderByRaw('deadline IS NULL')->orderBy('deadline')->limit(self::LIST_LIMIT)->get();

        return ['projects' => $projects->map(function (Project $project) use ($name) {
            $summary = [
                'name' => $project->name,
                'status' => $project->status_label,
                'deadline' => $project->deadline ? Carbon::parse($project->deadline)->toDateString() : null,
                'overdue' => $project->isOverdue(),
                'progress_percent' => $project->progress,
                'tasks' => $project->tasks_count,
                'done_tasks' => $project->done_tasks_count,
                'owner' => $project->owner?->full_name,
            ];
            if ($name) {
                $summary['members'] = $project->members->pluck('full_name')->values();
                $summary['open_tasks'] = $project->tasks()->open()->with('assignee')->orderByPriority()
                    ->limit(self::LIST_LIMIT)->get()
                    ->map(fn (Task $task) => [
                        'title' => $task->title,
                        'status' => $task->status_label,
                        'assignee' => $task->assignee?->full_name,
                        'due_date' => $task->due_date ? Carbon::parse($task->due_date)->toDateString() : null,
                    ]);
            }
            return $summary;
        })];
    }

    private function findMembers(array $input): array
    {
        $role = $this->choice($input, 'role', self::MEMBER_ROLES);
        $with = [
            'userDetail',
            'availabilities',
            'skills' => fn ($skills) => $skills->where('team_id', $this->team->id)->orderBy('name'),
        ];
        $members = collect();
        if ($role !== 'athlete') {
            $members = $members->merge($this->team->activeCoaches()->with($with)->get()
                ->map(fn ($member) => [$member, 'coach']));
        }
        if ($role !== 'coach') {
            $members = $members->merge($this->team->activeAthletes()->with($with)->get()
                ->map(fn ($member) => [$member, 'athlete']));
        }
        if ($name = $this->text($input, 'name')) {
            $matching = $this->filterByName($members->pluck(0), $name)->pluck('id')->all();
            $members = $members->filter(fn ($entry) => in_array($entry[0]->id, $matching, true));
        }

        $openTasks = Task::where('team_id', $this->team->id)->open()
            ->whereIn('assignee_id', $members->pluck('0.id'))
            ->selectRaw('assignee_id, count(*) as open_tasks')->groupBy('assignee_id')
            ->pluck('open_tasks', 'assignee_id');

        return ['members' => $members->map(fn ($entry) => [
            'name' => $entry[0]->full_name,
            'role' => $entry[1],
            'experience_level' => $entry[0]->userDetail?->experience_label,
            'skills' => $entry[0]->skills->map(fn ($skill) => "{$skill->name} ({$skill->pivot->level})")->values(),
            'available' => $entry[0]->availabilities->pluck('label')->values(),
            'open_tasks' => (int) ($openTasks[$entry[0]->id] ?? 0),
        ])->sortBy('name')->values()];
    }

    private function draftTasks(array $input): array
    {
        $tasks = $input['tasks'] ?? null;
        if (!is_array($tasks) || !$tasks) {
            throw new InvalidArgumentException('Pass at least one task.');
        }
        $room = self::MAX_DRAFTS - count($this->drafts);
        $notes = [];
        if (count($tasks) > $room) {
            $notes[] = 'Only ' . self::MAX_DRAFTS . ' drafts per answer; the rest were dropped.';
            $tasks = array_slice($tasks, 0, max($room, 0));
        }

        foreach ($tasks as $task) {
            $title = is_array($task) ? $this->text($task, 'title') : null;
            if (!$title) {
                $notes[] = 'Skipped a draft without a title.';
                continue;
            }
            $draft = [
                'title' => mb_substr($title, 0, 255),
                'notes' => mb_substr((string) $this->text($task, 'notes'), 0, 2000) ?: null,
                'priority' => in_array($task['priority'] ?? null, array_keys(Task::PRIORITIES), true)
                    ? $task['priority'] : 'medium',
                'due_date' => null,
                'assignee_id' => null,
                'assignee_name' => null,
            ];
            if ($due = $this->text($task, 'due_date')) {
                $valid = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $due, $parts)
                    && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
                if ($valid) {
                    $draft['due_date'] = $due;
                } else {
                    $notes[] = "“{$draft['title']}”: “{$due}” is not a date, left without deadline.";
                }
            }
            if ($assignee = $this->text($task, 'assignee')) {
                $people = $this->person($assignee);
                if ($people->count() === 1) {
                    $draft['assignee_id'] = $people->first()->id;
                    $draft['assignee_name'] = $people->first()->full_name;
                } else {
                    $notes[] = "“{$draft['title']}”: no unique team member matches “{$assignee}”, left unassigned.";
                }
            }
            $this->drafts[] = $draft;
        }

        return [
            'drafts_saved' => count($this->drafts),
            'notes' => $notes,
            'next_step' => 'The drafts are shown to the user below your answer. Nothing was created yet; '
                . 'the user reviews and creates each task.',
        ];
    }

    /**
     * People work can be assigned to in this team (account holder plus active members).
     */
    private function person(string $name): Collection
    {
        $this->people ??= EloquentCollection::make($this->team->assignablePeople($this->manager)->all())
            ->load('userDetail');
        return $this->filterByName($this->people, $name);
    }

    /**
     * Every word of the name has to match the first name, last name or nickname.
     */
    private function filterByName(Collection $people, string $name): Collection
    {
        $words = preg_split('/\s+/', mb_strtolower(trim($name)), -1, PREG_SPLIT_NO_EMPTY);
        return $people->filter(function (User $person) use ($words) {
            $names = mb_strtolower("{$person->first_name} {$person->last_name} {$person->userDetail?->nick_name}");
            foreach ($words as $word) {
                if (!str_contains($names, $word)) {
                    return false;
                }
            }
            return true;
        })->values();
    }

    private function nameProblem(string $name, Collection $matches, string $what): array
    {
        return $matches->isEmpty()
            ? ['error' => "No {$what} matches “{$name}”."]
            : [
                'error' => "Several {$what}s match “{$name}”. Ask the user which one.",
                'matches' => $matches->pluck('full_name'),
            ];
    }

    private function choice(array $input, string $key, array $allowed): string
    {
        $value = $input[$key] ?? null;
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("{$key} must be one of: " . implode(', ', $allowed));
        }
        return $value;
    }

    private function text(array $input, string $key): ?string
    {
        $value = $input[$key] ?? null;
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}

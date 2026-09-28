<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const DONE_LIMIT = 20;

    public function index(Request $request)
    {
        $team = $this->currentTeam();
        $mine = $request->boolean('mine');
        if ($request->query('view') === 'board') {
            return $this->board($team, $mine);
        }

        $overdue = $today = $upcoming = $noDate = $done = collect();
        if ($team) {
            $base = Task::with(['assignee', 'project'])->where('team_id', $team->id)
                ->when($mine, fn ($query) => $query->where('assignee_id', Auth::id()));
            $overdue  = (clone $base)->open()->whereNotNull('due_date')
                            ->whereDate('due_date', '<', Carbon::today())->orderBy('due_date')->get();
            $today    = (clone $base)->open()
                            ->whereDate('due_date', Carbon::today())->orderBy('due_time')->get();
            $upcoming = (clone $base)->open()->whereNotNull('due_date')
                            ->whereDate('due_date', '>', Carbon::today())->orderBy('due_date')->get();
            $noDate   = (clone $base)->open()->whereNull('due_date')
                            ->orderByPriority()->orderByDesc('created_at')->get();
            $done     = (clone $base)->where('status', 'done')
                            ->orderByDesc('completed_at')->limit(self::DONE_LIMIT)->get();
        }
        return view('frontend.tasks.index', compact('overdue', 'today', 'upcoming', 'noDate', 'done', 'mine'));
    }

    private function board($team, bool $mine)
    {
        $columns = collect(Task::STATUSES)->map(fn () => collect());
        if ($team) {
            $base = Task::with(['assignee', 'project'])->where('team_id', $team->id)
                ->when($mine, fn ($query) => $query->where('assignee_id', Auth::id()));
            $open = (clone $base)->open()->orderByPriority()
                ->orderByRaw('due_date IS NULL')->orderBy('due_date')->get();
            $done = (clone $base)->where('status', 'done')
                ->orderByDesc('completed_at')->limit(self::DONE_LIMIT)->get();
            $columns = $columns->map(fn ($tasks, $status) => $status === 'done'
                ? $done
                : $open->where('status', $status)->values());
        }
        return view('frontend.tasks.board', compact('columns', 'mine'));
    }

    public function create(Request $request)
    {
        $members = $this->assignableMembers();
        $projects = $this->projectOptions();
        $preselectedProject = $projects->firstWhere('id', (int) $request->query('project'))?->id;
        return view('frontend.tasks.create', compact('members', 'projects', 'preselectedProject'));
    }

    public function store(Request $request)
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $this->validateRequest($request);
        $task = new Task([
            'team_id' => $team->id,
            'user_id' => Auth::id(),
        ]);
        $task->fill($this->attributesFromRequest($request));
        $task->moveTo($request->input('status', 'todo'));
        return redirect()->route('tasks.index');
    }

    public function edit(Task $task)
    {
        $this->authorizeTask($task);
        $members = $this->assignableMembers($task);
        $projects = $this->projectOptions();
        return view('frontend.tasks.edit', compact('task', 'members', 'projects'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);
        $this->validateRequest($request, $task);
        $task->fill($this->attributesFromRequest($request));
        $task->moveTo($request->input('status', $task->status));
        return redirect()->route('tasks.index');
    }

    public function destroy(Task $task)
    {
        $this->authorizeTask($task);
        $task->delete();
        return redirect()->route('tasks.index');
    }

    public function toggle(Task $task)
    {
        $this->authorizeTask($task);
        $task->moveTo($task->isDone() ? 'todo' : 'done');
        return redirect()->back();
    }

    /**
     * Move a task to another workflow column (Kanban drag-and-drop or status select).
     */
    public function move(Request $request, Task $task)
    {
        $this->authorizeTask($task);
        $this->validate($request, [
            'status' => ['required', Rule::in(array_keys(Task::STATUSES))],
        ]);
        $task->moveTo($request->input('status'));
        if ($request->wantsJson()) {
            return response()->json([
                'status' => $task->status,
                'status_label' => $task->status_label,
            ]);
        }
        return redirect()->back();
    }

    private function validateRequest(Request $request, ?Task $task = null)
    {
        $this->validate($request, [
            'title'       => 'required|string|max:255',
            'notes'       => 'nullable|string|max:5000',
            'due_date'    => 'nullable|date_format:d/m/Y',
            'due_time'    => 'nullable|date_format:H:i,H:i:s',
            'label'       => 'nullable|string|max:50',
            'status'      => ['nullable', Rule::in(array_keys(Task::STATUSES))],
            'priority'    => ['nullable', Rule::in(array_keys(Task::PRIORITIES))],
            'assignee_id' => ['nullable', Rule::in($this->assignableMembers($task)->pluck('id')->all())],
            'project_id'  => ['nullable', Rule::in($this->projectOptions()->pluck('id')->all())],
        ]);
    }

    private function attributesFromRequest(Request $request): array
    {
        $due = $request->input('due_date')
             ? Carbon::createFromFormat('d/m/Y', $request->input('due_date'))->format('Y-m-d')
             : null;
        return [
            'title'       => $request->input('title'),
            'notes'       => $request->input('notes'),
            'due_date'    => $due,
            'due_time'    => $request->input('due_time') ?: null,
            'label'       => $request->input('label'),
            'priority'    => $request->input('priority') ?: 'medium',
            'assignee_id' => $request->input('assignee_id') ?: null,
            'project_id'  => $request->input('project_id') ?: null,
        ];
    }

    /**
     * People a task can be assigned to: the account holder plus the current team's active coaches
     * and athletes. An inactive member stays selectable on tasks already assigned to them.
     */
    private function assignableMembers(?Task $task = null)
    {
        $team = $this->currentTeam();
        $members = $team ? $team->assignablePeople(Auth::user()) : collect([Auth::user()]);
        if ($task?->assignee) {
            $members->push($task->assignee);
        }
        return $members->unique('id')->sortBy('full_name')->values();
    }

    private function projectOptions()
    {
        return $this->currentTeam()?->projects()->orderBy('name')->get(['id', 'name', 'status']) ?? collect();
    }

    private function authorizeTask(Task $task)
    {
        $team = $this->currentTeam();
        if (!$team || $task->team_id !== $team->id) abort(403);
    }
}

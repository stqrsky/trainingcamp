<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        $team = $this->team();
        $overdue = $today = $upcoming = $noDate = $done = collect();
        if ($team) {
            $base = Task::where('team_id', $team->id);
            $overdue  = (clone $base)->where('status', 0)->whereNotNull('due_date')
                            ->whereDate('due_date', '<', Carbon::today())->orderBy('due_date')->get();
            $today    = (clone $base)->where('status', 0)
                            ->whereDate('due_date', Carbon::today())->orderBy('due_time')->get();
            $upcoming = (clone $base)->where('status', 0)->whereNotNull('due_date')
                            ->whereDate('due_date', '>', Carbon::today())->orderBy('due_date')->get();
            $noDate   = (clone $base)->where('status', 0)->whereNull('due_date')
                            ->orderByDesc('created_at')->get();
            $done     = (clone $base)->where('status', 1)
                            ->orderByDesc('completed_at')->limit(20)->get();
        }
        return view('frontend.tasks.index', compact('overdue', 'today', 'upcoming', 'noDate', 'done'));
    }

    public function create()
    {
        return view('frontend.tasks.create');
    }

    public function store(Request $request)
    {
        $this->validate($request, ['title' => 'required|string|max:255']);
        $team = $this->team();
        $due = $request->input('due_date')
             ? Carbon::createFromFormat('d/m/Y', $request->input('due_date'))->format('Y-m-d')
             : null;
        Task::create([
            'team_id'  => $team ? $team->id : null,
            'user_id'  => Auth::id(),
            'title'    => $request->input('title'),
            'notes'    => $request->input('notes'),
            'due_date' => $due,
            'due_time' => $request->input('due_time') ?: null,
            'label'    => $request->input('label'),
            'priority' => (int) $request->input('priority', 0),
        ]);
        return redirect()->route('tasks.index');
    }

    public function edit(Task $task)
    {
        $this->authorizeTask($task);
        return view('frontend.tasks.edit', compact('task'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);
        $this->validate($request, ['title' => 'required|string|max:255']);
        $due = $request->input('due_date')
             ? Carbon::createFromFormat('d/m/Y', $request->input('due_date'))->format('Y-m-d')
             : null;
        $task->update([
            'title'    => $request->input('title'),
            'notes'    => $request->input('notes'),
            'due_date' => $due,
            'due_time' => $request->input('due_time') ?: null,
            'label'    => $request->input('label'),
            'priority' => (int) $request->input('priority', 0),
        ]);
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
        if ($task->status == 0) {
            $task->update(['status' => 1, 'completed_at' => now()]);
        } else {
            $task->update(['status' => 0, 'completed_at' => null]);
        }
        return redirect()->back();
    }

    private function team()
    {
        return User::find(Auth::id())->team;
    }

    private function authorizeTask(Task $task)
    {
        $team = $this->team();
        if (!$team || $task->team_id !== $team->id) abort(403);
    }
}

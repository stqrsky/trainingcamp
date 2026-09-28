<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $team = $this->currentTeam();
        if (!$team) {
            return redirect()->route('user.setting')
                ->withErrors(['error' => 'Complete your profile and create a team first.']);
        }
        $projects = $this->withProgress($team->projects())->with('owner')
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planning' THEN 1 WHEN 'on_hold' THEN 2 ELSE 3 END")
            ->orderByRaw('deadline IS NULL')->orderBy('deadline')->orderBy('name')
            ->get();
        [$open, $completed] = $projects->partition(fn ($project) => $project->status !== 'completed');
        return view('frontend.projects.index', compact('open', 'completed'));
    }

    public function create()
    {
        return view('frontend.projects.create', ['people' => $this->people()]);
    }

    public function store(Request $request)
    {
        $team = $this->currentTeam();
        abort_unless($team, 404);
        $project = $team->projects()->create($this->validated($request));
        return redirect()->route('projects.show', $project);
    }

    public function show(Project $project)
    {
        $this->authorizeProject($project);
        $project->loadCount([
            'tasks',
            'tasks as done_tasks_count' => fn ($tasks) => $tasks->where('status', 'done'),
        ]);
        $openTasks = $project->tasks()->with('assignee')->open()->orderByPriority()
            ->orderByRaw('due_date IS NULL')->orderBy('due_date')->get();
        $doneTasks = $project->tasks()->with('assignee')->where('status', 'done')
            ->orderByDesc('completed_at')->get();
        return view('frontend.projects.show', compact('project', 'openTasks', 'doneTasks'));
    }

    public function edit(Project $project)
    {
        $this->authorizeProject($project);
        return view('frontend.projects.edit', ['project' => $project, 'people' => $this->people($project)]);
    }

    public function update(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        $project->update($this->validated($request, $project));
        return redirect()->route('projects.show', $project);
    }

    public function destroy(Project $project)
    {
        $this->authorizeProject($project);
        $project->delete(); // tasks stay, only their project link is cleared
        return redirect()->route('projects.index');
    }

    private function validated(Request $request, ?Project $project = null): array
    {
        $data = $this->validate($request, [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'status'      => ['required', Rule::in(array_keys(Project::STATUSES))],
            'owner_id'    => ['nullable', Rule::in($this->people($project)->pluck('id')->all())],
            'deadline'    => 'nullable|date_format:d/m/Y',
        ]);
        $data['deadline'] = !empty($data['deadline'])
            ? Carbon::createFromFormat('d/m/Y', $data['deadline'])->format('Y-m-d')
            : null;
        return $data;
    }

    /**
     * Possible owners: the team's assignable people, plus the current owner if they became inactive.
     */
    private function people(?Project $project = null)
    {
        $people = $this->currentTeam()?->assignablePeople(Auth::user()) ?? collect([Auth::user()]);
        if ($project?->owner) {
            $people = $people->push($project->owner)->unique('id')->values();
        }
        return $people;
    }

    private function withProgress($query)
    {
        return $query->withCount([
            'tasks',
            'tasks as done_tasks_count' => fn ($tasks) => $tasks->where('status', 'done'),
        ]);
    }

    private function authorizeProject(Project $project): void
    {
        abort_unless($project->team_id === $this->currentTeam()?->id, 404);
    }
}

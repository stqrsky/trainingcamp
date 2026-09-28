<a href="{{ route('projects.show', $project) }}" class="tc-project-card">
    <div class="d-flex justify-content-between align-items-start gap-2">
        <strong class="text-truncate">{{ $project->name }}</strong>
        <span class="tc-status tc-project-status--{{ $project->status }}">{{ $project->status_label }}</span>
    </div>
    @include('frontend.projects._progress', ['project' => $project])
    <div class="tc-project-meta">
        <span>{{ $project->progress }}% · {{ $project->done_tasks_count }}/{{ $project->tasks_count }} tasks</span>
        @if($project->deadline)
        <span class="{{ $project->isOverdue() ? 'text-danger fw-600' : '' }}">
            Due {{ \Carbon\Carbon::parse($project->deadline)->format('j M Y') }}
        </span>
        @endif
    </div>
</a>

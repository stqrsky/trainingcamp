@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 head-title">
            <h4 class="title text-truncate">{{ $project->name }}</h4>
            <a href="{{ route('projects.edit', $project) }}" class="close" aria-label="Edit project">
                <span class="material-icons" aria-hidden="true">edit</span>
            </a>
        </div>
        @include('frontend.tasks._view_tabs')

        <section class="tc-home-section" aria-label="Project overview">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <span class="tc-status tc-project-status--{{ $project->status }}">{{ $project->status_label }}</span>
                @if($project->deadline)
                <span class="tc-task-badge {{ $project->isOverdue() ? 'tc-task-badge--overdue' : '' }}">
                    Due {{ \Carbon\Carbon::parse($project->deadline)->format('j M Y') }}
                </span>
                @endif
                @if($project->owner)
                <span class="tc-task-badge">Owner: {{ $project->owner->full_name }}</span>
                @endif
            </div>
            @include('frontend.projects._progress', ['project' => $project])
            <p class="tc-project-meta mb-2">
                <span>{{ $project->progress }}% done · {{ $project->done_tasks_count }} of {{ $project->tasks_count }} tasks</span>
            </p>
            @if($project->description)
            <p class="mb-2" style="white-space: pre-line">{{ $project->description }}</p>
            @endif
            @if($project->members->isNotEmpty())
            <div class="d-flex flex-wrap gap-1 align-items-center">
                <span class="text-muted small me-1">People:</span>
                @foreach($project->members as $member)
                <span class="tc-avatar tc-avatar--xs" title="{{ $member->full_name }}">{{ $member->initials }}</span>
                @endforeach
            </div>
            @endif
        </section>

        <section aria-labelledby="project-open-tasks">
            <div class="tc-home-section-head">
                <h2 class="tc-task-group-header mb-0" id="project-open-tasks">Open tasks ({{ $openTasks->count() }})</h2>
                <a href="{{ route('tasks.create', ['project' => $project->id]) }}" class="btn btn-sm btn-link p-0 tc-link-strong">Add task</a>
            </div>
            @forelse($openTasks as $task)
                @include('frontend.tasks._item', ['task' => $task])
            @empty
            <p class="text-muted small">No open tasks in this project.</p>
            @endforelse
        </section>

        @if($doneTasks->isNotEmpty())
        <details class="mt-4">
            <summary class="tc-task-group-header" style="cursor:pointer">Done ({{ $doneTasks->count() }})</summary>
            @foreach($doneTasks as $task)
                @include('frontend.tasks._item', ['task' => $task])
            @endforeach
        </details>
        @endif
    </div>
</div>
@endsection

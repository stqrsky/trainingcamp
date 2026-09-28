@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title">Projects</h4>
            <a href="{{ route('projects.create') }}" class="close btn-add" aria-label="New project">
                <span class="material-icons add" aria-hidden="true">add</span>
            </a>
        </div>
        @include('frontend.tasks._view_tabs')

        @forelse($open as $project)
            @include('frontend.projects._card', ['project' => $project])
        @empty
        <div class="text-center py-5 text-muted tc-empty">
            <span class="material-icons" style="font-size:48px" aria-hidden="true">folder_open</span>
            <p class="mt-2">No open projects. Tap <strong>+</strong> to start one and group tasks under it.</p>
        </div>
        @endforelse

        @if($completed->isNotEmpty())
        <details class="mt-4">
            <summary class="tc-task-group-header" style="cursor:pointer">Completed ({{ $completed->count() }})</summary>
            @foreach($completed as $project)
                @include('frontend.projects._card', ['project' => $project])
            @endforeach
        </details>
        @endif
    </div>
</div>
@endsection

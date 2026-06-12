@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title">Task List</h4>
            <a href="{{ route('tasks.create') }}" class="close btn-add" aria-label="Add task">
                <span class="material-icons add">add</span>
            </a>
        </div>

        {{-- Overdue --}}
        @if($overdue->count())
        <div class="tc-task-group-header tc-task-group-header--overdue">
            <span class="material-icons" style="font-size:16px">warning</span> Overdue
        </div>
        @foreach($overdue as $task)
            @include('frontend.tasks._item', ['task' => $task])
        @endforeach
        @endif

        {{-- Today --}}
        @if($today->count())
        <div class="tc-task-group-header mt-3">
            Today · {{ now()->format('j M') }}
        </div>
        @foreach($today as $task)
            @include('frontend.tasks._item', ['task' => $task])
        @endforeach
        @endif

        {{-- Upcoming --}}
        @if($upcoming->count())
        <div class="tc-task-group-header mt-3">Upcoming</div>
        @foreach($upcoming as $task)
            @include('frontend.tasks._item', ['task' => $task])
        @endforeach
        @endif

        {{-- No date --}}
        @if($noDate->count())
        <div class="tc-task-group-header mt-3">No Date</div>
        @foreach($noDate as $task)
            @include('frontend.tasks._item', ['task' => $task])
        @endforeach
        @endif

        @if($overdue->isEmpty() && $today->isEmpty() && $upcoming->isEmpty() && $noDate->isEmpty())
        <div class="text-center py-5 text-muted tc-empty">
            <span class="material-icons" style="font-size:48px">checklist</span>
            <p class="mt-2">No tasks yet. Tap <strong>+</strong> to add one.</p>
        </div>
        @endif

        {{-- Completed --}}
        @if($done->count())
        <details class="mt-4">
            <summary class="tc-task-group-header" style="cursor:pointer">
                Completed ({{ $done->count() }})
            </summary>
            @foreach($done as $task)
                @include('frontend.tasks._item', ['task' => $task])
            @endforeach
        </details>
        @endif

        <a href="{{ route('tasks.create') }}" class="tc-task-add-btn">
            <span class="material-icons" style="font-size:16px">add</span> New task
        </a>
    </div>
</div>
@endsection

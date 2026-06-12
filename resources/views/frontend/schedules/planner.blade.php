@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title schedule">Sparring Schedule</h4>
            <a href="{{ route('schedules.create') }}" class="close btn-add" aria-label="Add">
                <span class="material-icons add">add</span>
            </a>
        </div>
        @include('frontend.schedules._view_tabs')

        {{-- Day navigation --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('schedules.planner', ['date' => $date->copy()->subDay()->format('d/m/Y')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_left</span>
            </a>
            <div class="text-center">
                <strong>{{ $date->format('l, j F Y') }}</strong>
                @if($date->isToday()) <span class="badge bg-primary ms-1">Today</span>@endif
            </div>
            <a href="{{ route('schedules.planner', ['date' => $date->copy()->addDay()->format('d/m/Y')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_right</span>
            </a>
        </div>
    </div>

    {{-- Time grid --}}
    @php $gridStart = 6; $gridEnd = 22; $pxPerHour = 60; @endphp
    <div class="card-body pt-0">
        <div class="tc-day-grid-wrap mb-4" style="min-height:{{ ($gridEnd - $gridStart) * $pxPerHour }}px">
            @for($h = $gridStart; $h <= $gridEnd; $h++)
            <div class="tc-day-hour-row" style="height:{{ $pxPerHour }}px">
                <span class="tc-day-hour-label">{{ sprintf('%02d:00', $h) }}</span>
                <div class="tc-day-hour-line"></div>
            </div>
            @endfor
            <div class="tc-day-events">
                @foreach($schedules as $ev)
                @php
                    [$sh,$sm] = explode(':', $ev->start);
                    [$eh,$em] = explode(':', $ev->end);
                    $top = ($sh - $gridStart) * $pxPerHour + intdiv((int)$sm * $pxPerHour, 60);
                    $ht  = max(32, ($eh - $sh) * $pxPerHour + intdiv(($em - $sm) * $pxPerHour, 60));
                @endphp
                <a href="{{ route('schedules.edit', $ev->id) }}" class="tc-day-event text-decoration-none"
                   style="top:{{ $top }}px;height:{{ $ht }}px;background:{{ $ev->colorBg }};border-color:{{ $ev->colorHex }};color:{{ $ev->colorHex }}">
                    <strong>{{ $ev->title ?: 'Sparring' }}</strong>
                    <small class="d-block">{{ $ev->start }} – {{ $ev->end }}</small>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Tasks section --}}
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0 fw-700">
                Today
                @if($tasks->count())<span class="badge bg-primary ms-1">{{ $tasks->count() }}</span>@endif
            </h6>
            <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-link p-0">See all</a>
        </div>

        @forelse($tasks as $task)
        <div class="tc-task-item">
            <form action="{{ route('tasks.toggle', $task->id) }}" method="POST" class="mb-0">
                @csrf
                <button type="submit" class="tc-task-checkbox {{ $task->status ? 'done' : '' }}" title="Toggle">
                    @if($task->status)<span class="material-icons" style="font-size:14px;color:#fff">check</span>@endif
                </button>
            </form>
            <div class="flex-fill">
                <a href="{{ route('tasks.edit', $task->id) }}"
                   class="tc-task-title {{ $task->status ? 'done' : '' }} text-decoration-none d-block">{{ $task->title }}</a>
                <div class="tc-task-meta">
                    @if($task->label)<span class="tc-task-badge">{{ $task->label }}</span>@endif
                    @if($task->due_date)<span class="tc-task-badge {{ $task->isOverdue() ? 'tc-task-badge--overdue' : '' }}">{{ $task->dueLabel }}</span>@endif
                </div>
            </div>
        </div>
        @empty
        <p class="text-muted small py-2">No tasks for this day.</p>
        @endforelse

        <a href="{{ route('tasks.create') }}" class="tc-task-add-btn">
            <span class="material-icons" style="font-size:16px">add</span> New task
        </a>
    </div>
</div>
@endsection

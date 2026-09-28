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

        {{-- Month navigation --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('schedules.month', ['month' => $date->copy()->subMonth()->format('Y-m')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_left</span>
            </a>
            <strong>{{ $date->format('F Y') }}</strong>
            <a href="{{ route('schedules.month', ['month' => $date->copy()->addMonth()->format('Y-m')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_right</span>
            </a>
        </div>

        {{-- Calendar grid --}}
        <div class="tc-month-grid">
            @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $dow)
            <div class="tc-month-day-header">{{ $dow }}</div>
            @endforeach

            @php $cursor = $calStart->copy(); @endphp
            @while($cursor->lte($calEnd))
            @php
                $key    = $cursor->format('Y-m-d');
                $dayEvts = $schedulesByDate[$key] ?? collect();
                $dayTasks = $tasksByDate[$key] ?? collect();
                $shownTasks = $dayTasks->take(max(0, 3 - $dayEvts->count()));
                $hiddenCount = $dayEvts->count() + $dayTasks->count() - min(3, $dayEvts->count()) - $shownTasks->count();
                $isToday = $cursor->isToday();
                $outOfMonth = $cursor->month !== $date->month;
            @endphp
            <div class="tc-month-day-cell {{ $outOfMonth ? 'tc-month-oom' : '' }}">
                <div class="tc-month-day-num {{ $isToday ? 'tc-today-num' : '' }}">{{ $cursor->day }}</div>
                @foreach($dayEvts->take(3) as $ev)
                <a href="{{ route('schedules.day', ['date' => $cursor->format('d/m/Y')]) }}"
                   class="tc-month-event d-block text-decoration-none"
                   style="background:{{ $ev->colorBg }};color:{{ $ev->colorHex }}">
                    {{ $ev->title ?: $ev->start }}
                </a>
                @endforeach
                @foreach($shownTasks as $task)
                <a href="{{ route('tasks.edit', $task) }}"
                   class="tc-month-event tc-month-task d-block text-decoration-none {{ $task->isOverdue() ? 'is-overdue' : '' }}"
                   title="Due: {{ $task->title }}">
                    <span class="material-icons" aria-hidden="true">task_alt</span><span class="visually-hidden">Due:</span> {{ $task->title }}
                </a>
                @endforeach
                @if($hiddenCount > 0)
                <a href="{{ route('schedules.agenda', ['from' => $key]) }}" class="small text-muted text-decoration-none">+{{ $hiddenCount }} more</a>
                @endif
            </div>
            @php $cursor->addDay(); @endphp
            @endwhile
        </div>
    </div>
</div>
@endsection

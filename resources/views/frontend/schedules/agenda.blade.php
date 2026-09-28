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

        {{-- Range navigation --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('schedules.agenda', ['from' => $from->copy()->subDays($to->diffInDays($from) + 1)->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-secondary" aria-label="Previous two weeks">
                <span class="material-icons" style="font-size:18px" aria-hidden="true">chevron_left</span>
            </a>
            <strong>{{ $from->format('d M') }} – {{ $to->format('d M Y') }}</strong>
            <a href="{{ route('schedules.agenda', ['from' => $to->copy()->addDay()->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-secondary" aria-label="Next two weeks">
                <span class="material-icons" style="font-size:18px" aria-hidden="true">chevron_right</span>
            </a>
        </div>

        @if($overdue->isNotEmpty())
        <section class="tc-agenda-day" aria-labelledby="agenda-overdue">
            <h2 class="tc-task-group-header tc-task-group-header--overdue" id="agenda-overdue">
                <span class="material-icons" style="font-size:16px" aria-hidden="true">warning</span> Overdue
            </h2>
            @foreach($overdue as $task)
                @include('frontend.tasks._item', ['task' => $task])
            @endforeach
        </section>
        @endif

        @forelse($days as $day => $entries)
        @php $dayDate = \Carbon\Carbon::parse($day); @endphp
        <section class="tc-agenda-day" aria-labelledby="agenda-{{ $day }}">
            <h2 class="tc-task-group-header" id="agenda-{{ $day }}">
                {{ $dayDate->format('l, j M') }}
                @if($dayDate->isToday())<span class="badge bg-primary ms-1">Today</span>@endif
            </h2>
            @foreach($entries as $entry)
                @if($entry['type'] === 'sparring')
                @php $sparring = $entry['item']; @endphp
                <a href="{{ route('schedules.edit', $sparring) }}" class="tc-upcoming {{ $sparring->isCancelled() ? 'is-cancelled' : '' }}"
                   style="--tc-upcoming-accent: {{ $sparring->color_hex }}">
                    <span class="tc-agenda-time">{{ $sparring->start }}<small>{{ $sparring->end }}</small></span>
                    <span class="tc-upcoming-body">
                        <strong class="text-truncate">
                            {{ $sparring->title ?: 'Sparring' }}
                            @if($sparring->status !== 'planned')<span class="tc-status tc-status--{{ $sparring->status }}">{{ $sparring->status_label }}</span>@endif
                        </strong>
                        <span class="tc-upcoming-meta">
                            {{ $sparring->participants->pluck('full_name')->implode(' vs ') ?: 'No participants' }}
                            @if($sparring->location) · {{ $sparring->location }}@endif
                        </span>
                    </span>
                    <span class="material-icons tc-upcoming-chevron" aria-hidden="true">chevron_right</span>
                </a>
                @else
                    @include('frontend.tasks._item', ['task' => $entry['item']])
                @endif
            @endforeach
        </section>
        @empty
        @if($overdue->isEmpty())
        <div class="text-center py-5 text-muted tc-empty">
            <span class="material-icons" style="font-size:48px" aria-hidden="true">event_available</span>
            <p class="mt-2">Nothing planned for these two weeks.</p>
        </div>
        @endif
        @endforelse
    </div>
</div>
@endsection

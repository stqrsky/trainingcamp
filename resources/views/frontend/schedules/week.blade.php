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

        {{-- Week navigation --}}
        @php $weekLabel = $days[0]->format('d M').' – '.$days[6]->format('d M Y'); @endphp
        <div class="d-flex align-items-center justify-content-between mb-3">
            <a href="{{ route('schedules.week', ['week' => $date->copy()->subWeek()->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_left</span>
            </a>
            <strong>{{ $weekLabel }}</strong>
            <a href="{{ route('schedules.week', ['week' => $date->copy()->addWeek()->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_right</span>
            </a>
        </div>
    </div>

    {{-- Time grid --}}
    @php $gridStart = 6; $gridEnd = 22; $pxPerHour = 64; @endphp
    <div class="tc-week-grid-wrap">
        {{-- Day headers --}}
        <div class="tc-week-header">
            <div class="tc-week-time-col"></div>
            @foreach($days as $day)
            @php $isToday = $day->isToday(); @endphp
            <div class="tc-week-day-header {{ $isToday ? 'tc-today-col' : '' }}">
                <div class="tc-week-dow">{{ $day->format('D') }}</div>
                <div class="tc-week-dnum {{ $isToday ? 'tc-today-num' : '' }}">{{ $day->format('j') }}</div>
            </div>
            @endforeach
        </div>

        {{-- Scrollable body --}}
        <div class="tc-week-body">
            <div class="tc-week-time-col">
                @for($h = $gridStart; $h <= $gridEnd; $h++)
                <div class="tc-time-label" style="height:{{ $pxPerHour }}px">{{ sprintf('%02d:00', $h) }}</div>
                @endfor
            </div>
            @foreach($days as $day)
            <div class="tc-week-day-col {{ $day->isToday() ? 'tc-today-col' : '' }}">
                {{-- Hour lines --}}
                @for($h = $gridStart; $h <= $gridEnd; $h++)
                <div class="tc-hour-line" style="top:{{ ($h - $gridStart) * $pxPerHour }}px"></div>
                @endfor
                {{-- Events --}}
                @php $dayEvts = $schedulesByDate[$day->format('Y-m-d')] ?? collect(); @endphp
                @foreach($dayEvts as $ev)
                @php
                    [$sh,$sm] = explode(':', $ev->start);
                    [$eh,$em] = explode(':', $ev->end);
                    $top = ($sh - $gridStart) * $pxPerHour + intdiv((int)$sm * $pxPerHour, 60);
                    $ht  = max(28, ($eh - $sh) * $pxPerHour + intdiv(($em - $sm) * $pxPerHour, 60));
                @endphp
                <a href="{{ route('schedules.edit', $ev->id) }}" class="tc-event-block text-decoration-none"
                   style="top:{{ $top }}px;height:{{ $ht }}px;background:{{ $ev->colorBg }};border-color:{{ $ev->colorHex }};color:{{ $ev->colorHex }}">
                    <strong class="d-block" style="font-size:11px">{{ $ev->title ?: 'Sparring' }}</strong>
                    <span style="font-size:10px">{{ $ev->start }}–{{ $ev->end }}</span>
                </a>
                @endforeach
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

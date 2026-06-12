@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

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
        <div class="d-flex align-items-center justify-content-between mb-2">
            <a href="{{ route('schedules.day', ['date' => $date->copy()->subDay()->format('d/m/Y')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_left</span>
            </a>
            <div class="text-center">
                <strong class="d-block">{{ $date->format('l, j F Y') }}</strong>
                @if($date->isToday())<span class="badge bg-primary">Today</span>@endif
            </div>
            <a href="{{ route('schedules.day', ['date' => $date->copy()->addDay()->format('d/m/Y')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <span class="material-icons" style="font-size:18px">chevron_right</span>
            </a>
        </div>
    </div>

    {{-- Time grid --}}
    @php $gridStart = 6; $gridEnd = 22; $pxPerHour = 64; @endphp
    <div class="tc-day-grid-wrap">
        @for($h = $gridStart; $h <= $gridEnd; $h++)
        <div class="tc-day-hour-row" style="height:{{ $pxPerHour }}px">
            <span class="tc-day-hour-label">{{ sprintf('%02d:00', $h) }}</span>
            <div class="tc-day-hour-line"></div>
        </div>
        @endfor

        {{-- Events overlay --}}
        <div class="tc-day-events">
            @foreach($schedules as $ev)
            @php
                [$sh,$sm] = explode(':', $ev->start);
                [$eh,$em] = explode(':', $ev->end);
                $top = ($sh - $gridStart) * $pxPerHour + intdiv((int)$sm * $pxPerHour, 60);
                $ht  = max(32, ($eh - $sh) * $pxPerHour + intdiv(($em - $sm) * $pxPerHour, 60));
            @endphp
            <a href="{{ route('schedules.edit', $ev->id) }}"
               class="tc-day-event text-decoration-none"
               style="top:{{ $top }}px;height:{{ $ht }}px;background:{{ $ev->colorBg }};border-color:{{ $ev->colorHex }};color:{{ $ev->colorHex }}">
                <strong>{{ $ev->title ?: 'Sparring' }}</strong>
                <small class="d-block">{{ $ev->start }} – {{ $ev->end }}</small>
                @if($ev->location)
                <small class="d-block text-muted">
                    <span class="material-icons" style="font-size:11px;vertical-align:middle">location_on</span>
                    {{ $ev->location }}
                </small>
                @endif
                @if($ev->participants->count())
                <small class="d-block mt-1">
                    {{ $ev->participants->pluck('full_name')->implode(' vs ') }}
                </small>
                @endif
            </a>
            @endforeach
        </div>
    </div>

    @if($schedules->isEmpty())
    <div class="text-center py-5 text-muted tc-empty">
        <span class="material-icons" style="font-size:48px">event_busy</span>
        <p class="mt-2">No sessions on this day.</p>
    </div>
    @endif
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
@endsection

@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title schedule">Sparring Schedule</h4>
            <a href="{{ route('schedules.create') }}" type="button" class="close btn-add" aria-label="Add sparring assignment">
                <span aria-hidden="true" class="material-icons add">add</span>
            </a>
        </div>

        @include('frontend.schedules._view_tabs')

        {{-- Prominent date header --}}
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div class="tc-date-head">
                <span class="material-icons" aria-hidden="true">calendar_today</span>
                <span>{{ $date_format }}</span>
            </div>
            <button id="today-btn" type="button" class="btn btn-sm btn-outline-secondary">Today</button>
        </div>

        {{-- Pick a date (server-side navigation) --}}
        <form class="tc-search mb-2" id="date-form" data-no-spinner>
            <input class="form-control" name="date" id="date" type="text" value="{{ $date }}"
                   aria-label="Pick a date" placeholder="Pick a date">
            <button class="btn search" type="submit">
                <span class="material-icons align-middle" style="font-size:18px">event</span>
            </button>
        </form>

        {{-- Filter the day's slots by athlete name (instant, client-side) --}}
        <div class="tc-search">
            <input class="form-control" id="name-filter" type="search" autocomplete="off"
                   aria-label="Filter by athlete name" placeholder="Filter this day by athlete name…">
        </div>
    </div>

    <div class="card-body" id="slot-list">
        @forelse($schedules as $schedule)
        @php
            $names = $schedule->participants->pluck('full_name')->implode(' ');
        @endphp
        <div class="card schedule mb-3 tc-slot" data-names="{{ \Illuminate\Support\Str::lower($names) }}"
             style="border-left: 4px solid {{ $schedule->colorHex }}">
            <div class="toast-header time d-flex align-items-center justify-content-between">
                <div>
                    @if($schedule->title)
                    <strong class="d-block">{{ $schedule->title }}</strong>
                    <small class="text-muted">{{ $schedule->startEndTime }}</small>
                    @else
                    <strong>{{ $schedule->startEndTime }}</strong>
                    @endif
                </div>
                <div class="d-flex align-items-center">
                    <a href="{{ route('schedules.edit', ['schedule' => $schedule->id]) }}"
                       class="close" aria-label="Edit assignment" title="Edit">
                        <span aria-hidden="true" class="material-icons">edit</span>
                    </a>
                    <div class="btn-group">
                        <button type="button" class="close" id="dropdown{{$schedule->id}}"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="More actions">
                            <span aria-hidden="true" class="material-icons">more_vert</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown{{$schedule->id}}">
                            <a class="dropdown-item" href="{{ route('schedules.edit', ['schedule' => $schedule->id]) }}">Reschedule</a>
                            <form action="{{ route('schedules.destroy', ['schedule' => $schedule->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <input type="submit" class="dropdown-item" value="Delete" />
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tc-slot-body">
                @foreach($schedule->participants as $key => $athlete)
                @php
                    $parts    = array_filter(explode(' ', trim($athlete->full_name)));
                    $initials = collect($parts)->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                    $hasImg   = $athlete->userDetail && $athlete->userDetail->image;
                @endphp
                @if($key > 0)
                <div class="tc-vs">VS</div>
                @endif
                <div class="tc-spar-row">
                    <span class="tc-avatar tc-avatar--md">
                        @if($hasImg)
                        <img src="{{ asset($athlete->userDetail->image->file_name) }}" alt="{{ $athlete->full_name }}">
                        @else
                        {{ $initials ?: '?' }}
                        @endif
                    </span>
                    <div class="d-flex flex-column">
                        <span class="tc-spar-name">{{ $athlete->full_name }}</span>
                        <span class="tc-badge tc-badge--athlete mt-1">Athlete</span>
                    </div>
                </div>
                @endforeach
            </div>
            @if($schedule->location || $schedule->video_url)
            <div class="px-3 pb-2 d-flex flex-wrap gap-2">
                @if($schedule->location)
                <span class="tc-meta-pill">
                    <span class="material-icons" style="font-size:14px">location_on</span>
                    {{ $schedule->location }}
                </span>
                @endif
                @if($schedule->video_url)
                <a href="{{ $schedule->video_url }}" target="_blank" rel="noopener" class="tc-meta-pill tc-meta-pill--video">
                    <span class="material-icons" style="font-size:14px">videocam</span>
                    {{ $schedule->videoLabel }}
                </a>
                @endif
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-5 text-muted tc-empty">
            <span class="material-icons" style="font-size:48px">event_busy</span>
            <p class="mt-2">No sessions scheduled for this day.</p>
        </div>
        @endforelse

        {{-- Shown by the filter when nothing matches --}}
        <div class="text-center py-4 text-muted tc-empty" id="no-match" hidden>
            <span class="material-icons" style="font-size:40px">search_off</span>
            <p class="mt-1">No athletes match that name.</p>
        </div>
    </div>
</div>
@endsection

@section('script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#date').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            minYear: 1901,
            maxYear: parseInt(moment().format('YYYY'), 10),
            locale: { format: 'DD/MM/YYYY' }
        }, function(start) {
            document.getElementById('date-form').submit()
        })

        // Today shortcut
        $('#today-btn').on('click', function() {
            $('#date').val(moment().format('DD/MM/YYYY'))
            document.getElementById('date-form').submit()
        })
    })

    // Instant client-side filter of the day's sparring slots by athlete name
    (function () {
        var input = document.getElementById('name-filter')
        if (!input) return
        var slots = Array.prototype.slice.call(document.querySelectorAll('.tc-slot'))
        var noMatch = document.getElementById('no-match')

        input.addEventListener('input', function () {
            var q = input.value.trim().toLowerCase()
            var visible = 0
            slots.forEach(function (slot) {
                var match = !q || (slot.getAttribute('data-names') || '').indexOf(q) !== -1
                slot.hidden = !match
                if (match) visible++
            })
            if (noMatch) noMatch.hidden = !(q && visible === 0 && slots.length > 0)
        })
    })()
</script>
@endsection

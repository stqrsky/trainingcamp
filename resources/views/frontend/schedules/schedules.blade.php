@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <span></span>
            <h4 class="title schedule">Calendar and Sparring Assignments</h4>
            <a href="{{ route('schedules.create') }}" type="button" class="ms-2 mb-1 close btn-add" name="button">
                <span aria-hidden="true" class="material-icons add">add</span>
            </a>
        </div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <h5 class="mb-0">{{ $date_format }}</h5>
            <button id="today-btn" type="button" class="btn btn-sm btn-outline-secondary">Today</button>
        </div>
        <form class="form-inline" id="date-form" data-no-spinner>
            <input class="form-control col-9" name="date" id="date" type="text" value="{{ $date }}" aria-label="Search">
            <button class="btn search btn-outline-dark col-3" type="submit">Search</button>
        </form>
    </div>
    <div class="card-body">
        @forelse($schedules as $schedule)
        <div class="card schedule mb-3">
            <div aria-live="assertive" aria-atomic="true">
                <div class="toast-header time d-flex align-items-center justify-content-between">
                    <strong class="text-muted">{{ $schedule->startEndTime }}</strong>
                    <div class="btn-group">
                        <button type="button" class="ms-2 mb-1 close" id="dropdown{{$schedule->id}}" data-bs-dismiss="toast" aria-label="Close" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span aria-hidden="true" class="material-icons">more_vert</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{$schedule->id}}">
                            <a class="dropdown-item" href="{{ route('schedules.edit', ['schedule' => $schedule->id]) }}">Edit</a>
                            <form action="{{ route('schedules.destroy', ['schedule' => $schedule->id]) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <input type="submit" class="dropdown-item" value="Delete" />
                            </form>
                        </div>
                    </div>
                </div>
                @foreach($schedule->participants as $key => $athlete)
                <div class="toast-header d-flex align-items-center @if($key > 0) float-right @endif">
                    <img src="{{
                        $athlete->userDetail && $athlete->userDetail->image ? 
                        asset($athlete->userDetail->image->file_name) :
                        asset('assets/default-avatar.svg')
                    }}" class="rounded-circle me-3" height="50px" width="50px" alt="avatar">
                    <strong class="me-auto">{{ $athlete->full_name }}</strong>
                </div>
                @endforeach
            </div>
        </div>
        @empty
        <div class="text-center py-5 text-muted">
            <span class="material-icons" style="font-size:48px">event_busy</span>
            <p class="mt-2">No sessions scheduled for this day.</p>
        </div>
        @endforelse
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
</script>
@endsection
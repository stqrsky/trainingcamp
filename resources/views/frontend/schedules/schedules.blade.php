@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="content mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <span></span>
            <h4 class="title">Calendar and Sparring Assignments</h4>
            <a href="{{ route('schedules.create') }}" type="button" class="ml-2 mb-1 close btn-add" name="button">
                <span aria-hidden="true" class="material-icons">add</span>
            </a>
        </div>
        <h4>{{ $date_format }}</h4>
        <form class="form-inline">
            <input class="form-control col-9" name="date" id="date" type="text" value="{{ $date }}" aria-label="Search">
            <button class="btn btn-outline-dark col-3" type="submit">Search</button>
        </form>
    </div>
    <div class="card-body">
        @foreach($schedules as $schedule)
        <div class="card schedule mb-3">
            <div aria-live="assertive" aria-atomic="true">
                <div class="toast-header time d-flex align-items-center justify-content-between">
                    <strong class="text-muted">{{ $schedule->startEndTime }}</strong>
                    <div class="btn-group">
                        <button type="button" class="ml-2 mb-1 close" id="dropdown{{$schedule->id}}" data-dismiss="toast" aria-label="Close" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
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
                        "https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU"
                    }}" class="rounded-circle mr-3" height="50px" width="50px" alt="avatar">
                    <strong class="mr-auto">{{ $athlete->full_name }}</strong>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
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
            locale: {
                format: 'DD/MM/YYYY'
            }
        }, function(start, end, label) {
            console.log(moment(start).format('YYYY-MM-DD'));
        });
    })
</script>
@endsection
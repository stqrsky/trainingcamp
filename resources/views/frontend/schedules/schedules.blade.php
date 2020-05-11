@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="content mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <span></span>
            <h4 class="title">Calendar and Sparring Assignments</h4>
            <button type="button" class="ml-2 mb-1 close btn-add" name="button">
                <span aria-hidden="true" class="material-icons">add</span>
            </button>
        </div>
        <h4>Monday, 11 May 2020</h4>
        <input type="text" class="form-control" name="calendar" id="calendar" value="10/05/2020" />
    </div>
    <div class="card-body">
        @for($athlete = 1; $athlete < 5; $athlete++) <div class="card schedule mb-3">
            <div aria-live="assertive" aria-atomic="true">
                <div class="toast-header time d-flex align-items-center justify-content-between">
                    <strong class="text-muted">8.15 - 9.00</strong>
                    <div class="btn-group">
                        <button type="button" class="ml-2 mb-1 close" id="dropdown{{$athlete}}" data-dismiss="toast" aria-label="Close" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span aria-hidden="true" class="material-icons">more_vert</span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{$athlete}}">
                            <form action="#" method="POST">
                                @csrf
                                <input type="submit" class="dropdown-item" value="Delete" />
                            </form>
                            <a class="dropdown-item" href="#">Edit</a>
                        </div>
                    </div>
                </div>
                <div class="toast-header d-flex align-items-center">
                    <img src="{{ asset('assets/images/carano.jpg') }}" class="rounded-circle mr-3" height="50px" width="50px" alt="avatar">
                    <strong class="mr-auto">Athlete One</strong>
                </div>
                <div class="toast-header d-flex align-items-center float-right">
                    <strong class="mr-3">Athete Two</strong>
                    <img src="{{ asset('assets/images/khabib.jpg') }}" class="rounded-circle mr-auto" height="50px" width="50px" alt="avatar">
                </div>
            </div>
    </div>
    @endfor
</div>
</div>
@endsection

@section('script')
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#calendar').daterangepicker({
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
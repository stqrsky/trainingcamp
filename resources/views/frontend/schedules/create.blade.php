@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="{{ asset('select2/css/select2.min.css') }}">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="content mb-4">
    <div class="card-body">
        @error('error')
        <div class="alert alert-danger" role="alert">
            {{ $message }}
        </div>
        @enderror
        <form action="{{ route('schedules.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('frontend.schedules.form')

            <button type="submit" class="btn btn-primary float-right">Add</button>
            <a href="{{ route('schedules.index') }}" type="button" class="btn btn-warning float-right mr-1">Cancel</a>
        </form>
    </div>
</div>
</div>
@endsection

@section('script')
<script src="{{ asset('select2/js/select2.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#first_athlete, #second_athlete').select2({
            placeholder: 'Select Athlete',
            allowClear: true
        });
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

    function selectFile(event) {
        var input = event.target
        var filename = $(input)[0].files[0].name
        $('#filename').html(filename)
    }
</script>
@endsection
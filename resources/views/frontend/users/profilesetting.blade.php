@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="{{ asset('select2/css/select2.min.css') }}">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="bgprosetting col-12 pb-3 mb-5 content">
            @error('error')
            <div class="alert alert-danger" role="alert">
                {{ $message }}
            </div>
            @enderror
            <form method="POST" action="{{ route('user.profile.setting.put') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('frontend.users.formprofile')
                <button type="submit" class="btn edit btn-outline-dark float-right">Update Profile</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('select2/js/select2.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        $('#skills').select2({
            placeholder: 'Select Your Skills'
        });
        $('#date_of_birth').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            minYear: 1901,
            maxYear: parseInt(moment().format('YYYY'), 10),
            locale: {
                format: 'DD/MM/YYYY'
            }
        });
    })

    function selectFile(event) {
        var input = event.target
        var filename = $(input)[0].files[0].name
        $('#filename').html(filename)
    }
</script>
@endsection
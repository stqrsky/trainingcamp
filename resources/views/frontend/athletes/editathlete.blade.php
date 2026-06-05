@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="{{ asset('select2/css/select2.min.css') }}">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="athletebg col-12 content mb-5 pb-3">
            @error('error')
            <div class="alert alert-danger" role="alert">
                {{ $message }}
            </div>
            @enderror
            <form method="POST" action="{{ route('user.athletes.update', ['id' => $user->id]) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                @include('frontend.athletes.form', ['edit' => true])
                <button type="submit" class="btn edit update float-right ms-2">Update</button>
                <button type="submit" class="btn delete btn-danger float-right">Delete</button>
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
            placeholder: 'Select Skills'
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
    document.getElementById('picture').addEventListener('change', function() {
        var file = this.files[0]
        if (!file) return
        document.getElementById('filename').textContent = file.name
        var preview = document.getElementById('picture-preview')
        var objectUrl = URL.createObjectURL(file)
        preview.src = objectUrl
        preview.onload = function() { URL.revokeObjectURL(objectUrl) }
    })
</script>
@endsection
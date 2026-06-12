@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<h4 class="title mt-4">New Task</h4>
<div class="content create-schedule mt-1">
    <div class="card-body">
        @error('error')
        <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            @include('frontend.tasks.form')
            <button type="submit" class="btn create btn-outline-dark float-right">Save</button>
            <a href="{{ route('tasks.index') }}" class="btn btn-warning btn-outline-dark float-right me-1">Cancel</a>
        </form>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
$(document).ready(function () {
    $('#due_date').daterangepicker({
        singleDatePicker: true, showDropdowns: true,
        autoUpdateInput: false,
        locale: { format: 'DD/MM/YYYY', cancelLabel: 'Clear' }
    }, function (s) { $('#due_date').val(s.format('DD/MM/YYYY')); });
    $('#due_date').on('cancel.daterangepicker', function () { $(this).val(''); });
});
</script>
@endsection

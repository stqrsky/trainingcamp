@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<h4 class="title mt-4">Edit Task</h4>
<div class="content create-schedule mt-1">
    <div class="card-body">
        @error('error')
        <div class="alert alert-danger">{{ $message }}</div>
        @enderror
        {{-- Kept outside the update form: nested forms break both buttons --}}
        <form id="task-delete-form" action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
        <form action="{{ route('tasks.update', $task->id) }}" method="POST">
            @csrf
            @method('PUT')
            @include('frontend.tasks.form')
            <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="submit" form="task-delete-form" class="btn btn-outline-danger btn-sm">Delete</button>
                <div>
                    <a href="{{ route('tasks.index') }}" class="btn btn-warning btn-outline-dark me-1">Cancel</a>
                    <button type="submit" class="btn create btn-outline-dark">Update</button>
                </div>
            </div>
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
    @if($task->due_date_format)
    $('#due_date').val('{{ $task->due_date_format }}');
    @endif
});
</script>
@endsection

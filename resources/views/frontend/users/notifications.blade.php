@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content pb-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <h4 class="title">Reminder settings</h4>
            </div>
            @if(session()->has('msg'))
            <div class="alert alert-success" role="status">{{ session('msg') }}</div>
            @endif
            <div class="card form">
                <div class="card-body">
                    <p class="text-muted small">Reminders appear under the bell in the top bar. They are calculated live and disappear once a task is done or a sparring is over.</p>
                    <form method="POST" action="{{ route('user.notifications.put') }}">
                        @csrf
                        @method('PUT')
                        <fieldset>
                            <legend class="visually-hidden">Reminder types</legend>
                            @foreach($types as $key => $label)
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="reminder-{{ $key }}"
                                       name="reminders[{{ $key }}]" value="1" @checked($user->wantsReminder($key))>
                                <label class="form-check-label" for="reminder-{{ $key }}">{{ $label }}</label>
                            </div>
                            @endforeach
                        </fieldset>
                        <button type="submit" class="btn create btn-outline-dark float-end">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

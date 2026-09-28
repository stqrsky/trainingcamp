@extends('frontend.layouts.app')

@section('content')
<h4 class="title mt-4">New Project</h4>
<div class="content create-schedule mt-1">
    <div class="card-body">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            @include('frontend.projects.form')
            <button type="submit" class="btn create btn-outline-dark float-end">Create</button>
            <a href="{{ route('projects.index') }}" class="btn btn-warning btn-outline-dark float-end me-1">Cancel</a>
        </form>
    </div>
</div>
@endsection

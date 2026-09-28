@extends('frontend.layouts.app')

@section('content')
<h4 class="title mt-4">Edit Project</h4>
<div class="content create-schedule mt-1">
    <div class="card-body">
        {{-- Separate delete form; nested forms would break both buttons --}}
        <form id="project-delete-form" action="{{ route('projects.destroy', $project) }}" method="POST" class="d-none">
            @csrf
            @method('DELETE')
        </form>
        <form action="{{ route('projects.update', $project) }}" method="POST">
            @csrf
            @method('PUT')
            @include('frontend.projects.form')
            <div class="d-flex justify-content-between align-items-center mt-3">
                <button type="submit" form="project-delete-form" class="btn btn-outline-danger btn-sm">Delete</button>
                <div>
                    <a href="{{ route('projects.show', $project) }}" class="btn btn-warning btn-outline-dark me-1">Cancel</a>
                    <button type="submit" class="btn create btn-outline-dark">Save</button>
                </div>
            </div>
            <p class="text-muted small mt-2 mb-0">Deleting a project keeps its tasks; they are only unlinked.</p>
        </form>
    </div>
</div>
@endsection

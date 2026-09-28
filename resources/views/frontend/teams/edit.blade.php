@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content pb-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <h4 class="title">Edit Team</h4>
            </div>
            <div class="card form">
                <div class="card-body">
                    @error('error')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror
                    <form method="POST" action="{{ route('teams.update') }}">
                        @csrf
                        @method('PUT')
                        @include('frontend.teams.form')
                        <button type="submit" class="btn create btn-outline-dark float-right">Save</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

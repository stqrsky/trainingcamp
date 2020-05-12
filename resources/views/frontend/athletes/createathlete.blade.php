@extends('frontend/layouts/app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            <form method="POST" action="{{ route('user.athletes.create.post') }}">
                @csrf
                @include('frontend.athletes.form')
                <button type="submit" class="btn btn-primary float-right">Create</button>
            </form>

        </div>
    </div>
</div>
@endsection
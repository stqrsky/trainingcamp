@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            <form method="POST" action="{{ route('user.detail.update', ['id' => $id]) }}">
                @csrf
                @method('PUT')
                @include('frontend.athletes.form')
                <button type="submit" class="btn btn-primary float-right ml-2">Update</button>
                <button type="submit" class="btn btn-danger float-right">Delete</button>
            </form>

        </div>
    </div>
</div>
@endsection

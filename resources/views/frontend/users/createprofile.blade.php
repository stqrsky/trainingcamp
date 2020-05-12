@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 pb-3 content">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('user.setting.post') }}">
                        @csrf
                        @include('frontend.users.formprofile')
                        <button type="submit" class="btn btn-primary float-right">Create</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
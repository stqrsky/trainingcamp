@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 pb-3 mb-5 content">

            <div class="card-body">

                    <form method="POST" action="{{ route('user.setting.post') }}">
                        @csrf
                        @include('frontend.users.formprofile')
                        <button type="submit" class="btn btn-primary float-right ml-2">Update Profile</button>
                        <button type="submit" class="btn btn-danger float-right">Sign Out</button>
                    </form>

                </div>

        </div>
    </div>
</div>
@endsection

@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 pb-3 mb-5 content">
            <form method="POST" action="{{ route('user.account.setting.put') }}">
                @csrf
                @method('PUT')
                @include('frontend.users.formprofile')
                <button type="submit" class="btn btn-primary float-right">Update Profile</button>
            </form>
        </div>
    </div>
</div>
@endsection

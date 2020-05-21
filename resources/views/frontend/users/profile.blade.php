@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 pb-3 mb-5 content">

            <div aria-live="assertive" aria-atomic="true">
                <div class="toast-header me d-flex align-items-center">
                    <img src="{{
                        $user->userDetail && $user->userDetail->image ? 
                        asset($user->userDetail->image->file_name) :
                        "https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg"
                    }}" class="rounded-circle mr-3" height="70px" width="70px" alt="avatar">
                    <div class="d-flex flex-column">
                        <strong class="mr-auto text-dark">{{ $user->full_name }}</strong>
                        <strong class="mr-auto">{{ $user->team ? $user->team->name : '' }}</strong>
                    </div>
                </div>
                <div class="toast-body about">
                    <h4 class="card-title font-weight-bold mb-2 andreas">Coach</h4>
                    {!! $user->userDetail ? $user->userDetail->about : '' !!}
                </div>
                <div class="toast-body skill">
                    @foreach($user->skills as $skill)
                    <span class="badge badge-warning">{{ $skill->name }}</span>
                    @endforeach
                </div>
                <div class="toast-body d-flex justify-content-between">
                    <a href="{{ route('user.profile.setting') }}" type="button" class="btn profilebtn btn-outline-dark">Profile Settings</a>
                    <a href="{{ route('user.account.setting') }}" type="button" class="btn profilebtn btn-outline-dark">Account Settings</a>
                </div>
                <div class="toast-body sign-out d-flex justify-content-center">
                    <form action="{{ route('user.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn bg-danger text-white glyphicon glyphicon-log-out">Sign Out</button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
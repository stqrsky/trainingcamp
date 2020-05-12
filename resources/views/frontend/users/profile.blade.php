@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 pb-3 mb-5 content">

            <div aria-live="assertive" aria-atomic="true">
                <div class="toast-header d-flex align-items-center">
                    <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" class="rounded-circle mr-3" height="70px" width="70px" alt="avatar">
                    <div class="d-flex flex-column">
                        <strong class="mr-auto">Full Name</strong>
                        <strong class="mr-auto">Team</strong>
                    </div>
                </div>
                <div class="toast-body">
                    <h4 class="card-title font-weight-bold mb-2 andreas">Coach</h4>
                    See? Just like this.
                </div>
                <div class="toast-body">
                    @for($skill = 1; $skill < 10; $skill++) <span class="badge badge-secondary">Skill {{$skill}}</span>
                        @endfor
                </div>
                <div class="toast-body d-flex justify-content-between">
                    <a href="{{ route('user.profile.setting') }}" type="button" class="btn profilebtn1 btn-outline-dark">Profile Settings</a>
                    <a href="{{ route('user.account.setting') }}" type="button" class="btn profilebtn2 btn-outline-dark">Account Settings</a>
                </div>
                <div class="toast-body d-flex justify-content-center">
                    <a type="button" class="btn bg-danger text-white glyphicon glyphicon-log-out">Sign Out</a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
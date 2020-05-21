@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 pb-3 mb-5 content">

            <div aria-live="assertive" aria-atomic="true">
                <div class="toast-header d-flex align-items-center">
                    <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" class="rounded-circle mr-3" height="70px" width="70px" alt="avatar">
                    <div class="d-flex flex-column">
                        <strong class="mr-auto">
                            {{ $user->full_name }}
                        </strong>
                        <strong class="mr-auto">{{ $team->name }}</strong>
                    </div>
                </div>
                <div class="toast-body">
                    <dl class="row">
                        <dt class="col-sm-4">Email</dt>
                        <dd class="col-sm-8">{{ $user->email }}</dd>

                        <dt class="col-sm-4">Nick Name</dt>
                        <dd class="col-sm-8">{{ $user->userDetail->nick_name }}</dd>

                        <dt class="col-sm-4">Age</dt>
                        <dd class="col-sm-8">{{ $user->userDetail->age }}</dd>

                        <dt class="col-sm-4">Height</dt>
                        <dd class="col-sm-8">{{ $user->userDetail->height }} cm</dd>

                        <dt class="col-sm-4 text-truncate">Weight</dt>
                        <dd class="col-sm-8">{{ $user->userDetail->height }} kg</dd>
                    </dl>
                </div>
                <div class="toast-body">
                    <h5>About</h5>
                    {!! $user->userDetail->about !!}
                </div>
                <div class="toast-body">
                    <h5>Skills</h5>
                    @foreach($user->skills as $skill)
                    <span class="badge badge-warning">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
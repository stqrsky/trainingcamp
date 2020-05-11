@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="{{ asset('css/athlete.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <span></span>
                <h4 class="title">Team 2020</h4>
                <button type="button" class="ml-2 mb-1 close btn-add" name="button">
                    <span aria-hidden="true" class="material-icons">add</span>
                </button>
            </div>
            <div class="head-title d-flex align-items-center justify-content-between my-3">
                <h4 class="text-dark mb-0">Coaches</h4>
            </div>
            <div class="list-team-members">
                <ul>
                    <li class="d-flex align-items-center mb-4">
                        <div class="cont-user-wrapper col-lg-3">
                            <div class="d-flex align-items-center  w-30">
                                <div class="user-photo">
                                    <img class="img-fluid" src="{{ asset('assets/images/2.jpg') }}" alt="img avatar">
                                </div>
                                <div class="name ml-3">
                                    <span class="font-weight-bold">Ronald Mcdonald</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="head-title d-flex align-items-center justify-content-between my-3">
                <h4 class="text-dark mb-0 mt-4">Athletes</h4>
            </div>
            <div class="head-title mb-3 my-3">
                <form class="form-inline">
                    <input class="form-control col-9" name="find" type="search" placeholder="Search Athletes" aria-label="Search">
                    <button class="btn btn-outline-dark col-3" type="submit">Search</button>
                </form>
            </div>
            <div class="list-team-members">
                <ul>
                    @for($user = 4; $user < 9; $user++) <li class="d-flex align-items-center justify-content-between mb-2">
                        <a href="#">
                            <img src="{{ asset('assets/images/1.jpg') }}" class="rounded-circle mr-3" height="50px" width="50px" alt="avatar">
                            <strong class="mr-auto">Who am I</strong>
                        </a>
                        <div class="btn-group float-right">
                            <a type="button" class="ml-2 mb-1 close" id="dropdown{{$user}}" data-dismiss="toast" aria-label="Close" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span aria-hidden="true" class="material-icons">more_vert</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{$user}}">
                                <button class="dropdown-item" onClick="">Edit</button>
                                <form action="" method="GET">
                                    <input type="submit" class="dropdown-item" value="Remove" />
                                </form>
                            </div>
                        </div>
                        </li>
                        @endfor
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
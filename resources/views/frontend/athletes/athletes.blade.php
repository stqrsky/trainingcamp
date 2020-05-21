@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="{{ asset('css/athlete.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            @if($team)
            @error('error')
            <div class="alert alert-danger" role="alert">
                {{ $message }}
            </div>
            @enderror
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <span></span>
                <h4 class="title-team"><u>{{ $team->name }}</u></h4>
                <a href="{{ route('user.athletes.create') }}" type="button" class="ml-2 mb-1 close btn-add" name="button">
                    <span aria-hidden="true" class="material-icons add">add</span>
                </a>
            </div>
            <div class="head-title d-flex align-items-center justify-content-between my-3">
                <h4 class="text-dark mb-0">Coaches</h4>
            </div>
            <div class="list-team-members">
                <ul>
                    @foreach($team->coaches as $coach)
                    <li class="d-flex align-items-center mb-4">
                        <div class="cont-user-wrapper col-lg-3">
                            <div class="d-flex align-items-center w-30">
                                <div class="user-photo">
                                    <img class="img-fluid" src="{{
                                        $coach->userDetail && $coach->userDetail->image ? 
                                        asset($coach->userDetail->image->file_name) :
                                        "https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU"
                                    }}" alt="img avatar">
                                </div>
                                <div class="name ml-3">
                                    <span class="font-weight-bold">
                                        {{ $coach->full_name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="head-title d-flex align-items-center justify-content-between my-3">
                <h4 class="text-dark mb-0 mt-4">Athletes</h4>
            </div>
            <div class="head-title mb-3 my-3">
                <form class="form-inline">
                    <input class="form-control col-9" name="search" type="search" placeholder="Search Athletes" value="{{ $search }}" aria-label="Search">
                    <button class="btn search btn-outline-dark col-3 text-dark" type="submit">Search</button>
                </form>
            </div>
            <div class="list-team-members">
                <ul>
                    @foreach($team->athletes as $athlete)
                    <li class="justify-content-between mb-3">
                        <div class="col-12 d-flex align-items-center justify-content-between">
                            <a href="{{ route('user.athletes.detail', ['id' => $athlete->id]) }}">
                                <div class="d-flex align-items-center">
                                    <img src="{{
                                        $athlete->userDetail && $athlete->userDetail->image ? 
                                        asset($athlete->userDetail->image->file_name) :
                                        "https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU"
                                    }}" class="rounded-circle mr-3" height="70px" width="70px" alt="avatar">
                                    <div class="d-flex flex-column text-truncate">
                                        <strong class="mr-auto">
                                            {{ $athlete->full_name }}
                                        </strong>
                                        <strong class="mr-auto">{{ $athlete->userDetail->nick_name }}</strong>
                                    </div>
                                </div>
                            </a>
                            <div class="btn-group float-right">
                                <a type="button" class="ml-2 mb-1 close" id="dropdown{{$athlete->id}}" data-dismiss="toast" aria-label="Close" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <span aria-hidden="true" class="material-icons">more_vert</span>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{$athlete->id}}">
                                    <a href="{{ route('user.athletes.edit', ['id' => $athlete->id]) }}" type="button" class="dropdown-item" onClick="">Edit</a>
                                    <form action="{{ route('user.athletes.delete', ['id' => $athlete->id]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <input type="submit" class="dropdown-item" value="Remove" />
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex align-items-center mt-3">
                            @foreach($athlete->skills as $skill)
                            <span class="badge badge-warning mr-1">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            @else
            <div class="d-flex justify-content-center align-items-center mb-3 head-title">
                <a href="{{ route('user.profile') }}" type="button" class="btn btn-primary">Complete Your Profile</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
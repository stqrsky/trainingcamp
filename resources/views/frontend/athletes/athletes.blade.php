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
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            <div class="d-flex justify-content-between align-items-center mb-2 head-title">
                <h4 class="title-team">{{ $team->name }}</h4>
                <a href="{{ route('user.athletes.create') }}" type="button" class="close btn-add" aria-label="Add member">
                    <span aria-hidden="true" class="material-icons add">add</span>
                </a>
            </div>

            {{-- Coaches --}}
            <p class="list-section-label">Coaches</p>
            <div class="list-team-members">
                <ul>
                    @forelse($team->coaches as $coach)
                    @php
                        $parts    = array_filter(explode(' ', trim($coach->full_name)));
                        $initials = collect($parts)->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        $hasImg   = $coach->userDetail && $coach->userDetail->image;
                    @endphp
                    <li>
                        <a href="{{ route('user.athletes.detail', ['id' => $coach->id]) }}"
                           class="d-flex align-items-center gap-3">
                            <span class="tc-avatar tc-avatar--md">
                                @if($hasImg)
                                <img src="{{ asset($coach->userDetail->image->file_name) }}" alt="{{ $coach->full_name }}">
                                @else{{ $initials ?: '?' }}@endif
                            </span>
                            <div class="d-flex flex-column">
                                <strong>{{ $coach->full_name }}</strong>
                                <span class="tc-badge tc-badge--coach mt-1" style="align-self:flex-start">Coach</span>
                            </div>
                        </a>
                    </li>
                    @empty
                    <li class="text-muted tc-empty" style="border-style:dashed">No coaches yet.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Athletes --}}
            <p class="list-section-label">Athletes</p>
            <form class="tc-search mb-3" data-no-spinner>
                <input class="form-control" name="search" type="search" placeholder="Search athletes…"
                       value="{{ $search }}" aria-label="Search athletes">
                <button class="btn search" type="submit">
                    <span class="material-icons align-middle" style="font-size:18px">search</span>
                </button>
            </form>

            <div class="list-team-members">
                <ul>
                    @forelse($team->athletes as $athlete)
                    @php
                        $parts    = array_filter(explode(' ', trim($athlete->full_name)));
                        $initials = collect($parts)->take(2)->map(fn($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                        $hasImg   = $athlete->userDetail && $athlete->userDetail->image;
                    @endphp
                    <li>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <a href="{{ route('user.athletes.detail', ['id' => $athlete->id]) }}"
                               class="d-flex align-items-center gap-3 text-truncate flex-grow-1">
                                <span class="tc-avatar tc-avatar--lg">
                                    @if($hasImg)
                                    <img src="{{ asset($athlete->userDetail->image->file_name) }}" alt="{{ $athlete->full_name }}">
                                    @else{{ $initials ?: '?' }}@endif
                                </span>
                                <div class="d-flex flex-column text-truncate">
                                    <strong class="text-truncate">{{ $athlete->full_name }}</strong>
                                    @if($athlete->userDetail && $athlete->userDetail->nick_name)
                                    <small class="text-muted text-truncate">“{{ $athlete->userDetail->nick_name }}”</small>
                                    @endif
                                </div>
                            </a>
                            <div class="d-flex align-items-center gap-1">
                                <a href="{{ route('schedules.create') }}" class="tc-assign-btn" title="Assign sparring">
                                    <span class="material-icons" aria-hidden="true">sports_kabaddi</span>
                                    <span class="d-none d-sm-inline">Assign</span>
                                </a>
                                <div class="btn-group">
                                    <a type="button" class="close" id="dropdown{{$athlete->id}}"
                                       data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Athlete actions">
                                        <span aria-hidden="true" class="material-icons">more_vert</span>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown{{$athlete->id}}">
                                        <a href="{{ route('user.athletes.edit', ['id' => $athlete->id]) }}" class="dropdown-item">Edit</a>
                                        <form action="{{ route('user.athletes.delete', ['id' => $athlete->id]) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <input type="submit" class="dropdown-item" value="Remove" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($athlete->skills->count())
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            @foreach($athlete->skills as $skill)
                            <span class="tc-badge tc-badge--skill">{{ $skill->name }}</span>
                            @endforeach
                        </div>
                        @endif
                    </li>
                    @empty
                    <li class="text-center py-4 text-muted tc-empty" style="border-style:dashed">
                        <span class="material-icons" style="font-size:40px">people_outline</span>
                        <p class="mt-1">No athletes yet. Tap <strong>+</strong> to add one.</p>
                    </li>
                    @endforelse
                </ul>
            </div>
            @else
            <div class="d-flex justify-content-center align-items-center py-5 head-title">
                <a href="{{ route('user.profile') }}" type="button" class="btn btn-primary">Complete Your Profile</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

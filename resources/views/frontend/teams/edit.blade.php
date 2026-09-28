@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content pb-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <h4 class="title">Edit Team</h4>
            </div>
            <div class="card form">
                <div class="card-body">
                    @error('error')
                    <div class="alert alert-danger" role="alert">{{ $message }}</div>
                    @enderror
                    <form method="POST" action="{{ route('teams.update') }}">
                        @csrf
                        @method('PUT')
                        @include('frontend.teams.form')
                        <button type="submit" class="btn create btn-outline-dark float-right">Save</button>
                    </form>
                </div>
            </div>

            {{-- Skill catalog of this team: members get a level per skill --}}
            <div class="card form mt-3">
                <div class="card-body">
                    <h2 class="tc-task-group-header" id="team-skills">Team skills</h2>
                    <p class="text-muted small">Skills your members can be rated in, e.g. Boxing, Clinch or Conditioning.</p>
                    <ul class="tc-skill-catalog" aria-labelledby="team-skills">
                        @forelse($team->skills as $skill)
                        <li>
                            <span class="text-truncate">{{ $skill->name }}</span>
                            <form method="POST" action="{{ route('teams.skills.destroy', $skill) }}" class="mb-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="close" aria-label="Remove skill {{ $skill->name }}">
                                    <span class="material-icons" aria-hidden="true">close</span>
                                </button>
                            </form>
                        </li>
                        @empty
                        <li class="tc-skill-catalog-empty">No skills yet.</li>
                        @endforelse
                    </ul>
                    <form method="POST" action="{{ route('teams.skills.store') }}" class="tc-search mt-2">
                        @csrf
                        <label class="visually-hidden" for="skill_name">New skill</label>
                        <input type="text" class="form-control @error('skill_name') is-invalid @enderror" id="skill_name"
                               name="skill_name" placeholder="Add a skill…" maxlength="50" value="{{ old('skill_name') }}" required>
                        <button class="btn search" type="submit" aria-label="Add skill">
                            <span class="material-icons align-middle" style="font-size:18px" aria-hidden="true">add</span>
                        </button>
                    </form>
                    @error('skill_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

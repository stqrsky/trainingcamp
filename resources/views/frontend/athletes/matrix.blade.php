@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title">Skill Matrix</h4>
            <a href="{{ route('teams.edit') }}" class="btn btn-sm btn-link p-0 tc-link-strong">Manage skills</a>
        </div>
        <p class="text-muted small">{{ $team->name }} · active members · B = Beginner, I = Intermediate, A = Advanced, E = Expert</p>

        @if($skills->isEmpty())
        <div class="text-center py-5 text-muted tc-empty">
            <span class="material-icons" style="font-size:48px" aria-hidden="true">grid_view</span>
            <p class="mt-2">This team has no skills yet. <a href="{{ route('teams.edit') }}">Add skills</a> to build the matrix.</p>
        </div>
        @elseif($members->isEmpty())
        <p class="text-muted">No active members yet.</p>
        @else
        @php
            $levelCell = function ($member, $skill) {
                return $member->skills->firstWhere('id', $skill->id)?->pivot->level;
            };
        @endphp

        {{-- Tablet and desktop: classic matrix --}}
        <div class="tc-matrix-wrap d-none d-md-block">
            <table class="tc-matrix">
                <caption class="visually-hidden">Skill levels of active members</caption>
                <thead>
                    <tr>
                        <th scope="col">Member</th>
                        <th scope="col">Level</th>
                        @foreach($skills as $skill)
                        <th scope="col">{{ $skill->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($members as $row)
                    <tr>
                        <th scope="row">
                            <a href="{{ route('user.athletes.detail', ['id' => $row['member']->id]) }}">{{ $row['member']->full_name }}</a>
                            <small>{{ $row['role'] }}</small>
                        </th>
                        <td>{{ $row['member']->userDetail?->experience_label ?? '–' }}</td>
                        @foreach($skills as $skill)
                        @php $level = $levelCell($row['member'], $skill); @endphp
                        <td>
                            @if($level)
                            <span class="tc-level tc-level--{{ $level }}" title="{{ \App\Models\Skill::LEVELS[$level] }}">
                                {{ strtoupper(substr($level, 0, 1)) }}<span class="visually-hidden"> {{ \App\Models\Skill::LEVELS[$level] }}</span>
                            </span>
                            @else
                            <span class="text-muted" aria-label="Not rated">·</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Phones: one card per member instead of a wide table --}}
        <ul class="tc-matrix-cards d-md-none">
            @foreach($members as $row)
            <li>
                <a href="{{ route('user.athletes.detail', ['id' => $row['member']->id]) }}" class="fw-700">{{ $row['member']->full_name }}</a>
                <small class="text-muted">{{ $row['role'] }} · {{ $row['member']->userDetail?->experience_label ?? 'Level not set' }}</small>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    @foreach($skills as $skill)
                    @php $level = $levelCell($row['member'], $skill); @endphp
                    @if($level)
                    <span class="tc-badge tc-badge--skill">{{ $skill->name }} · {{ \App\Models\Skill::LEVELS[$level] }}</span>
                    @endif
                    @endforeach
                </div>
            </li>
            @endforeach
        </ul>
        @endif
    </div>
</div>
@endsection

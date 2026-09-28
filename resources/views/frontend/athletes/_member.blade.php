@php
    $isSelf   = $member->is(auth()->user());
    $isActive = (bool) $member->pivot->active;
    $hasImg   = $member->userDetail && $member->userDetail->image;
@endphp
<li class="{{ $isActive ? '' : 'tc-member-inactive' }}">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <a href="{{ route('user.athletes.detail', ['id' => $member->id]) }}"
           class="d-flex align-items-center gap-3 text-truncate flex-grow-1">
            <span class="tc-avatar tc-avatar--md">
                @if($hasImg)
                <img src="{{ asset($member->userDetail->image->file_name) }}" alt="{{ $member->full_name }}">
                @else{{ $member->initials }}@endif
            </span>
            <div class="d-flex flex-column text-truncate">
                <strong class="text-truncate">{{ $member->full_name }}</strong>
                @if($member->userDetail && $member->userDetail->nick_name)
                <small class="text-muted text-truncate">“{{ $member->userDetail->nick_name }}”</small>
                @endif
                <div class="d-flex flex-wrap gap-1 mt-1">
                    <span class="tc-badge tc-badge--{{ $role }}" style="align-self:flex-start">{{ ucfirst($role) }}</span>
                    @if($isSelf)<span class="tc-badge tc-badge--skill">You</span>@endif
                    @unless($isActive)<span class="tc-status tc-status--cancelled">Inactive</span>@endunless
                    @if($member->userDetail?->experience_label)<span class="tc-status">{{ $member->userDetail->experience_label }}</span>@endif
                </div>
            </div>
        </a>
        <div class="d-flex align-items-center gap-1">
            @if($role === 'athlete' && $isActive)
            <a href="{{ route('schedules.create', ['athlete' => $member->id]) }}" class="tc-assign-btn"
               title="Assign sparring" aria-label="Assign sparring for {{ $member->full_name }}">
                <span class="material-icons" aria-hidden="true">sports_kabaddi</span>
                <span class="d-none d-sm-inline">Assign</span>
            </a>
            @endif
            @if($isSelf)
            <a href="{{ route('user.profile.setting') }}" class="close" aria-label="Edit your profile" title="Edit your profile">
                <span aria-hidden="true" class="material-icons">edit</span>
            </a>
            @else
            <div class="btn-group">
                <button type="button" class="close" id="member-actions-{{ $member->id }}"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                        aria-label="Actions for {{ $member->full_name }}">
                    <span aria-hidden="true" class="material-icons">more_vert</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="member-actions-{{ $member->id }}">
                    <a href="{{ route('user.athletes.edit', ['id' => $member->id]) }}" class="dropdown-item">Edit</a>
                    <form action="{{ route('user.athletes.status', ['id' => $member->id]) }}" method="POST" data-no-spinner>
                        @csrf
                        <button type="submit" class="dropdown-item">{{ $isActive ? 'Set inactive' : 'Set active' }}</button>
                    </form>
                    <div class="dropdown-divider"></div>
                    <form action="{{ route('user.athletes.delete', ['id' => $member->id]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="dropdown-item text-danger">Remove from team</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
    @if($member->skills->count())
    <div class="d-flex flex-wrap gap-1 mt-2">
        @foreach($member->skills as $skill)
        <span class="tc-badge tc-badge--skill">{{ $skill->name }} · {{ \App\Models\Skill::LEVELS[$skill->pivot->level] ?? $skill->pivot->level }}</span>
        @endforeach
    </div>
    @endif
</li>

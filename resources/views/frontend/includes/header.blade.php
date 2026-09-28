<nav class="navbar fixed-top navbar-expand-lg d-flex align-items-center">
    <a class="d-flex align-items-center gap-2 text-decoration-none" href="{{ route('home') }}">
        <img class="img" src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Trainingcamp" width="40" height="40">
        <span class="tc-brand {{ $headerCurrentTeam ? 'd-none d-sm-inline' : '' }}">Trainingcamp</span>
    </a>
    @if($headerCurrentTeam)
    <div class="dropdown tc-team-switch">
        <button type="button" class="tc-team-switch-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Current team: {{ $headerCurrentTeam->name }}. Switch team">
            <span class="text-truncate">{{ $headerCurrentTeam->name }}</span>
            <span class="material-icons" aria-hidden="true">expand_more</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header">Your teams</h6></li>
            @foreach($headerTeams as $headerTeam)
            @php $isCurrent = $headerTeam->id === $headerCurrentTeam->id; @endphp
            <li>
                <form method="POST" action="{{ route('teams.switch', $headerTeam) }}" data-no-spinner>
                    @csrf
                    <button type="submit" class="dropdown-item {{ $isCurrent ? 'active' : '' }}" @if($isCurrent) aria-current="true" @endif>
                        <span class="text-truncate">{{ $headerTeam->name }}</span>
                        @if($isCurrent)<span class="material-icons ms-auto" aria-hidden="true">check</span>@endif
                    </button>
                </form>
            </li>
            @endforeach
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item" href="{{ route('teams.edit') }}">
                    <span class="material-icons" aria-hidden="true">edit</span> Edit team
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="{{ route('teams.create') }}">
                    <span class="material-icons" aria-hidden="true">add</span> New team
                </a>
            </li>
        </ul>
    </div>
    @endif
    <button type="button" class="tc-theme-toggle" data-tc-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode">
        <span class="material-icons tc-icon-light">dark_mode</span>
        <span class="material-icons tc-icon-dark">light_mode</span>
    </button>
</nav>

<nav class="navbar fixed-top navbar-expand-lg d-flex align-items-center">
    <a class="d-flex align-items-center gap-2 text-decoration-none" href="{{ route('home') }}">
        <img class="img" src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Trainingcamp" width="40" height="40">
        <span class="tc-brand {{ $headerCurrentTeam ? 'd-none d-sm-inline' : '' }}">Trainingcamp</span>
    </a>
    <button type="button" class="tc-theme-toggle tc-search-btn" data-palette-open
            aria-label="Search and commands (Ctrl+K)" title="Search (⌘K / Ctrl+K)">
        <span class="material-icons" aria-hidden="true">search</span>
    </button>
    @if($headerReminders)
    @php $reminderCount = $headerReminders['count']; @endphp
    <div class="dropdown tc-reminders">
        <button type="button" class="tc-theme-toggle tc-bell" data-bs-toggle="dropdown" aria-expanded="false"
                aria-label="Reminders: {{ $reminderCount }}">
            <span class="material-icons" aria-hidden="true">{{ $reminderCount ? 'notifications_active' : 'notifications_none' }}</span>
            @if($reminderCount)<span class="tc-bell-count" aria-hidden="true">{{ $reminderCount > 9 ? '9+' : $reminderCount }}</span>@endif
        </button>
        <div class="dropdown-menu dropdown-menu-end tc-reminders-menu">
            <h6 class="dropdown-header">Reminders</h6>
            @forelse($headerReminders['items'] as $reminder)
            <a class="dropdown-item tc-reminder tc-reminder--{{ $reminder['tone'] }}" href="{{ $reminder['url'] }}">
                <span class="material-icons" aria-hidden="true">{{ $reminder['icon'] }}</span>
                <span class="tc-reminder-text">
                    <strong>{{ $reminder['title'] }}</strong>
                    <small>{{ $reminder['subtitle'] }}</small>
                </span>
            </a>
            @empty
            <p class="px-3 mb-1 small text-muted">Nothing needs your attention right now.</p>
            @endforelse
            @if($reminderCount > $headerReminders['items']->count())
            <p class="px-3 mb-1 small text-muted">+{{ $reminderCount - $headerReminders['items']->count() }} more</p>
            @endif
            <div class="dropdown-divider"></div>
            <a class="dropdown-item small" href="{{ route('activity') }}">Team activity</a>
            <a class="dropdown-item small" href="{{ route('user.notifications') }}">Reminder settings</a>
        </div>
    </div>
    @endif
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

@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <h4 class="title overview">Bootcamp Overview</h4>
                <a href="{{ route('notification.create') }}" type="button" class="close btn-add" aria-label="Add post">
                    <span aria-hidden="true" class="material-icons add">add</span>
                </a>
            </div>

            @if(!$team)
            {{-- Greeting (the dashboard below replaces it once a team exists) --}}
            <div class="tc-banner tc-banner--greeting">
                <span class="material-icons" aria-hidden="true">sports_mma</span>
                <div class="tc-banner-body">
                    <strong>Welcome back to your bootcamp</strong>
                    <p>Here's the latest from your team — announcements, updates and notes.</p>
                </div>
            </div>

            <div class="tc-banner tc-banner--warning">
                <span class="material-icons" aria-hidden="true">groups</span>
                <div class="tc-banner-body">
                    <strong>Set up your team</strong>
                    <p>Complete your profile and create your first team to plan tasks and sparrings.</p>
                    <a href="{{ route('user.setting') }}" class="btn btn-sm btn-primary mt-2">Get started</a>
                </div>
            </div>
            @else
            {{-- Key numbers for the active team --}}
            <div class="tc-stat-grid" aria-label="Team summary">
                <a href="{{ route('tasks.index') }}" class="tc-stat">
                    <span class="tc-stat-value">{{ $stats['open'] }}</span>
                    <span class="tc-stat-label">Open tasks</span>
                </a>
                <a href="{{ route('tasks.index') }}" class="tc-stat {{ $stats['overdue'] ? 'tc-stat--alert' : '' }}">
                    <span class="tc-stat-value">{{ $stats['overdue'] }}</span>
                    <span class="tc-stat-label">Overdue</span>
                </a>
                <a href="{{ route('tasks.index', ['mine' => 1]) }}" class="tc-stat">
                    <span class="tc-stat-value">{{ $stats['mine'] }}</span>
                    <span class="tc-stat-label">Assigned to me</span>
                </a>
                <a href="{{ route('schedules.week') }}" class="tc-stat">
                    <span class="tc-stat-value">{{ $stats['sparrings_this_week'] }}</span>
                    <span class="tc-stat-label">Sparrings this week</span>
                </a>
            </div>

            {{-- Insurance / liability notice (dismissible, remembered per device) --}}
            <div class="tc-banner tc-banner--warning" id="insurance-note" role="note" hidden>
                <span class="material-icons" aria-hidden="true">verified_user</span>
                <div class="tc-banner-body">
                    <strong>Insurance reminder</strong>
                    <p>Every athlete must hold valid sports insurance before sparring. The club is not
                       liable for injuries sustained without active cover.</p>
                </div>
                <button type="button" class="tc-banner-close" data-dismiss-note aria-label="Dismiss reminder">
                    <span class="material-icons" aria-hidden="true">close</span>
                </button>
            </div>

            {{-- Tasks that need attention now --}}
            <section class="tc-home-section" aria-labelledby="home-focus">
                <div class="tc-home-section-head">
                    <h2 class="tc-task-group-header mb-0" id="home-focus">Due today &amp; overdue</h2>
                    <a href="{{ route('tasks.index') }}" class="btn btn-sm btn-link p-0">All tasks</a>
                </div>
                @forelse($focusTasks as $task)
                    @include('frontend.tasks._item', ['task' => $task])
                @empty
                <p class="text-muted small mb-0">Nothing due today. <a href="{{ route('tasks.create') }}">Add a task</a></p>
                @endforelse
            </section>

            {{-- Next sparring sessions --}}
            <section class="tc-home-section" aria-labelledby="home-sparrings">
                <div class="tc-home-section-head">
                    <h2 class="tc-task-group-header mb-0" id="home-sparrings">Upcoming sparrings</h2>
                    <a href="{{ route('schedules.week') }}" class="btn btn-sm btn-link p-0">Calendar</a>
                </div>
                @forelse($upcomingSparrings as $sparring)
                @php $sparringDate = \Carbon\Carbon::parse($sparring->date); @endphp
                <a href="{{ route('schedules.index', ['date' => $sparring->date_format]) }}" class="tc-upcoming"
                   style="--tc-upcoming-accent: {{ $sparring->color_hex }}">
                    <span class="tc-upcoming-date" aria-hidden="true">
                        <span class="tc-upcoming-dow">{{ $sparringDate->isToday() ? 'Today' : $sparringDate->format('D') }}</span>
                        <span class="tc-upcoming-day">{{ $sparringDate->format('j') }}</span>
                    </span>
                    <span class="tc-upcoming-body">
                        <strong class="text-truncate">
                            {{ $sparring->title ?: 'Sparring' }}
                            @if($sparring->status === 'confirmed')<span class="tc-status tc-status--confirmed">Confirmed</span>@endif
                        </strong>
                        <span class="tc-upcoming-meta">
                            <span class="visually-hidden">{{ $sparringDate->format('l, j F') }},</span>
                            {{ $sparring->start }}–{{ $sparring->end }}
                            @if($sparring->participants->isNotEmpty())
                            · {{ $sparring->participants->pluck('full_name')->implode(' vs ') }}
                            @endif
                        </span>
                        @if($sparring->location)
                        <span class="tc-upcoming-meta">{{ $sparring->location }}</span>
                        @endif
                    </span>
                    <span class="material-icons tc-upcoming-chevron" aria-hidden="true">chevron_right</span>
                </a>
                @empty
                <p class="text-muted small mb-0">No sparrings planned. <a href="{{ route('schedules.create') }}">Plan one</a></p>
                @endforelse
            </section>

            {{-- What changed recently in this team --}}
            <section class="tc-home-section" aria-labelledby="home-activity">
                <div class="tc-home-section-head">
                    <h2 class="tc-task-group-header mb-0" id="home-activity">Recent activity</h2>
                    <a href="{{ route('activity') }}" class="btn btn-sm btn-link p-0">All activity</a>
                </div>
                @if($recentActivity->isNotEmpty())
                <ul class="tc-activity-list">
                    @foreach($recentActivity as $activity)
                        @include('frontend.activity._item', ['activity' => $activity])
                    @endforeach
                </ul>
                @else
                <p class="text-muted small mb-0">No activity yet.</p>
                @endif
            </section>

            {{-- Quick switch between teams with their open work --}}
            @if($teamOverview->isNotEmpty())
            <section class="tc-home-section" aria-labelledby="home-teams">
                <div class="tc-home-section-head">
                    <h2 class="tc-task-group-header mb-0" id="home-teams">Your teams</h2>
                    <a href="{{ route('teams.create') }}" class="btn btn-sm btn-link p-0">New team</a>
                </div>
                <ul class="tc-team-overview">
                    @foreach($teamOverview as $overviewTeam)
                    @php $isCurrent = $overviewTeam->id === $team->id; @endphp
                    <li>
                        <form method="POST" action="{{ route('teams.switch', $overviewTeam) }}" class="mb-0" data-no-spinner>
                            @csrf
                            <button type="submit" class="tc-team-row {{ $isCurrent ? 'is-current' : '' }}" @if($isCurrent) aria-current="true" @endif>
                                <span class="tc-team-row-name">{{ $overviewTeam->name }}</span>
                                <span class="tc-task-badge">{{ $overviewTeam->open_count }} open</span>
                                @if($overviewTeam->overdue_count)
                                <span class="tc-task-badge tc-task-badge--overdue">{{ $overviewTeam->overdue_count }} overdue</span>
                                @endif
                            </button>
                        </form>
                    </li>
                    @endforeach
                </ul>
            </section>
            @endif
            @endif

            <h2 class="tc-task-group-header mt-4" id="home-posts">Announcements</h2>
            @forelse($notifications as $notification)
            <div class="card home mb-3">
                <div aria-live="assertive" aria-atomic="true">
                    <div class="toast-header d-flex align-items-center">
                        <img src="{{
                            $notification->user->userDetail && $notification->user->userDetail->image ?
                            asset($notification->user->userDetail->image->file_name) :
                            asset('assets/default-avatar.svg')
                        }}" class="rounded-circle me-3" height="44px" width="44px" alt="Author avatar">
                        <strong class="me-auto"></strong>
                        <small class="text-muted">{{ $notification->time }}</small>
                        <div class="btn-group">
                            <button type="button" class="close" id="dropdown{{$notification->id}}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Post actions">
                                <span aria-hidden="true" class="material-icons">more_vert</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdown{{$notification->id}}">
                                <a class="dropdown-item" href="{{ route('notification.edit', ['notification' => $notification->id]) }}">Edit</a>
                                <form action="{{ route('notification.destroy', ['notification' => $notification->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="submit" class="dropdown-item" value="Delete" />
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="toast-body" style="white-space: pre-line">
                        <h4 class="card-title mb-2">{{ $notification->title }}</h4>
                        {{ $notification->description }}
                    </div>
                </div>

                @if($notification->image)
                <div class="view overlay">
                    <img class="card-img-top rounded-0" src="{{ asset($notification->image->file_name) }}" alt="{{ $notification->title }}">
                </div>
                @endif
            </div>
            @empty
            <div class="text-center py-5 text-muted tc-empty">
                <span class="material-icons" style="font-size:48px">inbox</span>
                <p class="mt-2">No posts yet. Tap <strong>+</strong> to add one.</p>
            </div>
            @endforelse

            @if($notifications->hasPages())
            <div class="d-flex justify-content-center">
                {!! $notifications->links('pagination::bootstrap-5') !!}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Show the insurance note unless the user dismissed it on this device
    (function () {
        var note = document.getElementById('insurance-note')
        if (!note) return
        if (localStorage.getItem('tc-insurance-dismissed') !== '1') note.hidden = false
        note.querySelector('[data-dismiss-note]').addEventListener('click', function () {
            note.hidden = true
            try { localStorage.setItem('tc-insurance-dismissed', '1') } catch (e) {}
        })
    })()
</script>
@endsection

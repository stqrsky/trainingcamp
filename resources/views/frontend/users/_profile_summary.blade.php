{{-- Experience, skills, availability and activity of a person; shared by the member detail and own profile pages --}}
<div class="tc-stat-grid mt-3" aria-label="Activity">
    <div class="tc-stat">
        <span class="tc-stat-value">{{ $summary['open_tasks'] }}</span>
        <span class="tc-stat-label">Open tasks</span>
    </div>
    <div class="tc-stat">
        <span class="tc-stat-value">{{ $summary['completed_tasks'] }}</span>
        <span class="tc-stat-label">Tasks done</span>
    </div>
    <div class="tc-stat">
        <span class="tc-stat-value">{{ $summary['upcoming_count'] }}</span>
        <span class="tc-stat-label">Upcoming sparrings</span>
    </div>
    <div class="tc-stat">
        <span class="tc-stat-value">{{ $summary['completed_sparrings'] }}</span>
        <span class="tc-stat-label">Sparrings done</span>
    </div>
</div>

<section class="tc-home-section" aria-labelledby="profile-skills-{{ $user->id }}">
    <h2 class="tc-task-group-header" id="profile-skills-{{ $user->id }}">Level &amp; skills</h2>
    <p class="mb-2">
        <span class="text-muted small">Experience:</span>
        <strong>{{ $user->userDetail?->experience_label ?? 'Not set' }}</strong>
    </p>
    @if($user->skills->isNotEmpty())
    <div class="d-flex flex-wrap gap-1">
        @foreach($user->skills as $skill)
        <span class="tc-badge tc-badge--skill">{{ $skill->name }} · {{ \App\Models\Skill::LEVELS[$skill->pivot->level] ?? $skill->pivot->level }}</span>
        @endforeach
    </div>
    @else
    <p class="text-muted small mb-0">No skills recorded for this team.</p>
    @endif
</section>

<section class="tc-home-section" aria-labelledby="profile-availability-{{ $user->id }}">
    <h2 class="tc-task-group-header" id="profile-availability-{{ $user->id }}">Availability</h2>
    @if($user->availabilities->isNotEmpty())
    <ul class="tc-availability-list">
        @foreach($user->availabilities as $slot)
        <li>
            <strong>{{ \App\Models\Availability::WEEKDAYS[$slot->weekday] }}</strong>
            <span>{{ $slot->start }}–{{ $slot->end }}</span>
        </li>
        @endforeach
    </ul>
    @else
    <p class="text-muted small mb-0">No availability recorded.</p>
    @endif
</section>

<section class="tc-home-section" aria-labelledby="profile-upcoming-{{ $user->id }}">
    <h2 class="tc-task-group-header" id="profile-upcoming-{{ $user->id }}">Upcoming sparrings</h2>
    @forelse($summary['upcoming_sparrings'] as $sparring)
    @php $sparringDate = \Carbon\Carbon::parse($sparring->date); @endphp
    <a href="{{ route('schedules.index', ['date' => $sparring->date_format]) }}" class="tc-upcoming"
       style="--tc-upcoming-accent: {{ $sparring->color_hex }}">
        <span class="tc-upcoming-date" aria-hidden="true">
            <span class="tc-upcoming-dow">{{ $sparringDate->isToday() ? 'Today' : $sparringDate->format('D') }}</span>
            <span class="tc-upcoming-day">{{ $sparringDate->format('j') }}</span>
        </span>
        <span class="tc-upcoming-body">
            <strong class="text-truncate">{{ $sparring->title ?: 'Sparring' }}</strong>
            <span class="tc-upcoming-meta">
                <span class="visually-hidden">{{ $sparringDate->format('l, j F') }},</span>
                {{ $sparring->start }}–{{ $sparring->end }} · {{ $sparring->participants->pluck('full_name')->implode(' vs ') }}
            </span>
        </span>
        <span class="material-icons tc-upcoming-chevron" aria-hidden="true">chevron_right</span>
    </a>
    @empty
    <p class="text-muted small mb-0">No upcoming sparrings.</p>
    @endforelse
</section>

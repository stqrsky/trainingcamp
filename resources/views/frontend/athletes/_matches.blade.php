{{-- Rule-based sparring partner suggestions; every point is listed so the ranking stays explainable --}}
<section class="tc-home-section" aria-labelledby="matches-{{ $user->id }}">
    <h2 class="tc-task-group-header" id="matches-{{ $user->id }}">Suggested sparring partners</h2>
    @forelse($matches as $match)
    @php $partner = $match['partner']; @endphp
    <article class="tc-match">
        <div class="tc-match-head">
            <span class="tc-avatar tc-avatar--sm" aria-hidden="true">{{ $partner->initials }}</span>
            <div class="flex-fill text-truncate">
                <a href="{{ route('user.athletes.detail', ['id' => $partner->id]) }}" class="fw-700 text-truncate d-block">{{ $partner->full_name }}</a>
                <small class="text-muted">{{ $partner->userDetail?->experience_label ?? 'Level not set' }}</small>
            </div>
            <span class="tc-match-points" title="{{ $match['points'] }} of {{ \App\Services\SparringMatcher::MAX_POINTS }} points">
                {{ $match['points'] }}<small>/{{ \App\Services\SparringMatcher::MAX_POINTS }}</small>
            </span>
        </div>
        <ul class="tc-match-reasons">
            @foreach($match['reasons'] as $reason)
            <li class="{{ $reason['points'] > 0 ? 'is-plus' : ($reason['points'] < 0 ? 'is-minus' : '') }}">
                <span>{{ $reason['label'] }}</span>
                <span class="tc-match-reason-points">{{ $reason['points'] > 0 ? '+' : '' }}{{ $reason['points'] }}</span>
            </li>
            @endforeach
        </ul>
        <a class="btn btn-sm btn-primary" href="{{ route('schedules.create', array_filter([
            'athlete' => $user->id,
            'partner' => $partner->id,
            'date'    => $match['slot'] ? $match['slot']['date']->format('d/m/Y') : null,
            'start'   => $match['slot']['start'] ?? null,
            'end'     => $match['slot']['end'] ?? null,
        ])) }}">
            Plan sparring{{ $match['slot'] ? ' · ' . $match['slot']['date']->format('D j M') . ' ' . $match['slot']['start'] : '' }}
        </a>
    </article>
    @empty
    <p class="text-muted small mb-0">No other active athletes in this team yet.</p>
    @endforelse
</section>

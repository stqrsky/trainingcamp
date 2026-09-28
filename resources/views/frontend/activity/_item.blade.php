<li class="tc-activity">
    <span class="tc-activity-icon material-icons" aria-hidden="true">{{ $activity->icon }}</span>
    <div class="tc-activity-body">
        <p class="mb-0">
            <strong>{{ $activity->actor_name }}</strong>
            @if($activity->url)
            <a href="{{ $activity->url }}">{{ $activity->description }}</a>
            @else
            {{ $activity->description }}
            @endif
        </p>
        <time datetime="{{ $activity->created_at->toIso8601String() }}" title="{{ $activity->created_at->format('j M Y, H:i') }}">
            {{ $activity->created_at->diffForHumans() }}
        </time>
    </div>
</li>

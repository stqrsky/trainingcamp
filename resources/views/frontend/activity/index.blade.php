@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title">Activity</h4>
        </div>

        @forelse($days as $day => $entries)
        @php $dayDate = \Carbon\Carbon::parse($day); @endphp
        <section class="mb-3" aria-labelledby="activity-{{ $day }}">
            <h2 class="tc-task-group-header" id="activity-{{ $day }}">
                {{ $dayDate->isToday() ? 'Today' : ($dayDate->isYesterday() ? 'Yesterday' : $dayDate->format('l, j M Y')) }}
            </h2>
            <ul class="tc-activity-list">
                @foreach($entries as $activity)
                    @include('frontend.activity._item', ['activity' => $activity])
                @endforeach
            </ul>
        </section>
        @empty
        <div class="text-center py-5 text-muted tc-empty">
            <span class="material-icons" style="font-size:48px" aria-hidden="true">history</span>
            <p class="mt-2">No activity yet. Changes to tasks, sparrings and members will show up here.</p>
        </div>
        @endforelse

        @if($activities->hasPages())
        <div class="d-flex justify-content-center">
            {!! $activities->links('pagination::bootstrap-5') !!}
        </div>
        @endif
    </div>
</div>
@endsection

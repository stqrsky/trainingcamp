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

            {{-- Greeting --}}
            <div class="tc-banner tc-banner--greeting">
                <span class="material-icons" aria-hidden="true">sports_mma</span>
                <div class="tc-banner-body">
                    <strong>Welcome back to your bootcamp</strong>
                    <p>Here's the latest from your team — announcements, updates and notes.</p>
                </div>
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
                        {!! $notification->description !!}
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

@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" href="{{ asset('css/athlete.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            @if($team)
            @error('error')
            <div class="alert alert-danger" role="alert">{{ $message }}</div>
            @enderror

            <div class="d-flex justify-content-between align-items-center mb-2 head-title">
                <h4 class="title-team">{{ $team->name }}</h4>
                <a href="{{ route('user.athletes.create') }}" type="button" class="close btn-add" aria-label="Add member">
                    <span aria-hidden="true" class="material-icons add">add</span>
                </a>
            </div>

            {{-- Search, filter and sort (GET, so filtered views can be bookmarked) --}}
            @php $filtered = $filters['search'] !== '' || $filters['role'] !== 'all' || $filters['skill'] || $filters['status'] !== 'active'; @endphp
            <form class="tc-member-filters mb-3" method="GET" action="{{ route('user.athletes') }}" data-no-spinner data-member-filters>
                <div class="tc-search mb-2">
                    <input class="form-control" name="search" type="search" placeholder="Search by name or nickname…"
                           value="{{ $filters['search'] }}" aria-label="Search members">
                    <button class="btn search" type="submit" aria-label="Search">
                        <span class="material-icons align-middle" style="font-size:18px" aria-hidden="true">search</span>
                    </button>
                </div>
                <div class="tc-filter-row">
                    <label class="visually-hidden" for="filter-role">Role</label>
                    <select class="form-select form-select-sm" id="filter-role" name="role">
                        <option value="all" @selected($filters['role'] === 'all')>All roles</option>
                        <option value="coach" @selected($filters['role'] === 'coach')>Coaches</option>
                        <option value="athlete" @selected($filters['role'] === 'athlete')>Athletes</option>
                    </select>
                    <label class="visually-hidden" for="filter-skill">Skill</label>
                    <select class="form-select form-select-sm" id="filter-skill" name="skill">
                        <option value="">All skills</option>
                        @foreach($skills as $skill)
                        <option value="{{ $skill->id }}" @selected($filters['skill'] === $skill->id)>{{ $skill->name }}</option>
                        @endforeach
                    </select>
                    <label class="visually-hidden" for="filter-status">Status</label>
                    <select class="form-select form-select-sm" id="filter-status" name="status">
                        <option value="active" @selected($filters['status'] === 'active')>Active</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Inactive</option>
                        <option value="all" @selected($filters['status'] === 'all')>All members</option>
                    </select>
                    <label class="visually-hidden" for="filter-sort">Sort</label>
                    <select class="form-select form-select-sm" id="filter-sort" name="sort">
                        <option value="name" @selected($filters['sort'] === 'name')>Name A–Z</option>
                        <option value="name_desc" @selected($filters['sort'] === 'name_desc')>Name Z–A</option>
                        <option value="newest" @selected($filters['sort'] === 'newest')>Recently added</option>
                    </select>
                </div>
                @if($filtered)
                <a href="{{ route('user.athletes') }}" class="tc-filter-reset">Reset filters</a>
                @endif
            </form>

            @foreach(['coach' => $team->coaches, 'athlete' => $team->athletes] as $role => $members)
            @continue($filters['role'] !== 'all' && $filters['role'] !== $role)
            <p class="list-section-label">{{ $role === 'coach' ? 'Coaches' : 'Athletes' }} ({{ $members->count() }})</p>
            <div class="list-team-members">
                <ul>
                    @forelse($members as $member)
                        @include('frontend.athletes._member', ['member' => $member, 'role' => $role])
                    @empty
                    <li class="text-center py-4 text-muted tc-empty" style="border-style:dashed">
                        @if($filtered)
                        <p class="mb-0">No {{ $role === 'coach' ? 'coaches' : 'athletes' }} match these filters.</p>
                        @else
                        <span class="material-icons" style="font-size:40px" aria-hidden="true">people_outline</span>
                        <p class="mt-1 mb-0">No {{ $role === 'coach' ? 'coaches' : 'athletes' }} yet. Tap <strong>+</strong> to add one.</p>
                        @endif
                    </li>
                    @endforelse
                </ul>
            </div>
            @endforeach
            @else
            <div class="d-flex justify-content-center align-items-center py-5 head-title">
                <a href="{{ route('user.setting') }}" type="button" class="btn btn-primary">Complete Your Profile</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Apply filter selects immediately; the search field still submits with Enter
    document.querySelectorAll('[data-member-filters] select').forEach(function (select) {
        select.addEventListener('change', function () { select.form.submit() })
    })
</script>
@endsection

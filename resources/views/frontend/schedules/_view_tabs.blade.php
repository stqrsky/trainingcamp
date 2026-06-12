<div class="tc-view-tabs mb-3">
    <a href="{{ route('schedules.index') }}"
       class="tc-view-tab {{ request()->routeIs('schedules.index') ? 'active' : '' }}">List</a>
    <a href="{{ route('schedules.day') }}"
       class="tc-view-tab {{ request()->routeIs('schedules.day') ? 'active' : '' }}">Day</a>
    <a href="{{ route('schedules.week') }}"
       class="tc-view-tab {{ request()->routeIs('schedules.week') ? 'active' : '' }}">Week</a>
    <a href="{{ route('schedules.month') }}"
       class="tc-view-tab {{ request()->routeIs('schedules.month') ? 'active' : '' }}">Month</a>
    <a href="{{ route('schedules.planner') }}"
       class="tc-view-tab {{ request()->routeIs('schedules.planner') ? 'active' : '' }}">Planner</a>
</div>

<nav class="tc-view-tabs mb-3" aria-label="Task views">
    <a href="{{ route('tasks.index') }}"
       class="tc-view-tab {{ !request('mine') && request('view') !== 'board' ? 'active' : '' }}">List</a>
    <a href="{{ route('tasks.index', ['view' => 'board']) }}"
       class="tc-view-tab {{ !request('mine') && request('view') === 'board' ? 'active' : '' }}">Board</a>
    <a href="{{ route('tasks.index', array_filter(['view' => request('view') === 'board' ? 'board' : null, 'mine' => 1])) }}"
       class="tc-view-tab {{ request('mine') ? 'active' : '' }}">My Tasks</a>
</nav>

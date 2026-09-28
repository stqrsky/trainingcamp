<article class="tc-board-card" draggable="true" data-task-id="{{ $task->id }}">
    <a href="{{ route('tasks.edit', $task) }}" class="tc-board-card-title" draggable="false">{{ $task->title }}</a>
    <div class="tc-task-meta">
        <span class="tc-priority tc-priority--{{ $task->priority }}">{{ $task->priority_label }}</span>
        @if($task->due_date)
        <span class="tc-task-badge {{ $task->isOverdue() ? 'tc-task-badge--overdue' : '' }}">{{ $task->dueLabel }}</span>
        @endif
        @if($task->label)
        <span class="tc-task-badge">{{ $task->label }}</span>
        @endif
    </div>
    <div class="tc-board-card-footer">
        @if($task->assignee)
        <span class="tc-avatar tc-avatar--xs" title="Assigned to {{ $task->assignee->full_name }}">{{ $task->assignee->initials }}</span>
        @else
        <span class="text-muted small">Unassigned</span>
        @endif
        <form method="POST" action="{{ route('tasks.move', $task) }}" class="mb-0" data-no-spinner>
            @csrf
            @method('PATCH')
            <label class="visually-hidden" for="task-status-{{ $task->id }}">Status of {{ $task->title }}</label>
            <select id="task-status-{{ $task->id }}" name="status" class="form-select form-select-sm" data-status-select>
                @foreach(\App\Models\Task::STATUSES as $key => $label)
                <option value="{{ $key }}" @selected($task->status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <noscript><button type="submit" class="btn btn-sm btn-outline-secondary mt-1">Move</button></noscript>
        </form>
    </div>
</article>

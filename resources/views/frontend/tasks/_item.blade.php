<div class="tc-task-item">
    <form action="{{ route('tasks.toggle', $task->id) }}" method="POST" class="mb-0">
        @csrf
        <button type="submit"
                class="tc-task-checkbox {{ $task->isDone() ? 'done' : '' }}"
                title="{{ $task->isDone() ? 'Mark pending' : 'Mark done' }}"
                aria-label="{{ $task->isDone() ? 'Mark pending' : 'Mark done' }}: {{ $task->title }}">
            @if($task->isDone())
            <span class="material-icons" style="font-size:14px;color:#fff" aria-hidden="true">check</span>
            @endif
        </button>
    </form>
    <div class="flex-fill">
        <a href="{{ route('tasks.edit', $task->id) }}"
           class="tc-task-title {{ $task->isDone() ? 'done' : '' }} {{ $task->isHighPriority() ? 'tc-task-high' : '' }} text-decoration-none d-block">
            @if($task->isHighPriority() && !$task->isDone())
            <span class="text-danger me-1" title="{{ $task->priority_label }} priority">⚑</span>
            @endif
            {{ $task->title }}
        </a>
        <div class="tc-task-meta">
            @if(!in_array($task->status, ['todo', 'done'], true))
            <span class="tc-task-badge tc-task-badge--status">{{ $task->status_label }}</span>
            @endif
            @if($task->priority === 'urgent' && !$task->isDone())
            <span class="tc-priority tc-priority--urgent">Urgent</span>
            @endif
            @if($task->label)
            <span class="tc-task-badge">{{ $task->label }}</span>
            @endif
            @if($task->due_date)
            <span class="tc-task-badge {{ $task->isOverdue() ? 'tc-task-badge--overdue' : '' }}">
                {{ $task->dueLabel }}
            </span>
            @endif
            @if($task->assignee)
            <span class="tc-task-badge" title="Assigned to {{ $task->assignee->full_name }}">
                {{ $task->assignee->full_name }}
            </span>
            @endif
        </div>
    </div>
    <a href="{{ route('tasks.edit', $task->id) }}" class="tc-task-edit-btn" title="Edit" aria-label="Edit {{ $task->title }}">
        <span class="material-icons" style="font-size:18px" aria-hidden="true">chevron_right</span>
    </a>
</div>

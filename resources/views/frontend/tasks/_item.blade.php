<div class="tc-task-item">
    <form action="{{ route('tasks.toggle', $task->id) }}" method="POST" class="mb-0">
        @csrf
        <button type="submit"
                class="tc-task-checkbox {{ $task->status ? 'done' : '' }}"
                title="{{ $task->status ? 'Mark pending' : 'Mark done' }}">
            @if($task->status)
            <span class="material-icons" style="font-size:14px;color:#fff">check</span>
            @endif
        </button>
    </form>
    <div class="flex-fill">
        <a href="{{ route('tasks.edit', $task->id) }}"
           class="tc-task-title {{ $task->status ? 'done' : '' }} {{ $task->priority ? 'tc-task-high' : '' }} text-decoration-none d-block">
            @if($task->priority && !$task->status)
            <span class="text-danger me-1" title="High priority">⚑</span>
            @endif
            {{ $task->title }}
        </a>
        <div class="tc-task-meta">
            @if($task->label)
            <span class="tc-task-badge">{{ $task->label }}</span>
            @endif
            @if($task->due_date)
            <span class="tc-task-badge {{ $task->isOverdue() && !$task->status ? 'tc-task-badge--overdue' : '' }}">
                {{ $task->dueLabel }}
            </span>
            @endif
        </div>
    </div>
    <a href="{{ route('tasks.edit', $task->id) }}" class="tc-task-edit-btn" title="Edit">
        <span class="material-icons" style="font-size:18px">chevron_right</span>
    </a>
</div>

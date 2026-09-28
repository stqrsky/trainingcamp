{{-- Title --}}
<div class="form-group mb-3">
    <label for="task-title" class="col-form-label fw-600">Title</label>
    <input type="text" class="form-control @error('title') is-invalid @enderror"
           name="title" id="task-title" placeholder="Task title…"
           value="{{ isset($task) ? $task->title : old('title') }}" required>
    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

{{-- Due Date & Time --}}
<div class="d-flex gap-3 mb-3">
    <div class="form-group flex-fill">
        <label for="due_date">Due Date</label>
        <input type="text" class="form-control" name="due_date" id="due_date"
               placeholder="DD/MM/YYYY"
               value="{{ isset($task) ? $task->due_date_format : old('due_date') }}">
    </div>
    <div class="form-group flex-fill">
        <label for="due_time">Time <span class="text-muted small">(opt.)</span></label>
        <input type="time" class="form-control" name="due_time" id="due_time"
               value="{{ isset($task) ? $task->due_time : old('due_time') }}">
    </div>
</div>

{{-- Label --}}
<div class="form-group mb-3">
    <label for="label">Label <span class="text-muted small">(optional)</span></label>
    <input type="text" class="form-control" name="label" id="label"
           placeholder="e.g. Work, Home, Training…"
           value="{{ isset($task) ? $task->label : old('label') }}"
           list="label-suggestions">
    <datalist id="label-suggestions">
        <option value="Work"><option value="Home"><option value="Training">
        <option value="Health"><option value="Inbox"><option value="Groceries">
    </datalist>
</div>

{{-- Status & Assignee --}}
<div class="d-flex gap-3 mb-3">
    <div class="form-group flex-fill">
        <label for="status">Status</label>
        @php $currentStatus = old('status', isset($task) ? $task->status : 'todo'); @endphp
        <select class="form-select @error('status') is-invalid @enderror" name="status" id="status">
            @foreach(\App\Models\Task::STATUSES as $key => $label)
            <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group flex-fill">
        <label for="assignee_id">Assignee <span class="text-muted small">(optional)</span></label>
        @php $currentAssignee = (string) old('assignee_id', isset($task) ? $task->assignee_id : ''); @endphp
        <select class="form-select @error('assignee_id') is-invalid @enderror" name="assignee_id" id="assignee_id">
            <option value="">Unassigned</option>
            @foreach($members as $member)
            <option value="{{ $member->id }}" @selected($currentAssignee === (string) $member->id)>
                {{ $member->full_name }}{{ $member->id === auth()->id() ? ' (me)' : '' }}
            </option>
            @endforeach
        </select>
        @error('assignee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Priority --}}
<fieldset class="form-group mb-3">
    <legend class="col-form-label fs-6 pt-0">Priority</legend>
    @php $currentPriority = old('priority', isset($task) ? $task->priority : 'medium'); @endphp
    <div class="d-flex flex-wrap gap-3">
        @foreach(\App\Models\Task::PRIORITIES as $key => $label)
        <label class="d-flex align-items-center gap-1">
            <input type="radio" name="priority" value="{{ $key }}" @checked($currentPriority === $key)>
            <span class="tc-priority tc-priority--{{ $key }}">{{ $label }}</span>
        </label>
        @endforeach
    </div>
    @error('priority')<div class="text-danger small">{{ $message }}</div>@enderror
</fieldset>

{{-- Notes --}}
<div class="form-group mb-3">
    <label for="notes">Notes <span class="text-muted small">(optional)</span></label>
    <textarea class="form-control" name="notes" id="notes" rows="3"
              placeholder="Additional details…">{{ isset($task) ? $task->notes : old('notes') }}</textarea>
</div>

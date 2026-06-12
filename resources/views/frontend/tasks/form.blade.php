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

{{-- Priority --}}
<div class="form-group mb-3">
    <label class="d-block col-form-label">Priority</label>
    <div class="d-flex gap-3">
        <label class="d-flex align-items-center gap-1">
            <input type="radio" name="priority" value="0"
                   @checked((isset($task) ? $task->priority : old('priority', 0)) == 0)>
            <span>Normal</span>
        </label>
        <label class="d-flex align-items-center gap-1">
            <input type="radio" name="priority" value="1"
                   @checked((isset($task) ? $task->priority : old('priority')) == 1)>
            <span class="text-danger">⚑ High</span>
        </label>
    </div>
</div>

{{-- Notes --}}
<div class="form-group mb-3">
    <label for="notes">Notes <span class="text-muted small">(optional)</span></label>
    <textarea class="form-control" name="notes" id="notes" rows="3"
              placeholder="Additional details…">{{ isset($task) ? $task->notes : old('notes') }}</textarea>
</div>

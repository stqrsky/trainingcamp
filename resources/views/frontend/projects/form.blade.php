<div class="form-group">
    <label for="project-name" class="col-form-label">Name</label>
    <input type="text" class="form-control @error('name') is-invalid @enderror" id="project-name" name="name"
           value="{{ old('name', $project->name ?? '') }}" required maxlength="255">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="d-flex gap-3">
    <div class="form-group flex-fill">
        <label for="project-status">Status</label>
        @php $currentStatus = old('status', $project->status ?? 'active'); @endphp
        <select class="form-select @error('status') is-invalid @enderror" id="project-status" name="status">
            @foreach(\App\Models\Project::STATUSES as $key => $label)
            <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group flex-fill">
        <label for="project-deadline">Deadline <span class="text-muted small">(optional)</span></label>
        <input type="text" class="form-control @error('deadline') is-invalid @enderror" id="project-deadline" name="deadline"
               placeholder="DD/MM/YYYY" value="{{ old('deadline', $project->deadline_format ?? '') }}">
        @error('deadline')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group">
    <label for="project-owner">Owner <span class="text-muted small">(optional)</span></label>
    @php $currentOwner = (string) old('owner_id', $project->owner_id ?? ''); @endphp
    <select class="form-select @error('owner_id') is-invalid @enderror" id="project-owner" name="owner_id">
        <option value="">No owner</option>
        @foreach($people as $person)
        <option value="{{ $person->id }}" @selected($currentOwner === (string) $person->id)>
            {{ $person->full_name }}{{ $person->id === auth()->id() ? ' (me)' : '' }}
        </option>
        @endforeach
    </select>
    @error('owner_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="form-group">
    <label for="project-description">Description <span class="text-muted small">(optional)</span></label>
    <textarea class="form-control @error('description') is-invalid @enderror" id="project-description" name="description"
              rows="4">{{ old('description', $project->description ?? '') }}</textarea>
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

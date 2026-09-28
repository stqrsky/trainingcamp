<div class="form-group row">
    <label for="name" class="col-sm-12 col-form-label">Team Name</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" placeholder="Team Name" name="name" value="{{ old('name', isset($team) ? $team->name : '') }}" required>
        @error('name')<div class="invalid-feedback float-start">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="description" class="col-sm-12 col-form-label">Description <span class="text-muted small">(optional)</span></label>
    <div class="col-sm-12">
        <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="4">{{ old('description', isset($team) ? $team->description : '') }}</textarea>
        @error('description')<div class="invalid-feedback float-start">{{ $message }}</div>@enderror
    </div>
</div>

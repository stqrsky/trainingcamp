<div class="form-group row">
    <label for="title" class="col-sm-12 col-form-label">Image</label>
    <div class="col-sm-12">
        <input type="file" class="@error('file') is-invalid @enderror" id="title" name="file">
        @error('file')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
    @if(isset($notification) && $notification->image)
    <div class="col-sm-12 pt-3">
        <img src="{{ asset($notification->image->file_name) }}" class="img-fluid" alt="Responsive image">
    </div>
    @endif
</div>
<div class="form-group row">
    <label for="title" class="col-sm-12 col-form-label">Title</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" placeholder="Title" name="title" value="{{ isset($notification) ? $notification->title : old('title') }}">
        @error('title')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="description" class="col-sm-12 col-form-label">Description</label>
    <div class="col-sm-12">
        <textarea class="form-control @error('description') is-invalid @enderror" name="description" id="description" rows="8" cols="80">{{ isset($notification) ? $notification->description : old('description') }}</textarea>
        @error('description')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <div class="col-sm-12 text-center">
        <input class="inputfile" type="file" id="picture" name="picture">
        <label for="picture" class="border picture">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU" class="mx-auto d-block rounded-circle" height="128" width="128" alt="...">
            <label class="add-profile btn btn-sm btn-info" for="picture">Select Picture</label>
        </label>
    </div>
</div>
<div class="form-group row">
    <label for="first_name" class="col-sm-12 col-form-label">First Name</label>
    <div class="col-sm-12">
        <input type="text" class="@error('first_name') is-invalid @enderror form-control" id="first_name" placeholder="First Name" name="first_name" value="{{ isset($user) ? $user->first_name : old('first_name') }}">
        @error('first_name')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="last_name" class="col-sm-12 col-form-label">Last Name</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" placeholder="Last Name" name="last_name" value="{{ isset($user) ? $user->last_name : old('last_name') }}">
        @error('last_name')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="nick_name" class="col-sm-12 col-form-label">Nick Name</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('nick_name') is-invalid @enderror" id="nick_name" placeholder="Nick Name" name="nick_name" value="{{ isset($detail) ? $detail->nick_name : old('nick_name') }}">
        @error('nick_name')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="date_of_birth" class="col-sm-12 col-form-label">Date of Birth</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" placeholder="Date Of Birth" name="date_of_birth" value="{{ isset($detail) ? $detail->format_date_of_birth : old('date_of_birth') }}">
        @error('date_of_birth')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="weight" class="col-sm-12 col-form-label">Weight</label>
    <div class="col-sm-12">
        <input type="number" class="form-control @error('weight') is-invalid @enderror" id="weight" placeholder="Weight" name="weight" value="{{ isset($detail) ? $detail->weight : old('weight') }}">
        @error('weight')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="height" class="col-sm-12 col-form-label">Height</label>
    <div class="col-sm-12">
        <input type="number" class="form-control @error('height') is-invalid @enderror" id="height" placeholder="Height" name="height" value="{{ isset($detail) ? $detail->height : old('height') }}">
        @error('height')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="skills" class="col-sm-12 col-form-label">Skills</label>
    <div class="col-sm-12">
        <select name="skills[]" class="form-control @error('skills') is-invalid @enderror" id="skills" multiple="multiple">
            @if(isset($skills))
            @foreach($skills as $skill)
            <option value="{{ $skill->id }}" @if(isset($skill->pivot))
                selected="selected"
                @endif
                >{{ $skill->name }}</option>
            @endforeach
            @endif
        </select>
        @error('skills')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="about" class="col-sm-12 col-form-label">About</label>
    <div class="col-sm-12">
        <textarea class="form-control @error('about') is-invalid @enderror" id="about" name="about" rows="8" cols="80">{!! isset($detail) ? $detail->about : old('about') !!}</textarea>
        @error('about')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row">
    <label for="team" class="col-sm-12 col-form-label">Team</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('team') is-invalid @enderror" id="team" placeholder="Team Name" name="team" value="{{ isset($team) ? $team->name : old('team') }}">
        @error('team')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
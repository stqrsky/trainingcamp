<div class="form-group row">
    <div class="col-sm-12 text-center">
        <input class="inputfile @error('file') is-invalid @enderror" type="file" id="picture" name="file" onChange="selectFile(event)">
        <label for="picture" class="border picture">
            <img src="{{
                isset($user) && $user->userDetail && $user->userDetail->image ? 
                asset($user->userDetail->image->file_name) :
                "https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU"
            }}" class="mx-auto d-block rounded-circle" height="128" alt="...">
            <label class="add-profile btn btn-sm btn-info" for="picture">Select Picture</label>
        </label>
        <p id="filename"></p>

        @error('file')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
@if(!isset($edit))
<div>
    <div class="form-check form-check-inline">
        <input class="form-check-input @error('user_type') is-invalid @enderror" name="user_type" type="radio" id="athlete" value="athlete">
        <label class="form-check-label" for="athlete">Athlete</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input @error('user_type') is-invalid @enderror" name="user_type" type="radio" id="coach" value="coach">
        <label class="form-check-label" for="coach">Coach</label>
    </div>
    @error('user_type')<p class="text-danger">{{ $message }}</p>@enderror
</div>
@endif
<div class="form-group row">
    <label for="email" class="col-sm-12 col-form-label">Email</label>
    <div class="col-sm-12">
        <input type="email" class="@error('email') is-invalid @enderror form-control" id="email" placeholder="Email" name="email" value="{{ isset($user) ? $user->email : old('email') }}">
        @error('email')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
@if(!isset($edit))
<div class="form-group row">
    <label for="password" class="col-sm-12 col-form-label">Password</label>
    <div class="col-sm-12">
        <input type="password" class="@error('password') is-invalid @enderror form-control" id="password" placeholder="Password" name="password" value="{{ old('password') }}">
        @error('password')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
@endif
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
        <select name="skills[]" value="[1,2]" class="form-control @error('skills') is-invalid @enderror" id="skills" multiple="multiple">
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
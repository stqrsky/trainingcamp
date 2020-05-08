<div class="form-group row">
    <div class="col-sm-12 text-center">
        <input class="inputfile" type="file" id="picture" name="picture">
        <label for="picture" class="border picture">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU" class="mx-auto d-block rounded-circle" height="128" alt="...">
            <label class="add-profile btn btn-sm btn-dark" for="picture">Select Picture</label>
        </label>
    </div>
</div>
<div class="form-group row">
    <label for="name" class="col-sm-12 col-form-label">Name</label>
    <div class="col-sm-12">
        <input type="text" class="form-control" id="name" placeholder="Name" name="name" value="{{ isset($user) ? $user->first_name : old('name') }}">
    </div>
</div>
<div class="form-group row">
    <label for="age" class="col-sm-12 col-form-label">Age</label>
    <div class="col-sm-12">
        <input type="number" class="form-control" id="age" placeholder="Age" name="age" value="{{ isset($user) ? $user->age : old('age') }}">
    </div>
</div>
<div class="form-group row">
    <label for="weight" class="col-sm-12 col-form-label">Weight</label>
    <div class="col-sm-12">
        <input type="number" class="form-control" id="weight" placeholder="Weight" name="weight" value="{{ isset($user) ? $user->weight : old('weight') }}">
    </div>
</div>
<div class="form-group row">
    <label for="height" class="col-sm-12 col-form-label">Height</label>
    <div class="col-sm-12">
        <input type="number" class="form-control" id="height" placeholder="Height" name="height" value="{{ isset($user) ? $user->height : old('height') }}">
    </div>
</div>
<div class="form-group row">
    <label for="skills" class="col-sm-12 col-form-label">Skills</label>
    <div class="col-sm-12">
        <input type="text" class="form-control" id="skills" placeholder="Skills" name="skills">
    </div>
</div>
<div class="form-group row">
    <label for="note" class="col-sm-12 col-form-label">Note</label>
    <div class="col-sm-12">
        <textarea class="form-control" name="note" id="note" rows="8" cols="80">{!! isset($user) ? $user->about : old('about') !!}</textarea>
    </div>
</div>
<div class="form-group row">
    <label for="team" class="col-sm-12 col-form-label">Team</label>
    <div class="col-sm-12">
        <input type="text" class="form-control" id="team" placeholder="Team Name" name="team">
    </div>
</div>
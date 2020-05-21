<div class="form-group row">
    <label for="date" class="col-sm-12 col-form-label">Date</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('date') is-invalid @enderror" name="date" id="date" value="{{ isset($schedule) ? $schedule->date_format : old('date') }}" />
        @error('date')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-row d-flex justify-content-between">
    <div class="form-group col-xs-6">
        <label for="start">Start</label>
        <input type="time" class="form-control @error('start') is-invalid @enderror" id="start" name="start" value="{{ isset($schedule) ? $schedule->start: old('start') }}">
        @error('start')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
    <div class="form-group col-xs-6">
        <label for="end">End</label>
        <input type="time" class="form-control @error('end') is-invalid @enderror" id="end" name="end" value="{{ isset($schedule) ? $schedule->end: old('end') }}">
        @error('end')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group row">
    <label for="first_athlete" class="col-sm-12 col-form-label">First Athlete</label>
    <div class="col-sm-12">
        <select name="first_athlete" class="form-control @error('first_athlete') is-invalid @enderror" id="first_athlete">
            <option value=""></option>
            @foreach($athletes as $athlete)
            <option value="{{ $athlete->id }}" @if(isset($first_athlete) && $first_athlete==$athlete->id)
                selected="selected"
                @endif
                >{{ $athlete->full_name }}</option>
            @endforeach
        </select>
        @error('first_athlete')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>

<div class="form-group row">
    <label for="second_athlete" class="col-sm-12 col-form-label">First Athlete</label>
    <div class="col-sm-12">
        <select name="second_athlete" class="form-control @error('second_athlete') is-invalid @enderror" id="second_athlete">
            <option value=""></option>
            @foreach($athletes as $athlete)
            <option value="{{ $athlete->id }}" @if(isset($second_athlete) && $second_athlete==$athlete->id)
                selected="selected"
                @endif
                >{{ $athlete->full_name }}</option>
            @endforeach
        </select>
        @error('second_athlete')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>
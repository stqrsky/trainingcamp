{{-- Title --}}
<div class="form-group row mb-3">
    <label for="title" class="col-sm-12 col-form-label">Session Title <span class="text-muted small">(optional)</span></label>
    <div class="col-sm-12">
        <input type="text" class="form-control" name="title" id="title" placeholder="e.g. Sparring Session"
               value="{{ isset($schedule) ? $schedule->title : old('title') }}" />
    </div>
</div>

{{-- Date --}}
<div class="form-group row mb-3">
    <label for="date" class="col-sm-12 col-form-label">Date</label>
    <div class="col-sm-12">
        <input type="text" class="form-control @error('date') is-invalid @enderror" name="date" id="date" value="{{ isset($schedule) ? $schedule->date_format : old('date', $prefill['date'] ?? null) }}" />
        @error('date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Start / End --}}
<div class="d-flex gap-3 mb-3">
    <div class="form-group flex-fill">
        <label for="start">Start</label>
        <input type="time" class="form-control @error('start') is-invalid @enderror" id="start" name="start" value="{{ isset($schedule) ? $schedule->start: old('start', $prefill['start'] ?? null) }}">
        @error('start')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="form-group flex-fill">
        <label for="end">End</label>
        <input type="time" class="form-control @error('end') is-invalid @enderror" id="end" name="end" value="{{ isset($schedule) ? $schedule->end: old('end', $prefill['end'] ?? null) }}">
        @error('end')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Athletes --}}
<div class="form-group row mb-3">
    <label for="first_athlete" class="col-sm-12 col-form-label">First Athlete</label>
    <div class="col-sm-12">
        <select name="first_athlete" class="form-control @error('first_athlete') is-invalid @enderror" id="first_athlete">
            <option value=""></option>
            @foreach($athletes as $athlete)
            <option value="{{ $athlete->id }}" @selected((string) old('first_athlete', $first_athlete ?? '') === (string) $athlete->id)>{{ $athlete->full_name }}</option>
            @endforeach
        </select>
        @error('first_athlete')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
<div class="form-group row mb-3">
    <label for="second_athlete" class="col-sm-12 col-form-label">Second Athlete</label>
    <div class="col-sm-12">
        <select name="second_athlete" class="form-control @error('second_athlete') is-invalid @enderror" id="second_athlete">
            <option value=""></option>
            @foreach($athletes as $athlete)
            <option value="{{ $athlete->id }}" @selected((string) old('second_athlete', $second_athlete ?? '') === (string) $athlete->id)>{{ $athlete->full_name }}</option>
            @endforeach
        </select>
        @error('second_athlete')<div class="invalid-feedback">{{ $message }}</div>@enderror
        @unless(isset($schedule))
        <div class="tc-partner-suggestions mt-2" id="partner-suggestions" data-url="{{ route('schedules.partners') }}" aria-live="polite" hidden></div>
        @endunless
    </div>
</div>

{{-- Status --}}
<div class="form-group row mb-3">
    <label for="status" class="col-sm-12 col-form-label">Status</label>
    <div class="col-sm-12">
        @php $currentStatus = old('status', isset($schedule) ? $schedule->status : 'planned'); @endphp
        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
            @foreach(\App\Models\Schedule::STATUSES as $key => $label)
            <option value="{{ $key }}" @selected($currentStatus === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Goal --}}
<div class="form-group row mb-3">
    <label for="goal" class="col-sm-12 col-form-label">Goal <span class="text-muted small">(optional)</span></label>
    <div class="col-sm-12">
        <textarea class="form-control @error('goal') is-invalid @enderror" name="goal" id="goal" rows="2"
                  placeholder="e.g. Work on the jab and footwork">{{ old('goal', isset($schedule) ? $schedule->goal : '') }}</textarea>
        @error('goal')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Location --}}
<div class="form-group row mb-3">
    <label for="location" class="col-sm-12 col-form-label">Location <span class="text-muted small">(optional)</span></label>
    <div class="col-sm-12">
        <div class="input-group">
            <span class="input-group-text"><span class="material-icons" style="font-size:18px">location_on</span></span>
            <input type="text" class="form-control" name="location" id="location" placeholder="e.g. HQ Main Gym"
                   value="{{ isset($schedule) ? $schedule->location : old('location') }}" />
        </div>
    </div>
</div>

{{-- Color --}}
<div class="form-group mb-3">
    <label class="col-form-label d-block mb-1">Color</label>
    @php
        $colorMap = ['blue'=>'#2563eb','green'=>'#16a34a','red'=>'#dc2626','orange'=>'#ea580c','purple'=>'#7c3aed','pink'=>'#db2777','teal'=>'#0d9488','amber'=>'#d97706'];
        $selectedColor = isset($schedule) ? ($schedule->color ?? 'blue') : old('color', 'blue');
    @endphp
    <div class="tc-color-picker">
        @foreach($colorMap as $name => $hex)
        <label class="tc-color-swatch {{ $selectedColor === $name ? 'selected' : '' }}" title="{{ ucfirst($name) }}">
            <input type="radio" name="color" value="{{ $name }}" @checked($selectedColor === $name) hidden>
            <span style="background:{{ $hex }}"></span>
        </label>
        @endforeach
    </div>
</div>

{{-- Video Conference --}}
<div class="form-group mb-3">
    <button type="button" class="btn btn-outline-secondary btn-sm w-100 text-start tc-video-toggle" id="videoToggle">
        <span class="material-icons align-middle me-1" style="font-size:18px">videocam</span>
        Add Video Call
    </button>
    @php $vt = isset($schedule) ? $schedule->video_type : old('video_type'); @endphp
    <div id="videoOptions" class="{{ $vt ? '' : 'd-none' }} tc-video-options mt-2">
        <input type="hidden" name="video_type" id="video_type" value="{{ $vt }}">
        @foreach(['google_meet'=>['Google Meet','#EA4335'],'zoom'=>['Zoom','#2D8CFF'],'gotomeeting'=>['GoToMeeting','#F68212']] as $key=>[$label,$clr])
        <div class="tc-video-option {{ $vt === $key ? 'selected' : '' }}" data-type="{{ $key }}">
            <span class="tc-video-icon" style="background:{{ $clr }}20;color:{{ $clr }}">
                <span class="material-icons" style="font-size:18px">videocam</span>
            </span>
            <span class="fw-500">{{ $label }}</span>
            @if($vt === $key)<span class="ms-auto material-icons text-success" style="font-size:18px">check_circle</span>@endif
        </div>
        @endforeach
        <div class="{{ $vt ? '' : 'd-none' }}" id="videoUrlWrap">
            <input type="url" class="form-control mt-2 @error('video_url') is-invalid @enderror"
                   name="video_url" id="video_url" placeholder="Paste meeting link…"
                   value="{{ isset($schedule) ? $schedule->video_url : old('video_url') }}">
            @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

{{-- Notes --}}
<div class="form-group mb-3">
    <label for="notes" class="col-form-label">Notes <span class="text-muted small">(optional)</span></label>
    <textarea class="form-control" name="notes" id="notes" rows="3"
              placeholder="Additional details…">{{ isset($schedule) ? $schedule->notes : old('notes') }}</textarea>
</div>

{{-- Result (after the session) --}}
@isset($schedule)
<div class="form-group mb-3">
    <label for="result" class="col-form-label">Result <span class="text-muted small">(after the session)</span></label>
    <textarea class="form-control @error('result') is-invalid @enderror" name="result" id="result" rows="3"
              placeholder="How did it go? What should be trained next?">{{ old('result', $schedule->result) }}</textarea>
    @error('result')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
@endisset

<script>
(function () {
    var toggle = document.getElementById('videoToggle');
    var opts   = document.getElementById('videoOptions');
    var vtInput = document.getElementById('video_type');
    var urlWrap = document.getElementById('videoUrlWrap');
    if (toggle) {
        toggle.addEventListener('click', function () { opts.classList.toggle('d-none'); });
    }
    document.querySelectorAll('.tc-video-option').forEach(function (el) {
        el.addEventListener('click', function () {
            document.querySelectorAll('.tc-video-option').forEach(function(o){ o.classList.remove('selected'); });
            el.classList.add('selected');
            vtInput.value = el.dataset.type;
            urlWrap.classList.remove('d-none');
            document.getElementById('video_url').focus();
        });
    });
})();
</script>
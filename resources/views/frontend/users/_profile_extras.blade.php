{{--
    Experience level, skill levels and weekly availability.
    Shared by the member form and the own profile form; $profileUser is the person being edited (or null).
--}}
@php
    $profileUser = $profileUser ?? null;
    $teamSkills = $teamSkills ?? collect();
    $currentExperience = old('experience_level', $profileUser?->userDetail?->experience_level);
    $currentLevels = old('skill_levels', $profileUser
        ? $profileUser->skills->pluck('pivot.level', 'id')->all()
        : []);
    $slots = old('availability', $profileUser
        ? $profileUser->availabilities->map(fn ($slot) => ['weekday' => $slot->weekday, 'start' => $slot->start, 'end' => $slot->end])->all()
        : []);
@endphp

<div class="form-group row">
    <label for="experience_level" class="col-sm-12 col-form-label">Experience level</label>
    <div class="col-sm-12">
        <select name="experience_level" id="experience_level" class="form-select @error('experience_level') is-invalid @enderror">
            <option value="">Not set</option>
            @foreach(\App\Models\Skill::LEVELS as $key => $label)
            <option value="{{ $key }}" @selected($currentExperience === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('experience_level')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>
</div>

{{-- Skills come from the team's catalog, so they are only offered once a team exists --}}
@if(auth()->user()->currentTeam())
<fieldset class="form-group row">
    <legend class="col-sm-12 col-form-label">Skills</legend>
    <div class="col-sm-12">
        @forelse($teamSkills as $skill)
        <div class="tc-skill-level-row">
            <label for="skill-{{ $skill->id }}" class="text-truncate">{{ $skill->name }}</label>
            <select name="skill_levels[{{ $skill->id }}]" id="skill-{{ $skill->id }}" class="form-select form-select-sm">
                <option value="">—</option>
                @foreach(\App\Models\Skill::LEVELS as $key => $label)
                <option value="{{ $key }}" @selected(($currentLevels[$skill->id] ?? null) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @empty
        <p class="text-muted small mb-0">
            This team has no skills yet. <a href="{{ route('teams.edit') }}">Add skills to the team</a>
        </p>
        @endforelse
        @error('skill_levels.*')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>
</fieldset>
@endif

<fieldset class="form-group row" data-availability>
    <legend class="col-sm-12 col-form-label">Availability <span class="text-muted small">(preferred sparring times)</span></legend>
    <div class="col-sm-12">
        <input type="hidden" name="availability_present" value="1">
        <div data-availability-rows>
            @foreach($slots as $index => $slot)
                @include('frontend.users._availability_row', ['index' => $index, 'slot' => $slot])
            @endforeach
        </div>
        @if($errors->has('availability.*') || $errors->has('availability'))
        <div class="text-danger small">Check the availability times: the end must be after the start.</div>
        @endif
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" data-availability-add>
            <span class="material-icons align-middle" style="font-size:16px" aria-hidden="true">add</span> Add time slot
        </button>
        <template data-availability-template>
            @include('frontend.users._availability_row', ['index' => '__INDEX__', 'slot' => ['weekday' => 1, 'start' => '18:00', 'end' => '20:00']])
        </template>
    </div>
</fieldset>

<script>
    // Add and remove availability rows; indices only need to be unique per submit
    (function () {
        var box = document.querySelector('[data-availability]')
        if (!box) return
        var rows = box.querySelector('[data-availability-rows]')
        var template = box.querySelector('[data-availability-template]')
        var next = rows.children.length
        box.querySelector('[data-availability-add]').addEventListener('click', function () {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replace(/__INDEX__/g, 'n' + next++))
        })
        rows.addEventListener('click', function (e) {
            var remove = e.target.closest('[data-availability-remove]')
            if (remove) remove.closest('.tc-availability-row').remove()
        })
    })()
</script>

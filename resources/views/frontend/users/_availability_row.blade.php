<div class="tc-availability-row">
    <label class="visually-hidden" for="availability-{{ $index }}-weekday">Weekday</label>
    <select name="availability[{{ $index }}][weekday]" id="availability-{{ $index }}-weekday" class="form-select form-select-sm">
        @foreach(\App\Models\Availability::WEEKDAYS as $number => $day)
        <option value="{{ $number }}" @selected((int) ($slot['weekday'] ?? 1) === $number)>{{ substr($day, 0, 3) }}</option>
        @endforeach
    </select>
    <label class="visually-hidden" for="availability-{{ $index }}-start">From</label>
    <input type="time" name="availability[{{ $index }}][start]" id="availability-{{ $index }}-start"
           class="form-control form-control-sm" value="{{ $slot['start'] ?? '' }}" required>
    <label class="visually-hidden" for="availability-{{ $index }}-end">To</label>
    <input type="time" name="availability[{{ $index }}][end]" id="availability-{{ $index }}-end"
           class="form-control form-control-sm" value="{{ $slot['end'] ?? '' }}" required>
    <button type="button" class="close" data-availability-remove aria-label="Remove time slot">
        <span class="material-icons" aria-hidden="true">close</span>
    </button>
</div>

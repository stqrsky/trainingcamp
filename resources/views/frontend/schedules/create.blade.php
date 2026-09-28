@extends('frontend.layouts.app')

@section('style')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<link rel="stylesheet" href="{{ asset('select2/css/select2.min.css') }}">
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
@endsection

@section('content')
<h4 class="title mt-4">Assignments</h4>
<div class="content create-schedule mt-1">
    <div class="card-body">
        @error('error')
        <div class="alert alert-danger" role="alert">
            {{ $message }}
        </div>
        @enderror
        <form action="{{ route('schedules.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            @include('frontend.schedules.form')

            <button type="submit" class="btn create btn-outline-dark float-right">Add</button>
            <a href="{{ route('schedules.index') }}" type="button" class="btn btn-warning btn-outline-dark float-right me-1">Cancel</a>
        </form>
    </div>
</div>
</div>
@endsection

@section('script')
<script src="{{ asset('select2/js/select2.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $('#first_athlete, #second_athlete').select2({
            placeholder: 'Select Athlete',
            allowClear: true
        });
        $('#date').daterangepicker({
            singleDatePicker: true,
            showDropdowns: true,
            minYear: 1901,
            maxYear: parseInt(moment().format('YYYY'), 10),
            locale: {
                format: 'DD/MM/YYYY'
            }
        });

        // Matching: suggest partners for the first athlete; a click fills partner, date and time
        var box = document.getElementById('partner-suggestions')
        var request = null
        function loadSuggestions(athleteId) {
            box.replaceChildren()
            box.hidden = !athleteId
            if (!athleteId) return
            if (request) request.abort()
            request = new AbortController()
            fetch(box.dataset.url + '?athlete=' + encodeURIComponent(athleteId), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: request.signal
            }).then(function (response) {
                return response.ok ? response.json() : { suggestions: [] }
            }).then(function (data) {
                var title = document.createElement('p')
                title.className = 'tc-partner-suggestions-title'
                title.textContent = data.suggestions.length ? 'Suggested partners' : 'No other active athletes to suggest'
                box.appendChild(title)
                data.suggestions.forEach(function (suggestion) {
                    var button = document.createElement('button')
                    button.type = 'button'
                    button.className = 'tc-partner-chip'
                    button.title = suggestion.reasons.join(' · ')
                    var name = document.createElement('strong')
                    name.textContent = suggestion.name
                    var meta = document.createElement('small')
                    meta.textContent = suggestion.points + '/' + suggestion.max + ' pts'
                        + (suggestion.slot ? ' · ' + suggestion.slot.label : '')
                    button.appendChild(name)
                    button.appendChild(meta)
                    button.addEventListener('click', function () {
                        $('#second_athlete').val(String(suggestion.id)).trigger('change')
                        if (suggestion.slot) {
                            $('#date').val(suggestion.slot.date)
                            var picker = $('#date').data('daterangepicker')
                            if (picker) picker.setStartDate(suggestion.slot.date)
                            $('#start').val(suggestion.slot.start)
                            $('#end').val(suggestion.slot.end)
                        }
                    })
                    box.appendChild(button)
                })
            }).catch(function () {})
        }
        $('#first_athlete').on('change', function () { loadSuggestions(this.value) })
        if ($('#first_athlete').val()) loadSuggestions($('#first_athlete').val())
    })

    function selectFile(event) {
        var input = event.target
        var filename = $(input)[0].files[0].name
        $('#filename').html(filename)
    }
</script>
@endsection
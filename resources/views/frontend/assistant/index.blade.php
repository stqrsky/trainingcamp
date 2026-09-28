@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title">Assistant</h4>
            @if($enabled && $exchanges)
            <form method="POST" action="{{ route('assistant.clear') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-link p-0 tc-link-strong">Clear chat</button>
            </form>
            @endif
        </div>

        @if(session()->has('msg'))
        <div class="alert alert-success" role="status">{{ session('msg') }}</div>
        @endif

        @unless($enabled)
        <section class="tc-home-section" aria-label="Assistant setup">
            <p class="mb-2"><strong>The assistant is off.</strong> No team data is sent anywhere.</p>
            <p class="mb-2 small">
                To turn it on, add your Anthropic API key to the <code>.env</code> file of the server
                (<code>ANTHROPIC_API_KEY=…</code>) and reload this page.
            </p>
            <p class="mb-0 small text-muted">
                Once it is on, your questions and the data the assistant looks up for the active team
                (tasks, sparrings, projects, member names, skills and availability) are sent to Anthropic's Claude API.
                Contact details, birth dates and body measurements are never included.
            </p>
        </section>
        @else
        <p class="small text-muted mb-3">
            Ask about tasks, sparrings, projects and members of the active team. Your questions and the data
            the assistant looks up are sent to Anthropic's Claude API. It can only read; task drafts need your confirmation.
        </p>

        @forelse($exchanges as $exchange)
        <article class="tc-assistant-exchange" @if($loop->last) id="latest" @endif>
            <p class="tc-assistant-question">{{ $exchange['question'] }}</p>
            <div class="tc-assistant-answer {{ $exchange['failed'] ? 'tc-assistant-answer--failed' : '' }}">
                <span class="material-icons" aria-hidden="true">auto_awesome</span>
                <p>{{ $exchange['answer'] }}</p>
            </div>
            @if($exchange['drafts'])
            <ul class="tc-assistant-drafts" aria-label="Task drafts">
                @foreach($exchange['drafts'] as $draft)
                <li class="tc-assistant-draft">
                    <div class="tc-assistant-draft-text">
                        <strong class="d-block">{{ $draft['title'] }}</strong>
                        <small class="text-muted">
                            {{ \App\Models\Task::PRIORITIES[$draft['priority']] ?? '' }}
                            @if($draft['assignee_name']) · {{ $draft['assignee_name'] }} @endif
                            @if($draft['due_date']) · due {{ \Carbon\Carbon::parse($draft['due_date'])->format('j M Y') }} @endif
                        </small>
                    </div>
                    @if($draft['task_id'])
                    <span class="tc-task-badge"><span class="material-icons" aria-hidden="true">check</span> Created</span>
                    @else
                    <a href="{{ route('tasks.create', ['draft' => $draft['id']]) }}" class="btn btn-sm tc-assistant-review">Review</a>
                    @endif
                </li>
                @endforeach
            </ul>
            @endif
        </article>
        @empty
        <div class="tc-assistant-suggestions" aria-label="Example questions">
            @foreach([
                'What is overdue right now?',
                'Which sparrings are planned for the next 7 days?',
                'How are our projects doing?',
                'Turn these meeting notes into tasks: ',
            ] as $suggestion)
            <button type="button" class="tc-chip" data-assistant-suggestion="{{ $suggestion }}">{{ trim($suggestion, ': ') }}</button>
            @endforeach
        </div>
        @endforelse

        <form method="POST" action="{{ route('assistant.ask') }}" class="tc-assistant-form">
            @csrf
            <label for="assistant-question" class="visually-hidden">Your question</label>
            <textarea class="form-control @error('question') is-invalid @enderror" name="question" id="assistant-question"
                      rows="3" maxlength="4000" required placeholder="Ask about your team…">{{ old('question') }}</textarea>
            @error('question')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="d-flex justify-content-between align-items-center mt-2">
                <small class="text-muted d-none d-sm-inline">Ctrl+Enter to send</small>
                <span class="d-sm-none"></span>
                <button type="submit" class="btn create btn-outline-dark">Ask</button>
            </div>
        </form>
        @endunless
    </div>
</div>
@endsection

@section('script')
<script>
(function () {
    var field = document.getElementById('assistant-question')
    if (!field) return
    document.querySelectorAll('[data-assistant-suggestion]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            field.value = chip.getAttribute('data-assistant-suggestion')
            field.focus()
            field.setSelectionRange(field.value.length, field.value.length)
        })
    })
    field.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey) && field.value.trim()) {
            event.preventDefault()
            field.form.requestSubmit()
        }
    })
})()
</script>
@endsection

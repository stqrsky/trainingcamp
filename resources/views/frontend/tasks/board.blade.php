@extends('frontend.layouts.app')

@section('content')
<div class="content schedule-body mb-5">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3 head-title">
            <h4 class="title">Task Board</h4>
            <a href="{{ route('tasks.create') }}" class="close btn-add" aria-label="Add task">
                <span class="material-icons add">add</span>
            </a>
        </div>
        @include('frontend.tasks._view_tabs')

        <p class="tc-board-hint">Drag a card to another column or change its status. Swipe to see all columns.</p>

        <div class="tc-board" data-board>
            @foreach($columns as $status => $tasks)
            <section class="tc-board-col" data-status="{{ $status }}" aria-labelledby="board-col-{{ $status }}">
                <header class="tc-board-col-header">
                    <h5 id="board-col-{{ $status }}">{{ \App\Models\Task::STATUSES[$status] }}</h5>
                    <span class="tc-board-count" data-count>{{ $tasks->count() }}</span>
                </header>
                <div class="tc-board-list" data-dropzone>
                    @foreach($tasks as $task)
                        @include('frontend.tasks._card', ['task' => $task])
                    @endforeach
                    <p class="tc-board-empty" data-empty @if($tasks->isNotEmpty()) hidden @endif>
                        {{ $mine ? 'Nothing assigned to you' : 'No tasks' }}
                    </p>
                </div>
            </section>
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    // Kanban: drag-and-drop or the per-card status select move a task and persist it via PATCH
    (function () {
        var board = document.querySelector('[data-board]')
        if (!board) return
        var dragged = null

        function column(status) {
            return board.querySelector('.tc-board-col[data-status="' + status + '"]')
        }

        function refresh(col) {
            var list = col.querySelector('[data-dropzone]')
            var count = list.querySelectorAll('.tc-board-card').length
            col.querySelector('[data-count]').textContent = count
            list.querySelector('[data-empty]').hidden = count > 0
        }

        function move(card, status) {
            var from = card.closest('.tc-board-col')
            var to = column(status)
            if (!to || from === to) return
            var select = card.querySelector('[data-status-select]')
            var form = select.form
            var next = card.nextElementSibling

            to.querySelector('[data-dropzone]').prepend(card)
            select.value = status
            refresh(from)
            refresh(to)
            card.classList.add('is-saving')

            fetch(form.action, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(form),
                credentials: 'same-origin'
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status)
            }).catch(function () {
                from.querySelector('[data-dropzone]').insertBefore(card, next)
                select.value = from.dataset.status
                refresh(from)
                refresh(to)
                alert('The task could not be moved. Please try again.')
            }).finally(function () {
                card.classList.remove('is-saving')
            })
        }

        board.addEventListener('dragstart', function (e) {
            var card = e.target.closest('.tc-board-card')
            if (!card) return
            dragged = card
            card.classList.add('is-dragging')
            e.dataTransfer.effectAllowed = 'move'
            e.dataTransfer.setData('text/plain', card.dataset.taskId)
        })

        board.addEventListener('dragend', function () {
            if (dragged) dragged.classList.remove('is-dragging')
            dragged = null
            board.querySelectorAll('.is-over').forEach(function (col) { col.classList.remove('is-over') })
        })

        board.querySelectorAll('.tc-board-col').forEach(function (col) {
            col.addEventListener('dragover', function (e) {
                if (!dragged) return
                e.preventDefault()
                col.classList.add('is-over')
            })
            col.addEventListener('dragleave', function (e) {
                if (!col.contains(e.relatedTarget)) col.classList.remove('is-over')
            })
            col.addEventListener('drop', function (e) {
                e.preventDefault()
                col.classList.remove('is-over')
                if (dragged) move(dragged, col.dataset.status)
            })
        })

        board.addEventListener('change', function (e) {
            if (!e.target.matches('[data-status-select]')) return
            var card = e.target.closest('.tc-board-card')
            var status = e.target.value
            e.target.value = card.closest('.tc-board-col').dataset.status
            move(card, status)
        })
    })()
</script>
@endsection

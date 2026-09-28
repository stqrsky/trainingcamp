{{-- Global search and command palette: Cmd/Ctrl+K or the search button in the top bar --}}
<dialog class="tc-palette" id="tc-palette" aria-labelledby="tc-palette-title">
    <h2 class="visually-hidden" id="tc-palette-title">Search and commands</h2>
    <div class="tc-palette-input">
        <span class="material-icons" aria-hidden="true">search</span>
        <input type="text" id="tc-palette-input" role="combobox" autocomplete="off" spellcheck="false"
               aria-expanded="true" aria-controls="tc-palette-list" aria-autocomplete="list"
               aria-label="Search members, tasks, sparrings or type a command"
               placeholder="Search or type a command…" data-search-url="{{ route('search') }}">
        <kbd>Esc</kbd>
    </div>
    <ul class="tc-palette-list" id="tc-palette-list" role="listbox" aria-label="Results"></ul>
    <p class="tc-palette-status" id="tc-palette-status" aria-live="polite"></p>
    <script type="application/json" id="tc-palette-commands">@json($paletteCommands)</script>
</dialog>

<script>
    (function () {
        var dialog = document.getElementById('tc-palette')
        if (!dialog || typeof dialog.showModal !== 'function') return
        var input = document.getElementById('tc-palette-input')
        var list = document.getElementById('tc-palette-list')
        var status = document.getElementById('tc-palette-status')
        var commands = JSON.parse(document.getElementById('tc-palette-commands').textContent)
        var items = []
        var active = 0
        var timer = null
        var controller = null

        function open() {
            if (dialog.open) return
            input.value = ''
            render(matchCommands(''), [])
            dialog.showModal()
            input.focus()
        }

        function matchCommands(query) {
            var words = query.toLowerCase().split(/\s+/).filter(Boolean)
            return commands.filter(function (command) {
                var haystack = (command.title + ' ' + (command.keywords || '')).toLowerCase()
                return words.every(function (word) { return haystack.indexOf(word) !== -1 })
            })
        }

        function option(item, index) {
            var li = document.createElement('li')
            li.id = 'tc-palette-option-' + index
            li.setAttribute('role', 'option')
            li.dataset.index = index
            var icon = document.createElement('span')
            icon.className = 'material-icons'
            icon.setAttribute('aria-hidden', 'true')
            icon.textContent = item.icon || 'chevron_right'
            var text = document.createElement('span')
            text.className = 'tc-palette-text'
            var title = document.createElement('strong')
            title.textContent = item.title
            text.appendChild(title)
            if (item.subtitle) {
                var subtitle = document.createElement('small')
                subtitle.textContent = item.subtitle
                text.appendChild(subtitle)
            }
            li.appendChild(icon)
            li.appendChild(text)
            return li
        }

        function heading(label) {
            var li = document.createElement('li')
            li.setAttribute('role', 'presentation')
            li.className = 'tc-palette-group'
            li.textContent = label
            return li
        }

        // Commands first, then search results grouped by type
        function render(matchedCommands, groups) {
            list.replaceChildren()
            items = []
            var sections = []
            if (matchedCommands.length) sections.push({ label: 'Commands', items: matchedCommands })
            sections = sections.concat(groups)
            sections.forEach(function (section) {
                list.appendChild(heading(section.label))
                section.items.forEach(function (item) {
                    list.appendChild(option(item, items.length))
                    items.push(item)
                })
            })
            status.textContent = items.length ? items.length + ' results' : (input.value.trim() ? 'No results' : '')
            select(0)
        }

        function select(index) {
            if (!items.length) {
                input.removeAttribute('aria-activedescendant')
                return
            }
            active = (index + items.length) % items.length
            list.querySelectorAll('[role="option"]').forEach(function (li) {
                li.setAttribute('aria-selected', li.dataset.index == active ? 'true' : 'false')
            })
            var current = document.getElementById('tc-palette-option-' + active)
            input.setAttribute('aria-activedescendant', current.id)
            current.scrollIntoView({ block: 'nearest' })
        }

        function run(item) {
            if (!item) return
            if (item.post) {
                var form = document.createElement('form')
                form.method = 'POST'
                form.action = item.post
                var token = document.createElement('input')
                token.type = 'hidden'
                token.name = '_token'
                token.value = document.querySelector('meta[name="csrf-token"]').content
                form.appendChild(token)
                document.body.appendChild(form)
                form.submit()
                return
            }
            window.location.href = item.url
        }

        function search() {
            var query = input.value.trim()
            var matched = matchCommands(query)
            render(matched, [])
            if (controller) controller.abort()
            clearTimeout(timer)
            if (query.length < 2) return
            timer = setTimeout(function () {
                controller = new AbortController()
                fetch(input.dataset.searchUrl + '?q=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin',
                    signal: controller.signal
                }).then(function (response) {
                    if (!response.ok) throw new Error('HTTP ' + response.status)
                    return response.json()
                }).then(function (data) {
                    if (input.value.trim() === query) render(matched, data.groups)
                }).catch(function (error) {
                    if (error.name !== 'AbortError') status.textContent = 'Search is not available right now.'
                })
            }, 200)
        }

        input.addEventListener('input', search)
        input.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); select(active + 1) }
            if (e.key === 'ArrowUp') { e.preventDefault(); select(active - 1) }
            if (e.key === 'Enter') { e.preventDefault(); run(items[active]) }
        })
        list.addEventListener('click', function (e) {
            var li = e.target.closest('[role="option"]')
            if (li) run(items[li.dataset.index])
        })
        // Close when clicking the backdrop (outside the dialog box)
        dialog.addEventListener('click', function (e) {
            var box = dialog.getBoundingClientRect()
            var outside = e.clientX < box.left || e.clientX > box.right || e.clientY < box.top || e.clientY > box.bottom
            if (outside && e.clientX !== 0) dialog.close()
        })
        document.querySelectorAll('[data-palette-open]').forEach(function (button) {
            button.addEventListener('click', open)
        })
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault()
                open()
            }
        })
    })()
</script>

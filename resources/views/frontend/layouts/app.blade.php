<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Training Camp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css')}}">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script defer src="{{ asset('js/app.js')}}"></script>
    @yield('style')
</head>

<body>
    <div class="container-fluid base">
        <div class="row justify-content-center">
            <div class="col-md-4 col-sm-6 content">
                @if(Route::currentRouteName() != 'user.setting')
                @include('frontend.includes.header')
                @endif

                @yield('content')

                @if(Route::currentRouteName() != 'user.setting')
                @include('frontend.includes.navbarbottom')
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.slim.min.js"></script>
    @yield('script')
    <script>
        // Auto-dismiss alerts after 3s
        document.querySelectorAll('.alert').forEach(function(alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 0.5s'
                alert.style.opacity = '0'
                setTimeout(function() { alert.remove() }, 500)
            }, 3000)
        })

        // Delete confirmation on all delete forms
        document.querySelectorAll('form').forEach(function(form) {
            var input = form.querySelector('input[name="_method"][value="DELETE"]')
            if (!input) return
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to delete this?')) e.preventDefault()
            })
        })

        // Submit spinner on all submit buttons
        document.querySelectorAll('form:not([data-no-spinner])').forEach(function(form) {
            form.addEventListener('submit', function() {
                form.querySelectorAll('[type="submit"]').forEach(function(btn) {
                    btn.disabled = true
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + btn.innerText
                })
            })
        })
    </script>
</body>

</html>

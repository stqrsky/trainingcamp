<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ isset($title) ? $title : 'Training Camp' }}</title>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="{{ asset('css/app.css')}}">
    <meta name="theme-color" content="#563d7c">

    <!-- Custom styles for this template -->
    <link rel="stylesheet" href="{{ asset('/css/signin.css') }}">
</head>

<body>
    @yield('content')
</body>

</html>
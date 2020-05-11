@extends('backend.layout')

@section('content')
<form class="form-signin text-center">
    <div class="text-center">
        <img class="img" src="{{ asset('assets/images/TCtransp.png') }}" alt="Training Camp" width="280" height="280">
    </div>

    <h1 class="h3 mb-3 font-weight-normal"></h1>
    @error('error')
    <div class="alert alert-danger" role="alert">
        {{ $message }}
    </div>
    @enderror
    <div class="form-label-group">
        <input type="email" id="email" class="form-control" placeholder="Email address" required autofocus>
        <label for="email">Email address</label>
        @error('email')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" id="password" class="form-control" placeholder="Password" required>
        <label for="password">Password</label>
        @error('password')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <button class="btn btn-lg text-white bg-dark btn-block" type="submit">Sign in</button>
    <p class="mt-5 mb-3 text-muted">
        Don't have an account?
        <a class="mt-5 mb-3" href="{{ route('signup') }}">SIGN UP</a>
    </p>
</form>
@endsection
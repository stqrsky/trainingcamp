@extends('backend.layout')

@section('content')
<form class="form-signin text-center" method="POST" action="{{ route('login.post') }}">
    <div class="text-center">
        <img class="img-login" src="{{ asset('assets/images/TCtransp.png') }}" alt="Training Camp" width="260" height="260">
    </div>
    @csrf
    <h1 class="h3 mb-4 font-weight-normal"></h1>
    @error('error')
    <div class="alert alert-danger" role="alert">
        {{ $message }}
    </div>
    @enderror
    <div class="form-label-group">
        <input type="email" name="email" id="email" class="form-control" placeholder="" required autofocus>
        <label for="email">Email address</label>
        @error('email')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" name="password" id="password" class="form-control" placeholder="" required>
        <label for="password">Password</label>
        @error('password')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <button class="btn login mt-4 btn-lg text-white btn-block" type="submit">LOGIN</button>
    <p class="mt-3 mb-3 text-white">
        Don't have an account?
        <a class="mt-5 mb-3" href="{{ route('signup') }}">SIGN UP</a>
    </p>
</form>
@endsection
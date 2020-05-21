@extends('backend.layout')

@section('content')
<form class="form-signin" method="POST" action="{{ route('signup.post') }}">
    @csrf
    <div class="text-center">
        <img class="img-signup mb-4 mt-3" src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Training Camp" width="169" height="169">
        <h1 class="h3 mb-4 font-weight-normal">SIGN UP</h1>
    </div>
    @error('error')
    <div class="alert alert-danger" role="alert">
        {{ $message }}
    </div>
    @enderror
    <div class="form-label-group">
        <input type="email" id="inputEmail" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email address" required autofocus value="{{ old('email') }}">
        <label for="inputEmail">Email address</label>
        @error('email')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required value="{{ old('password') }}">
        <label for="password">Password</label>
        @error('password')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control @error('password') is-invalid @enderror" placeholder="Password" required value="{{ old('password_confirmation') }}">
        <label for="password_confirmation">Confirm Password</label>
        @error('password')<div class="invalid-feedback float-left">{{ $message }}</div>@enderror
    </div>

    <button class="btn signup btn-lg text-white btn-block" type="submit">SIGN UP NOW</button>
    <p class="mt-3 mb-3 text-white text-center">
        Already have an account?
        <a class="mt-3 mb-3" href="{{ route('login') }}">LOGIN</a>
    </p>
</form>
@endsection
@extends('backend.layout')

@section('content')
<form class="form-signin" method="POST" action="{{ route('signup.post') }}">
    @csrf
    <div class="tc-auth-brand">
        <img src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Trainingcamp logo">
        <h1>Create your account</h1>
        <p>Join your team on Trainingcamp</p>
    </div>

    @error('error')
    <div class="alert" role="alert">{{ $message }}</div>
    @enderror

    <div class="form-label-group">
        <input type="email" id="inputEmail" name="email" autocomplete="email"
               class="@error('email') is-invalid @enderror" placeholder="Email address" required autofocus value="{{ old('email') }}">
        <label for="inputEmail">Email address</label>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" id="password" name="password" autocomplete="new-password"
               class="@error('password') is-invalid @enderror" placeholder="Password" required>
        <label for="password">Password</label>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
               class="@error('password') is-invalid @enderror" placeholder="Confirm password" required>
        <label for="password_confirmation">Confirm password</label>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <button class="btn signup" type="submit">Create account</button>

    <p class="tc-auth-footer">
        Already have an account?
        <a href="{{ route('login') }}">Sign in</a>
    </p>
</form>
@endsection

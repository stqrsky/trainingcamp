@extends('backend.layout')

@section('content')
<form class="form-signin" method="POST" action="{{ route('login.post') }}">
    @csrf
    <div class="tc-auth-brand">
        <img src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Trainingcamp logo">
        <h1>Trainingcamp</h1>
        <p>Sign in to manage your bootcamp</p>
    </div>

    @error('error')
    <div class="alert" role="alert">{{ $message }}</div>
    @enderror

    <div class="form-label-group">
        <input type="email" name="email" id="email" autocomplete="email"
               class="@error('email') is-invalid @enderror" placeholder="Email address" required autofocus>
        <label for="email">Email address</label>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="form-label-group">
        <input type="password" name="password" id="password" autocomplete="current-password"
               class="@error('password') is-invalid @enderror" placeholder="Password" required>
        <label for="password">Password</label>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <button class="btn login" type="submit">Sign in</button>

    <p class="tc-auth-footer">
        Don't have an account?
        <a href="{{ route('signup') }}">Sign up</a>
    </p>
</form>
@endsection

@extends('backend.layout')

@section('content')
<form class="form-signin">
    <div class="text-center">
        <img class="img-signup" src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Training Camp" width="185" height="185">
        <h1 class="h3 mb-3 font-weight-normal">SIGN UP</h1>
    </div>

    <div class="form-label-group">
        <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
        <label for="inputEmail">Email address</label>
    </div>

    <div class="form-label-group">
        <input type="password" id="password" class="form-control" placeholder="Password" required>
        <label for="password">Password</label>
    </div>

    <div class="form-label-group">
        <input type="password" id="password_confirmation" class="form-control" placeholder="Password" required>
        <label for="password_confirmation">Confirm Password</label>
    </div>

    <button class="btn btn-lg btn-link bg-dark btn-block" type="submit">SIGN UP NOW</button>
</form>
@endsection
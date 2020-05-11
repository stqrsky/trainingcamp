@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content">
            <form method="POST" action="{{ route('user.account.setting.put') }}">
                @csrf
                @method('PUT')
                <div class="form-group row">
                    <label for="email" class="col-sm-12 col-form-label">Email</label>
                    <div class="col-sm-12">
                        <input type="email" class="form-control" id="email" placeholder="Email" name="email">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary float-right">Save</button>
                    </div>
                </div>
            </form>
            <form method="POST" action="{{ route('user.account.setting.put') }}">
                @csrf
                @method('PUT')
                <div class="form-group row">
                    <label for="current_password" class="col-sm-12 col-form-label">Current Password</label>
                    <div class="col-sm-12">
                        <input type="password" class="form-control" id="current_password" placeholder="Current Pasword" name="current_password">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="new_password" class="col-sm-12 col-form-label">New Password</label>
                    <div class="col-sm-12">
                        <input type="password" class="form-control" id="new_password" placeholder="New Password" name="new_password">
                    </div>
                </div>
                <div class="form-group row">
                    <label for="confirm_new_password" class="col-sm-12 col-form-label">Confirm New Password</label>
                    <div class="col-sm-12">
                        <input type="password" class="form-control" id="confirm_new_password" placeholder="Height" name="confirm_new_password">
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary float-right">Update Password</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

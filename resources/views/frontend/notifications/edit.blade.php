@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content pb-3 mb-3">
            <div class="card">

                <div class="card-body">
                    @error('error')
                    <div class="alert alert-danger" role="alert">
                        {{ $message }}
                    </div>
                    @enderror
                    <form method="POST" action="{{ route('notification.update', ['notification' => $notification->id]) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        @include('frontend.notifications.form')
                        <button type="submit" class="btn btn-primary float-right">Update</button>
                    </form>

                </div>

            </div>
        </div>
    </div>
</div>
@endsection
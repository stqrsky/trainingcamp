@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content pb-3">
            <div class="card">

                <div class="card-body">

                    <form method="POST" action="">
                        @csrf
                        @include('frontend.notifications.form')
                        <button type="submit" class="btn btn-primary float-right">Create</button>
                    </form>

                </div>

            </div>
        </div>
    </div>
</div>
<!-- Card -->
@endsection
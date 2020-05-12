@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <span></span>
                <h4 class="title">Bootcamp Overview</h4>
                <button onclick="" type="button" class="ml-2 mb-1 close btn-add" name="button">
                    <span aria-hidden="true" class="material-icons">add</span>
                </button>
            </div>
            @for($i = 1; $i < 4; $i++) <div class="card home mb-3">
                <div aria-live="assertive" aria-atomic="true">
                    <div class="toast-header d-flex align-items-center">
                        <img src="{{ asset('assets/images/2.jpg') }}" class="rounded-circle mr-3" height="50px" width="50px" alt="avatar">
                        <strong class="mr-auto"></strong>
                        <small class="text-muted">just now</small>
                        <div class="btn-group">
                            <button type="button" class="ml-2 mb-1 close" id="dropdown{{$i}}" data-dismiss="toast" aria-label="Close" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span aria-hidden="true" class="material-icons">more_vert</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{$i}}">
                                <form action="#" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="submit" class="dropdown-item" value="Delete" />
                                </form>
                                <a class="dropdown-item" href="#">Edit</a>
                            </div>
                        </div>
                    </div>
                    <div class="toast-body">
                        <h4 class="card-title font-weight-bold mb-2 andreas">Welcome to the Training Bootcamp!</h4>
                        See? Just like this.
                    </div>
                </div>
                <!-- <div class="card-body d-flex flex-row"> -->

                <!-- Avatar -->
                <!-- <img src="https://mdbootstrap.com/img/Photos/Avatars/avatar-8.jpg" class="rounded-circle mr-3" height="50px" width="50px" alt="avatar"> -->

                <!-- Content -->
                <!-- <div> -->

                <!-- Title -->
                <!-- <h4 class="card-title font-weight-bold mb-2 andreas">New spicy meals</h4> -->
                <!-- Subtitle -->
                <!-- <p class="card-text"><i class="far fa-clock pr-2"></i>07/24/2018</p> -->

                <!-- </div> -->

                <!-- </div> -->

                <!-- Card image -->
                <div class="view overlay">
                    <img class="card-img-top rounded-0" src="{{ asset('assets/images/mmatraining.jpg') }}" alt="Card image cap">
                    <a href="#!">
                        <div class="mask rgba-white-slight"></div>
                    </a>
                </div>

                <!-- Card content -->
                <div class="card-body">

                    <div class="collapse-content">

                        <!-- Text -->
                        <p class="card-text collapse" id="collapseContent">Recently, we added several exotic new dishes to our restaurant menu. They come from countries such as Mexico, Argentina, and Spain. Come to us, have some delicious wine and enjoy our juicy meals from around the world.</p>
                        <!-- Button -->
                        <a class="btn btn-flat red-text p-1 my-1 mr-0 mml-1 collapsed" data-toggle="collapse" href="#collapseContent" aria-expanded="false" aria-controls="collapseContent"></a>
                        <i class="fas fa-share-alt text-muted float-right p-1 my-1" data-toggle="tooltip" data-placement="top" title="Share this post"></i>
                        <i class="fas fa-heart text-muted float-right p-1 my-1 mr-3" data-toggle="tooltip" data-placement="top" title="I like it"></i>
                    </div>
                </div>
        </div>
        @endfor
    </div>
</div>
</div>
<!-- Card -->
@endsection
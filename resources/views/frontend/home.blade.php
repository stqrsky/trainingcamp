@extends('frontend.layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12 content mb-5 pb-3">
            <div class="d-flex justify-content-between align-items-center mb-3 head-title">
                <span></span>
                <h4 class="title">Bootcamp Overview</h4>
                <a href="{{ route('notification.create') }}" type="button" class="ml-2 mb-1 close btn-add" name="button">
                    <span aria-hidden="true" class="material-icons add">add</span>
                </a>
            </div>
            @foreach($notifications as $notification)
            <div class="card home mb-3">
                <div aria-live="assertive" aria-atomic="true">
                    <div class="toast-header d-flex align-items-center">
                        <img src="{{
                            $notification->user->userDetail && $notification->user->userDetail->image ? 
                            asset($notification->user->userDetail->image->file_name) :
                            "https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcRQ8xzdv564ewROcTBYDdv51oTD5SgNOCDDwMw4XXIdvxFGyQzn&usqp=CAU"
                        }}" class="rounded-circle mr-3" height="50px" width="50px" alt="avatar">
                        <strong class="mr-auto"></strong>
                        <small class="text-muted">{{ $notification->time }}</small>
                        <div class="btn-group">
                            <button type="button" class="ml-2 mb-1 close" id="dropdown{{$notification->id}}" data-dismiss="toast" aria-label="Close" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span aria-hidden="true" class="material-icons">more_vert</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown{{$notification->id}}">
                                <a class="dropdown-item" href="{{ route('notification.edit', ['notification' => $notification->id]) }}">Edit</a>
                                <form action="{{ route('notification.destroy', ['notification' => $notification->id]) }}" method="POST">
                                    @csrf
                                    @method('DELETE')
                                    <input type="submit" class="dropdown-item" value="Delete" />
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="toast-body" style="white-space: pre-line">
                        <h4 class="card-title font-weight-bold mb-2 andreas">{{ $notification->title }}</h4>
                        {!! $notification->description !!}
                    </div>
                </div>

                <!-- Card image -->
                @if($notification->image)
                <div class="view overlay">
                    <img class="card-img-top rounded-0" src="{{ asset($notification->image->file_name) }}" alt="Card image cap">
                    <a href="#!">
                        <div class="mask rgba-white-slight"></div>
                    </a>
                </div>
                @endif
            </div>
            @endforeach

            <div class="d-flex justify-content-center">
                {!! $notifications->links() !!}
            </div>
        </div>
    </div>
</div>
<!-- Card -->
@endsection
<nav class="navbar navbar-bottom fixed-bottom navbar-expand-lg navbar-light justify-content-center">
    <div class="col-md-4 col-sm-6">
        <ul class="nav nav-justified">
            <li class="nav-item">
                <a class="nav-link active" href="{{ route('home') }}">
                    <i class="material-icons">format_list_bulleted</i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('schedules') }}">
                    <i class="material-icons">date_range</i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.athletes') }}">
                    <i class="material-icons">people_outline</i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('user.profile') }}">
                    <i class="material-icons">reorder</i>
                </a>
            </li>
        </ul>
    </div>
</nav>
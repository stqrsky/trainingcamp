<nav class="navbar navbar-bottom fixed-bottom navbar-expand-lg navbar-light justify-content-center">
    <div class="col-md-4 col-sm-6">
        <ul class="nav nav-justified">
            <li class="nav-item">
                <a class="nav-link activen pb-0" href="{{ route('home') }}">
                    <i class="material-icons">format_list_bulleted</i>
                </a>
                <h6 class="mb-0 mt-0 text-white font-italic">Overview</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link pb-0" href="{{ route('schedules') }}">
                    <i class="material-icons">date_range</i>
                </a>
                <h6 class="mb-0 mt-0 text-white font-italic">Schedule</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link pb-0" href="{{ route('user.athletes') }}">
                    <i class="material-icons">people_outline</i>
                </a>
                <h6 class="mb-0 mt-0 text-white font-italic">Athletes</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link pb-0" href="{{ route('user.profile') }}">
                    <i class="material-icons">reorder</i>
                </a>
                <h6 class="mb-0 mt-0 text-white font-italic">Profile</h6>
            </li>
        </ul>
    </div>
</nav>
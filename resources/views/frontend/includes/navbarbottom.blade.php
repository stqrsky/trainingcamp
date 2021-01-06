<nav class="navbar navbar-bottom fixed-bottom navbar-expand-lg navbar-light justify-content-center">
    <div class="col-md-4 col-sm-6">
        <ul class="nav nav-justified">
            <li class="nav-item {{ set_active(['home', 'notification.create', 'notification.edit']) }}">
                <a class="nav-link activen pb-0" href="{{ route('home') }}">
                    <i class="material-icons">format_list_bulleted</i>
                </a>
                <h6 class="mb-0 mt-0 font-italic">Overview</h6>
            </li>
            <li class="nav-item {{ set_active(['schedules.index', 'schedules.create', 'schedules.edit']) }}">
                <a class="nav-link pb-0" href="{{ route('schedules.index') }}">
                    <i class="material-icons">date_range</i>
                </a>
                <h6 class="mb-0 mt-0 font-italic">Schedule</h6>
            </li>
            <li class="nav-item {{ set_active(['user.athletes', 'user.athletes.create', 'user.athletes.edit', 'user.athletes.detail']) }}">
                <a class="nav-link pb-0" href="{{ route('user.athletes') }}">
                    <i class="material-icons">people_outline</i>
                </a>
                <h6 class="mb-0 mt-0 font-italic">Athletes</h6>
            </li>
            <li class="nav-item {{ set_active(['user.profile', 'user.profile.setting', 'user.account.setting']) }}">
                <a class="nav-link pb-0" href="{{ route('user.profile') }}">
                    <i class="material-icons">reorder</i>
                </a>
                <h6 class="mb-0 mt-0 font-italic">Profile</h6>
            </li>
        </ul>
    </div>
</nav>
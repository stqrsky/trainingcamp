<nav class="navbar navbar-bottom fixed-bottom navbar-expand-lg navbar-light justify-content-center d-lg-none" aria-label="Main">
    <div class="tc-bottom-nav-inner">
        <ul class="nav nav-justified">
            @foreach(config('navigation.items') as $item)
            @php $isActive = request()->routeIs(...$item['active']); @endphp
            <li class="nav-item {{ $isActive ? 'active' : '' }}">
                <a class="nav-link pb-0" href="{{ route($item['route']) }}" @if($isActive) aria-current="page" @endif>
                    <i class="material-icons" aria-hidden="true">{{ $item['icon'] }}</i>
                    <span class="tc-nav-label">{{ $item['label'] }}</span>
                </a>
            </li>
            @endforeach
        </ul>
    </div>
</nav>

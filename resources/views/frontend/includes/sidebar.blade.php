<nav class="tc-sidebar d-none d-lg-flex" aria-label="Main">
    <ul>
        @foreach(config('navigation.items') as $item)
        @php $isActive = request()->routeIs(...$item['active']); @endphp
        <li>
            <a href="{{ route($item['route']) }}" class="{{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="page" @endif>
                <span class="material-icons" aria-hidden="true">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        </li>
        @endforeach
    </ul>
</nav>

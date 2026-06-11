<nav class="navbar fixed-top navbar-expand-lg d-flex align-items-center">
    <a class="d-flex align-items-center gap-2 text-decoration-none" href="{{ route('home') }}">
        <img class="img" src="{{ asset('assets/images/TCTrainingCampLogo.png') }}" alt="Trainingcamp" width="40" height="40">
        <span class="tc-brand">Trainingcamp</span>
    </a>
    <button type="button" class="tc-theme-toggle" data-tc-theme-toggle aria-label="Toggle dark mode" title="Toggle dark mode">
        <span class="material-icons tc-icon-light">dark_mode</span>
        <span class="material-icons tc-icon-dark">light_mode</span>
    </button>
</nav>

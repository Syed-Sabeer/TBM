@props(['class' => ''])
<a class="logo {{ $class ?? '' }}" href="{{ route('home') }}" aria-label="TBM — Tote Bag Market">
    <svg class="logo-mark" viewBox="0 0 40 40" fill="none" aria-hidden="true">
        <rect width="40" height="40" rx="10" fill="#1C3D2E"/>
        <path d="M12 15h16l1 13.5a1.2 1.2 0 0 1-1.2 1.3H12.2a1.2 1.2 0 0 1-1.2-1.3Z" fill="#F7F4ED"/>
        <path d="M16 15.5c0-4 8-4 8 0" stroke="#B9793C" stroke-width="2.1" stroke-linecap="round"/>
        <path d="M15 20.5h10" stroke="#1C3D2E" stroke-width="1.8" stroke-linecap="round"/>
    </svg>
    <span class="logo-text"><b>TBM</b><small>Tote Bag Market</small></span>
</a>

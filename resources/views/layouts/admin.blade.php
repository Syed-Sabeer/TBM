<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Back office') — TBM Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400..700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
@stack('styles')
<link rel="icon" href="{{ asset('favicon.svg') }}">
</head>
<body class="adm-body-root">

<div class="adm">
    @include('partials.admin-sidebar')

    <div class="adm-body">
        <header class="adm-top">
            <div>
                <h1>@yield('heading', 'Back office')</h1>
                <p>@yield('subheading')</p>
            </div>

            <div class="adm-top-right">
                @yield('toolbar')

                <div class="adm-user">
                    <span class="adm-avatar">{{ auth()->user()->initials() }}</span>
                    <div>
                        <b>{{ auth()->user()->name }}</b>
                        <span>{{ auth()->user()->getRoleNames()->map(fn ($r) => \Illuminate\Support\Str::headline($r))->join(', ') }}</span>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="icon-btn" type="submit" aria-label="Sign out"><x-icon name="logout"/></button>
                    </form>
                </div>
            </div>
        </header>

        <main class="adm-main">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>

<script src="{{ asset('assets/js/admin.js') }}" defer></script>
@stack('scripts')
</body>
</html>

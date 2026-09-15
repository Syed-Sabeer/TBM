<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Wholesale tote bags') — TBM</title>
<meta name="description" content="@yield('meta', 'Wholesale tote bags and reusable shopping bags, stocked in Los Angeles, New Jersey and Dallas. Trade accounts only.')">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400..700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
@stack('styles')
<link rel="icon" href="{{ asset('favicon.svg') }}">
</head>
<body class="@yield('body-class')">

@include('partials.announce')
@include('partials.header')

<main id="main">
    @hasSection('breadcrumb')
        <div class="breadcrumb-bar"><div class="container">@yield('breadcrumb')</div></div>
    @endif

    @include('partials.flash')

    @yield('content')
</main>

@include('partials.footer')
@include('partials.mobile-nav')

<div class="toast-wrap" id="toastWrap" aria-live="polite"></div>

<script src="{{ asset('assets/js/app.js') }}" defer></script>
@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title') — TBM</title>
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
<style>
    /* Documents print, so they carry their own sheet rather than the site's. */
    body { background:#fff; color:#14201A; font-family:Inter, system-ui, sans-serif; }
    .doc { max-width: 820px; margin: 0 auto; padding: 40px 32px 64px; }
    .doc-head { display:flex; justify-content:space-between; gap:32px; align-items:flex-start;
                border-bottom:2px solid #14201A; padding-bottom:20px; margin-bottom:28px; }
    .doc-title { font-family:Fraunces, Georgia, serif; font-size:1.75rem; margin:0 0 4px; }
    .doc-meta { font-size:.8125rem; line-height:1.7; text-align:right; }
    .doc-parties { display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-bottom:28px; }
    .doc-parties h4 { font-size:.6875rem; letter-spacing:.08em; text-transform:uppercase;
                      color:#6B7A70; margin:0 0 6px; }
    .doc-parties p { margin:0; font-size:.875rem; line-height:1.6; }
    .doc table { width:100%; border-collapse:collapse; font-size:.8125rem; }
    .doc th { text-align:left; border-bottom:1.5px solid #14201A; padding:8px 6px; font-size:.6875rem;
              text-transform:uppercase; letter-spacing:.06em; color:#3C4A42; }
    .doc td { padding:9px 6px; border-bottom:1px solid #E6E2D8; vertical-align:top; }
    .doc .num { text-align:right; }
    .doc .mono { font-family:'IBM Plex Mono', ui-monospace, monospace; }
    .doc tfoot td { border-bottom:none; padding-top:6px; }
    .doc tfoot .row-total td { border-top:1.5px solid #14201A; font-weight:700; font-size:.9375rem; }
    .doc-foot { margin-top:36px; padding-top:16px; border-top:1px solid #E6E2D8;
                font-size:.75rem; color:#6B7A70; line-height:1.7; }
    .doc-print { margin-bottom:20px; }
    @media print { .doc-print { display:none; } .doc { padding:0; } }
</style>
</head>
<body>
<div class="doc">
    <div class="doc-print">
        <button class="btn btn-outline btn-sm" type="button" onclick="window.print()">Print or save as PDF</button>
    </div>
    @yield('content')
</div>
</body>
</html>

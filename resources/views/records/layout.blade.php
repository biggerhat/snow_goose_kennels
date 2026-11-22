<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - {{ config('app.name') }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font: 13px/1.45 Georgia, 'Times New Roman', serif; color: #111; margin: 0; background: #f4f1ea; }
        .toolbar { position: sticky; top: 0; background: #1c1a17; color: #fff; padding: 10px 18px; display: flex; gap: 12px; align-items: center; font-family: system-ui, sans-serif; }
        .toolbar a, .toolbar button { color: #fff; background: #3a362f; border: 0; border-radius: 6px; padding: 7px 14px; font: inherit; text-decoration: none; cursor: pointer; }
        .page { max-width: 8.5in; margin: 18px auto; background: #fff; padding: 0.6in; box-shadow: 0 1px 6px rgba(0,0,0,.15); }
        .record { page-break-after: always; }
        .record:last-child { page-break-after: auto; }
        h1 { font-size: 22px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 18px 0 6px; padding-bottom: 3px; border-bottom: 1px solid #999; text-transform: uppercase; letter-spacing: .05em; }
        .sub { color: #555; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin: 4px 0; }
        th, td { text-align: left; vertical-align: top; padding: 3px 8px 3px 0; border-bottom: 1px solid #ddd; }
        th { font-weight: 600; width: 28%; color: #333; }
        table.grid th { width: auto; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; }
        .muted { color: #666; }
        .foot { margin-top: 22px; font-size: 11px; color: #666; border-top: 1px solid #ccc; padding-top: 6px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page { box-shadow: none; margin: 0; padding: 0; max-width: none; }
            @page { margin: 0.6in; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('admin.records') }}">← Back to Records</a>
        <button onclick="window.print()">Print or save as PDF</button>
        <span style="margin-left:auto;opacity:.7">{{ $title }}</span>
    </div>
    <div class="page">
        @yield('content')
    </div>
</body>
</html>

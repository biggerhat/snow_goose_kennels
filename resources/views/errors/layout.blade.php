<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <style>
        :root { color-scheme: light dark; --bg: #ece7dc; --fg: #17140f; --muted: #6b655b; --card: #f8f4ec; --line: #cfc8ba; }
        @media (prefers-color-scheme: dark) { :root { --bg: #14120f; --fg: #f0ebe1; --muted: #a39d92; --card: #1c1a16; --line: #3a362f; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: var(--bg); color: var(--fg); font: 17px/1.55 Georgia, 'Times New Roman', serif; }
        main { width: 100%; max-width: 30rem; text-align: center; background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 40px 28px; }
        img { width: 64px; height: auto; }
        .code { margin: 10px 0 0; font: 600 13px/1 system-ui, sans-serif; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); }
        h1 { margin: 8px 0 10px; font-size: 30px; line-height: 1.15; }
        p { margin: 0 0 22px; color: var(--muted); }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; font-family: system-ui, sans-serif; }
        a.btn { display: inline-block; padding: 12px 20px; border-radius: 999px; background: var(--fg); color: var(--bg); text-decoration: none; font-weight: 500; }
        a.ghost { background: transparent; color: var(--fg); border: 1px solid var(--line); }
    </style>
</head>
<body>
    <main>
        <img src="/favicon.svg" alt="">
        <p class="code">@yield('code')</p>
        <h1>@yield('heading')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            <a class="btn" href="{{ url('/') }}">Back to the home page</a>
            @hasSection('extra') @yield('extra') @endif
        </div>
    </main>
</body>
</html>

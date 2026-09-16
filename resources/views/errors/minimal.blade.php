@php
    $institutionName = config('app.display_name') === 'Laravel' ? 'Harare Polytechnic' : config('app.display_name');

    $currentUrl = url()->current();
    $previousUrl = url()->previous();
    $homeUrl = url('/');
    $currentOrigin = parse_url($homeUrl);
    $previousOrigin = parse_url($previousUrl);
    $isSameOrigin = ($previousOrigin['scheme'] ?? null) === ($currentOrigin['scheme'] ?? null)
        && ($previousOrigin['host'] ?? null) === ($currentOrigin['host'] ?? null)
        && ($previousOrigin['port'] ?? null) === ($currentOrigin['port'] ?? null);
    $backUrl = $previousUrl !== $currentUrl && $isSameOrigin ? $previousUrl : $homeUrl;

    $code = trim($__env->yieldContent('code'));

    // Each status gets its own accent and glyph so the page reads at a glance.
    // Tones are space separated RGB channels: they feed rgb(var(--tone) / <alpha>).
    $tones = [
        '401' => ['79 70 229', '129 140 248'],
        '402' => ['217 119 6', '251 191 36'],
        '403' => ['225 29 72', '251 113 133'],
        '404' => ['99 102 241', '165 180 252'],
        '419' => ['217 119 6', '251 191 36'],
        '429' => ['234 88 12', '251 146 60'],
        '500' => ['220 38 38', '248 113 113'],
        '503' => ['2 132 199', '56 189 248'],
    ];

    $glyphs = [
        '401' => '<rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        '402' => '<rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/>',
        '403' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        '404' => '<circle cx="12" cy="12" r="10"/><path d="m16.24 7.76-1.8 5.41a2 2 0 0 1-1.27 1.27l-5.41 1.8 1.8-5.41a2 2 0 0 1 1.27-1.27z"/>',
        '419' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        '429' => '<path d="M12 14l4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
        '500' => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        '503' => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94z"/>',
    ];

    // Codes without a page of their own arrive through errors::4xx / errors::5xx,
    // so fall back on the status class before falling back on the generic look.
    $classTones = ['4' => ['79 70 229', '129 140 248'], '5' => ['220 38 38', '248 113 113']];
    $classGlyphs = [
        '4' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><path d="M12 17h.01"/>',
        '5' => '<rect width="20" height="8" x="2" y="2" rx="2"/><rect width="20" height="8" x="2" y="14" rx="2"/><path d="M6 6h.01"/><path d="M6 18h.01"/>',
    ];
    $statusClass = substr($code, 0, 1);

    [$toneLight, $toneDark] = $tones[$code] ?? $classTones[$statusClass] ?? ['79 70 229', '129 140 248'];
    $glyph = $glyphs[$code] ?? $classGlyphs[$statusClass] ?? '<circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/>';

    // The appearance cookie is excluded from encryption, so it is readable even
    // when an exception short circuits the middleware that normally shares it.
    $appearance = request()->cookie('appearance');
    $appearance = in_array($appearance, ['light', 'dark', 'system'], true) ? $appearance : 'system';
    $systemPrefersDark = strcasecmp((string) request()->header('Sec-CH-Prefers-Color-Scheme', ''), 'dark') === 0;
    $themeClass = match (true) {
        $appearance === 'light' => 'light',
        $appearance === 'dark' => 'dark',
        $systemPrefersDark => 'dark',
        default => '', // No hint from the client: prefers-color-scheme decides.
    };
@endphp
<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @if ($themeClass !== '') class="{{ $themeClass }}" @endif
    style="--tone-light: {{ $toneLight }}; --tone-dark: {{ $toneDark }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="color-scheme" content="light dark">
        <meta name="theme-color" content="#f6f7fb" media="(prefers-color-scheme: light)">
        <meta name="theme-color" content="#0f1219" media="(prefers-color-scheme: dark)">

        <title>@yield('title') | {{ $institutionName }}</title>

        <link rel="icon" href="{{ asset('favicon.png') }}" sizes="32x32">

        {{-- Everything the page needs ships in this document: no webfonts, no scripts, one 3 KB logo. --}}
        <style>
            :root {
                --tone: var(--tone-light);
                --bg: #f6f7fb;
                --surface: #ffffff;
                --ink: #0f172a;
                --ink-muted: #56637a;
                --line: rgb(15 23 42 / 10%);
                --line-strong: rgb(15 23 42 / 16%);
                --grid: rgb(15 23 42 / 5%);
                --on-tone: #ffffff;
                --shadow: 0 1px 2px rgb(15 23 42 / 5%), 0 28px 60px -32px rgb(15 23 42 / 35%);
                --logo-filter: none;
            }

            html.dark {
                --tone: var(--tone-dark);
                --bg: #0f1219;
                --surface: #171c26;
                --ink: #f8fafc;
                --ink-muted: #9aa5b8;
                --line: rgb(255 255 255 / 10%);
                --line-strong: rgb(255 255 255 / 16%);
                --grid: rgb(255 255 255 / 5%);
                --on-tone: #0b0f19;
                --shadow: 0 1px 2px rgb(0 0 0 / 40%), 0 32px 64px -32px rgb(0 0 0 / 80%);
                --logo-filter: brightness(0) invert(1);
            }

            @media (prefers-color-scheme: dark) {
                html:not(.light) {
                    --tone: var(--tone-dark);
                    --bg: #0f1219;
                    --surface: #171c26;
                    --ink: #f8fafc;
                    --ink-muted: #9aa5b8;
                    --line: rgb(255 255 255 / 10%);
                    --line-strong: rgb(255 255 255 / 16%);
                    --grid: rgb(255 255 255 / 5%);
                    --on-tone: #0b0f19;
                    --shadow: 0 1px 2px rgb(0 0 0 / 40%), 0 32px 64px -32px rgb(0 0 0 / 80%);
                    --logo-filter: brightness(0) invert(1);
                }
            }

            *,
            *::before,
            *::after {
                box-sizing: border-box;
            }

            html {
                background: var(--bg);
            }

            body {
                position: relative;
                min-height: 100vh;
                min-height: 100svh;
                margin: 0;
                color: var(--ink);
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                font-size: 16px;
                line-height: 1.5;
                -webkit-font-smoothing: antialiased;
            }

            /* Two cheap painted layers stand in for the old 240 KB photograph. */
            body::before,
            body::after {
                position: fixed;
                z-index: -1;
                inset: 0;
                content: "";
                pointer-events: none;
            }

            body::before {
                background-image:
                    linear-gradient(var(--grid) 1px, transparent 1px),
                    linear-gradient(90deg, var(--grid) 1px, transparent 1px);
                background-size: 3.5rem 3.5rem;
                -webkit-mask-image: radial-gradient(ellipse 90% 65% at 50% 0%, #000 25%, transparent 78%);
                mask-image: radial-gradient(ellipse 90% 65% at 50% 0%, #000 25%, transparent 78%);
            }

            body::after {
                background:
                    radial-gradient(60rem 38rem at 12% -12%, rgb(var(--tone) / 14%), transparent 62%),
                    radial-gradient(48rem 34rem at 92% 8%, rgb(var(--tone) / 9%), transparent 60%);
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            :focus-visible {
                border-radius: 0.5rem;
                outline: 2px solid rgb(var(--tone));
                outline-offset: 3px;
            }

            .shell {
                display: flex;
                width: min(100% - 2.5rem, 60rem);
                min-height: 100vh;
                min-height: 100svh;
                margin-inline: auto;
                padding-block: 2rem;
                flex-direction: column;
                gap: 2rem;
            }

            .brand {
                display: inline-flex;
                width: fit-content;
                align-items: center;
                gap: 0.8rem;
                color: var(--ink-muted);
                font-size: 0.78rem;
                font-weight: 600;
                letter-spacing: 0.14em;
                text-transform: uppercase;
                transition: color 0.18s ease;
            }

            .brand:hover {
                color: var(--ink);
            }

            .brand img {
                width: 2.5rem;
                height: 2.5rem;
                object-fit: contain;
                filter: var(--logo-filter);
            }

            main {
                display: grid;
                flex: 1;
                align-content: center;
            }

            .card {
                position: relative;
                display: grid;
                overflow: hidden;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: center;
                gap: clamp(1.5rem, 4vw, 4rem);
                padding: clamp(1.75rem, 4.5vw, 3.25rem);
                border: 1px solid var(--line);
                border-radius: 1.5rem;
                background: var(--surface);
                box-shadow: var(--shadow);
                animation: rise 0.5s cubic-bezier(0.2, 0.8, 0.2, 1) both;
            }

            /* Hairline of accent along the top edge. */
            .card::before {
                position: absolute;
                top: 0;
                right: 0;
                left: 0;
                height: 2px;
                background: linear-gradient(90deg, transparent, rgb(var(--tone) / 65%), transparent);
                content: "";
            }

            .status {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.4rem 0.85rem 0.4rem 0.65rem;
                border: 1px solid rgb(var(--tone) / 28%);
                border-radius: 999px;
                background: rgb(var(--tone) / 10%);
                color: rgb(var(--tone));
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
            }

            .status svg {
                width: 1rem;
                height: 1rem;
                flex: none;
            }

            h1 {
                margin: 1.25rem 0 0;
                font-size: clamp(1.85rem, 4.4vw, 2.9rem);
                font-weight: 700;
                letter-spacing: -0.03em;
                line-height: 1.1;
                text-wrap: balance;
            }

            .message {
                max-width: 48ch;
                margin: 0.85rem 0 0;
                color: var(--ink-muted);
                font-size: 1.02rem;
                line-height: 1.65;
                text-wrap: pretty;
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                margin-top: 2rem;
                flex-wrap: wrap;
            }

            .button {
                display: inline-flex;
                min-height: 2.9rem;
                padding-inline: 1.25rem;
                align-items: center;
                justify-content: center;
                border: 1px solid transparent;
                border-radius: 0.75rem;
                background: rgb(var(--tone));
                color: var(--on-tone);
                font-size: 0.94rem;
                font-weight: 600;
                box-shadow: 0 10px 22px -12px rgb(var(--tone) / 85%);
                transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
            }

            .button:hover {
                box-shadow: 0 14px 26px -12px rgb(var(--tone) / 95%);
                transform: translateY(-1px);
            }

            .button:active {
                transform: translateY(0);
            }

            .button-secondary {
                border-color: var(--line-strong);
                background: transparent;
                color: var(--ink);
                box-shadow: none;
            }

            .button-secondary:hover {
                background: rgb(var(--tone) / 8%);
                border-color: rgb(var(--tone) / 35%);
                box-shadow: none;
            }

            /* Oversized status numeral, clipped by the card. */
            .watermark {
                margin-right: -0.08em;
                background: linear-gradient(180deg, rgb(var(--tone) / 26%), rgb(var(--tone) / 5%));
                -webkit-background-clip: text;
                background-clip: text;
                color: rgb(var(--tone) / 14%);
                font-size: clamp(6.5rem, 16vw, 11.5rem);
                font-weight: 800;
                letter-spacing: -0.06em;
                line-height: 0.85;
                user-select: none;
                animation: rise 0.6s 0.08s cubic-bezier(0.2, 0.8, 0.2, 1) both;
            }

            @supports (-webkit-background-clip: text) or (background-clip: text) {
                .watermark {
                    color: transparent;
                }
            }

            .footer {
                color: var(--ink-muted);
                font-size: 0.76rem;
                letter-spacing: 0.04em;
            }

            @keyframes rise {
                from { opacity: 0; transform: translateY(14px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @media (max-width: 48rem) {
                .shell {
                    width: min(100% - 2rem, 34rem);
                    gap: 1.5rem;
                }

                .card {
                    grid-template-columns: minmax(0, 1fr);
                    gap: 0;
                    border-radius: 1.25rem;
                }

                .watermark {
                    order: -1;
                    margin: 0 0 0.75rem;
                    font-size: clamp(4.5rem, 24vw, 7rem);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                *,
                *::before,
                *::after {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="shell">
            <a class="brand" href="{{ url('/') }}" aria-label="{{ __('trans.error_go_home') }}">
                <img src="{{ asset('assets/images/logo-mark.webp') }}" width="75" height="128" alt="" decoding="async">
                <span>{{ $institutionName }}</span>
            </a>

            <main>
                <section class="card" aria-labelledby="error-title">
                    <div>
                        <span class="status">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $glyph !!}</svg>
                            {{ __('trans.error_status', ['code' => $code]) }}
                        </span>

                        <h1 id="error-title">@yield('title')</h1>
                        <p class="message">@yield('message')</p>

                        <nav class="actions" aria-label="{{ __('trans.error_actions') }}">
                            <a class="button" href="@yield('primary_url', $backUrl)">
                                @yield('primary_label', __('trans.error_return_back'))
                            </a>

                            @hasSection('secondary_url')
                                <a class="button button-secondary" href="@yield('secondary_url')">
                                    @yield('secondary_label')
                                </a>
                            @endif
                        </nav>
                    </div>

                    <div class="watermark" aria-hidden="true">{{ $code }}</div>
                </section>
            </main>

            <footer class="footer">
                {{ __('trans.error_footer', ['institution' => $institutionName]) }}
            </footer>
        </div>
    </body>
</html>

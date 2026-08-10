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
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="theme-color" content="#0f172a">

        <title>@yield('title') | {{ $institutionName }}</title>

        <style>
            :root {
                --ink: #ffffff;
                --muted: rgba(255, 255, 255, 0.76);
                --accent: #5f7cff;
                --accent-hover: #7690ff;
                --danger: #dc2626;
                --line: rgba(255, 255, 255, 0.28);
                --shadow: 0 18px 50px rgba(2, 6, 23, 0.28);
            }

            * {
                box-sizing: border-box;
            }

            html {
                min-height: 100%;
                background: #0f172a;
            }

            body {
                min-height: 100vh;
                min-height: 100svh;
                margin: 0;
                color: var(--ink);
                font-family: "Trebuchet MS", "Gill Sans", sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            a {
                color: inherit;
                text-decoration: none;
            }

            .page {
                position: relative;
                isolation: isolate;
                display: grid;
                min-height: 100vh;
                min-height: 100svh;
                overflow: hidden;
            }

            .page::before {
                position: absolute;
                z-index: -3;
                inset: -3%;
                background: url("/assets/images/poly.png") center / cover no-repeat;
                content: "";
                animation: scene-in 1.4s cubic-bezier(.2, .8, .2, 1) both;
            }

            .page::after {
                position: absolute;
                z-index: -2;
                inset: 0;
                background:
                    radial-gradient(circle at 72% 42%, rgba(59, 130, 246, 0.08), transparent 34%),
                    linear-gradient(100deg, rgba(2, 6, 23, 0.94) 0%, rgba(15, 23, 42, 0.86) 47%, rgba(15, 23, 42, 0.45) 100%);
                content: "";
            }

            .shell {
                display: flex;
                width: min(100% - 3rem, 76rem);
                min-height: 100%;
                margin: 0 auto;
                padding: 2rem 0;
                flex-direction: column;
            }

            .brand {
                display: inline-flex;
                width: fit-content;
                align-items: center;
                gap: 0.9rem;
                font-family: Georgia, "Times New Roman", serif;
                font-size: clamp(1.15rem, 2vw, 1.55rem);
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                animation: content-in .7s .05s ease-out both;
            }

            .brand img {
                width: 3.25rem;
                height: 3.25rem;
                object-fit: contain;
                filter: brightness(0) invert(1);
            }

            .content {
                display: grid;
                flex: 1;
                grid-template-columns: minmax(0, 38rem) minmax(9rem, 1fr);
                align-items: center;
                gap: clamp(2rem, 8vw, 8rem);
                padding: 4rem 0;
            }

            .copy {
                animation: content-in .8s .16s cubic-bezier(.2, .8, .2, 1) both;
            }

            .code {
                display: block;
                margin-bottom: 0.35rem;
                color: var(--danger);
                font-size: 0.8rem;
                font-weight: 700;
                letter-spacing: 0.24em;
                text-transform: uppercase;
            }

            h1 {
                max-width: 12ch;
                margin: 0;
                font-family: Georgia, "Times New Roman", serif;
                font-size: clamp(3rem, 8vw, 6.7rem);
                font-weight: 700;
                letter-spacing: -0.055em;
                line-height: 0.94;
                text-wrap: balance;
            }

            .message {
                max-width: 38rem;
                margin: 1.6rem 0 0;
                color: var(--muted);
                font-size: clamp(1rem, 2vw, 1.2rem);
                line-height: 1.7;
                text-wrap: pretty;
            }

            .actions {
                display: flex;
                align-items: center;
                gap: 1rem;
                margin-top: 2.25rem;
                flex-wrap: wrap;
            }

            .button {
                display: inline-flex;
                min-height: 3.15rem;
                padding: 0.8rem 1.4rem;
                align-items: center;
                justify-content: center;
                border: 1px solid transparent;
                border-radius: 0.45rem;
                background: var(--accent);
                box-shadow: var(--shadow);
                font-size: 0.95rem;
                font-weight: 700;
                transition: background-color .2s ease, transform .2s ease;
            }

            .button:hover {
                background: var(--accent-hover);
                transform: translateY(-2px);
            }

            .button:focus-visible {
                outline: 3px solid rgba(255, 255, 255, 0.85);
                outline-offset: 4px;
            }

            .button-secondary {
                border-color: var(--line);
                background: rgba(15, 23, 42, 0.32);
                box-shadow: none;
                backdrop-filter: blur(8px);
            }

            .button-secondary:hover {
                background: rgba(255, 255, 255, 0.12);
            }

            .illustration {
                display: grid;
                place-items: center;
                animation: illustration-in .9s .28s cubic-bezier(.2, .8, .2, 1) both;
            }

            .illustration img {
                width: clamp(7.5rem, 18vw, 12rem);
                height: auto;
                filter: brightness(0) invert(1) opacity(.72) drop-shadow(0 18px 30px rgba(2, 6, 23, 0.28));
                animation: drift 5s 1.2s ease-in-out infinite;
            }

            .footer {
                color: rgba(255, 255, 255, 0.58);
                font-size: 0.78rem;
                letter-spacing: 0.04em;
                animation: content-in .7s .35s ease-out both;
            }

            @keyframes scene-in {
                from { opacity: 0; transform: scale(1.06); }
                to { opacity: 1; transform: scale(1); }
            }

            @keyframes content-in {
                from { opacity: 0; transform: translateY(18px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes illustration-in {
                from { opacity: 0; transform: translateY(24px) scale(.9); }
                to { opacity: 1; transform: translateY(0) scale(1); }
            }

            @keyframes drift {
                0%, 100% { transform: translateY(0) rotate(-1deg); }
                50% { transform: translateY(-10px) rotate(1deg); }
            }

            @media (max-width: 700px) {
                .shell {
                    width: min(100% - 2rem, 38rem);
                    padding: 1.25rem 0;
                }

                .brand img {
                    width: 2.75rem;
                    height: 2.75rem;
                }

                .content {
                    grid-template-columns: 1fr;
                    gap: 1.25rem;
                    padding: 3rem 0;
                }

                .illustration {
                    grid-row: 1;
                    justify-content: start;
                }

                .illustration img {
                    width: 5.75rem;
                }

                .copy {
                    grid-row: 2;
                }

                h1 {
                    font-size: clamp(2.75rem, 15vw, 4.5rem);
                }
            }

            @media (prefers-reduced-motion: reduce) {
                *, *::before, *::after {
                    scroll-behavior: auto !important;
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }
        </style>
    </head>
    <body>
        <main class="page">
            <div class="shell">
                <a class="brand" href="{{ url('/') }}" aria-label="{{ __('trans.error_go_home') }}">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="">
                    <span>{{ $institutionName }}</span>
                </a>

                <div class="content">
                    <section class="copy" aria-labelledby="error-title">
                        <span class="code">{{ __('trans.error_status', ['code' => trim($__env->yieldContent('code'))]) }}</span>
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
                    </section>

                    <div class="illustration" aria-hidden="true">
                        <img src="{{ asset('assets/images/sad.svg') }}" alt="">
                    </div>
                </div>

                <footer class="footer">
                    {{ __('trans.error_footer', ['institution' => $institutionName]) }}
                </footer>
            </div>
        </main>
    </body>
</html>

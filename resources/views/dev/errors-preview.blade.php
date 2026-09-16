@php
    $appearance = request()->cookie('appearance');
    $appearance = in_array($appearance, ['light', 'dark', 'system'], true) ? $appearance : 'system';
@endphp
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="color-scheme" content="light dark">
        <title>Error page preview</title>
        <style>
            :root {
                --bg: #f6f7fb;
                --panel: #ffffff;
                --ink: #0f172a;
                --muted: #56637a;
                --line: rgb(15 23 42 / 12%);
                --tone: #4f46e5;
                color-scheme: light dark;
            }

            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #0f1219;
                    --panel: #171c26;
                    --ink: #f8fafc;
                    --muted: #9aa5b8;
                    --line: rgb(255 255 255 / 14%);
                    --tone: #818cf8;
                }
            }

            * { box-sizing: border-box; }

            body {
                margin: 0;
                padding: 2rem clamp(1rem, 4vw, 3rem) 4rem;
                background: var(--bg);
                color: var(--ink);
                font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            }

            header { margin-bottom: 2rem; }

            h1 { margin: 0 0 .35rem; font-size: 1.5rem; letter-spacing: -.02em; }

            .lede { margin: 0; color: var(--muted); font-size: .9rem; max-width: 70ch; line-height: 1.6; }

            .toolbar {
                display: flex;
                align-items: center;
                gap: .5rem;
                margin-top: 1.5rem;
                flex-wrap: wrap;
            }

            .toolbar-label {
                margin-right: .25rem;
                color: var(--muted);
                font-size: .72rem;
                font-weight: 700;
                letter-spacing: .1em;
                text-transform: uppercase;
            }

            .chip {
                padding: .45rem .85rem;
                border: 1px solid var(--line);
                border-radius: 999px;
                background: var(--panel);
                color: var(--ink);
                font: inherit;
                font-size: .82rem;
                font-weight: 600;
                text-decoration: none;
                cursor: pointer;
            }

            .chip[aria-pressed="true"], .chip[aria-current="true"] {
                border-color: var(--tone);
                background: var(--tone);
                color: #fff;
            }

            @media (prefers-color-scheme: dark) {
                .chip[aria-pressed="true"], .chip[aria-current="true"] { color: #0b0f19; }
            }

            h2 {
                margin: 2.5rem 0 1rem;
                font-size: .75rem;
                font-weight: 700;
                letter-spacing: .12em;
                text-transform: uppercase;
                color: var(--muted);
            }

            .grid {
                display: grid;
                gap: 1.25rem;
                grid-template-columns: repeat(auto-fill, minmax(var(--tile), 1fr));
            }

            .tile {
                overflow: hidden;
                border: 1px solid var(--line);
                border-radius: .9rem;
                background: var(--panel);
            }

            .tile-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: .5rem;
                padding: .6rem .85rem;
                border-bottom: 1px solid var(--line);
                font-size: .82rem;
                font-weight: 700;
            }

            .tile-head a { color: var(--tone); font-size: .74rem; font-weight: 600; text-decoration: none; }
            .tile-head a:hover { text-decoration: underline; }

            .viewport { position: relative; overflow: hidden; height: var(--tile-height); }

            .viewport iframe {
                position: absolute;
                top: 0;
                left: 0;
                width: var(--frame-width);
                height: var(--frame-height);
                border: 0;
                transform: scale(var(--scale));
                transform-origin: top left;
            }

            /* Desktop framing: render 1400x860 and scale it into the tile. */
            body { --tile: 26rem; --frame-width: 1400px; --frame-height: 720px; --scale: .3; --tile-height: 216px; }

            /* Phone framing: render 420x860 at a larger scale. */
            body.is-mobile { --tile: 16rem; --frame-width: 420px; --frame-height: 860px; --scale: .55; --tile-height: 473px; }
        </style>
    </head>
    <body>
        <header>
            <h1>Error page preview</h1>
            <p class="lede">
                Every page under <code>resources/views/errors</code>, rendered live. Tiles respond 200 so they always
                draw; <strong>Open</strong> loads the page on its own, and <strong>Open real</strong> throws the actual
                exception so the true status code and any app-level handlers (such as the student 403 redirect) apply.
            </p>

            <div class="toolbar">
                <span class="toolbar-label">Theme</span>
                @foreach (['light', 'dark', 'system'] as $option)
                    <a class="chip"
                       href="{{ route('dev.errors.theme', $option) }}"
                       aria-current="{{ $appearance === $option ? 'true' : 'false' }}">{{ ucfirst($option) }}</a>
                @endforeach

                <span class="toolbar-label" style="margin-left:1rem">Width</span>
                <button class="chip" type="button" data-width="desktop" aria-pressed="true">Desktop</button>
                <button class="chip" type="button" data-width="mobile" aria-pressed="false">Phone</button>
            </div>
        </header>

        @foreach ($groups as $heading => $codes)
            <h2>{{ $heading }}</h2>
            <div class="grid">
                @foreach ($codes as $code)
                    <div class="tile">
                        <div class="tile-head">
                            <span>{{ $code }}</span>
                            <span>
                                <a href="{{ route('dev.errors.show', $code) }}" target="_blank" rel="noopener">Open</a>
                                &middot;
                                <a href="{{ route('dev.errors.show', $code) }}?real=1" target="_blank" rel="noopener">Open real</a>
                            </span>
                        </div>
                        <div class="viewport">
                            <iframe src="{{ route('dev.errors.show', $code) }}"
                                    title="Error {{ $code }}"
                                    loading="lazy"></iframe>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach

        <script>
            document.querySelectorAll('[data-width]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var mobile = button.dataset.width === 'mobile';
                    document.body.classList.toggle('is-mobile', mobile);
                    document.querySelectorAll('[data-width]').forEach(function (other) {
                        other.setAttribute('aria-pressed', String(other === button));
                    });
                });
            });
        </script>
    </body>
</html>

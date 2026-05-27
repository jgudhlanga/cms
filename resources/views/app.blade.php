<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class([
        'dark' => ($appearance ?? 'system') === 'dark' || (($appearance ?? 'system') === 'system' && ($systemPrefersDark ?? false)),
        'light' => ($appearance ?? 'system') === 'light',
    ])
    data-appearance="{{ $appearance ?? 'system' }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <script>
            window.__serverPrefersDark = @json($systemPrefersDark ?? false);
        </script>
        <script>
            (function () {
                function clientPrefersDark() {
                    return (
                        window.matchMedia('(prefers-color-scheme: dark)').matches ||
                        window.matchMedia('(-webkit-prefers-color-scheme: dark)').matches
                    );
                }

                function prefersDark() {
                    return clientPrefersDark() || window.__serverPrefersDark === true;
                }

                var appearance = 'system';

                try {
                    var stored = localStorage.getItem('appearance');
                    if (stored === 'dark' || stored === 'light' || stored === 'system') {
                        appearance = stored;
                    } else {
                        var match = document.cookie.match(/(?:^|;\s*)appearance=([^;]*)/);
                        if (match) {
                            var decoded = decodeURIComponent(match[1]);
                            if (decoded === 'dark' || decoded === 'light' || decoded === 'system') {
                                appearance = decoded;
                            }
                        }
                    }
                } catch (_) {}

                var isDark =
                    appearance === 'dark' || (appearance === 'system' && prefersDark());
                var root = document.documentElement;

                root.classList.remove('light', 'dark');
                root.dataset.appearance = appearance;
                root.dataset.resolvedTheme = isDark ? 'dark' : 'light';

                if (appearance === 'light') {
                    root.classList.add('light');
                    root.style.colorScheme = 'light';
                } else {
                    if (isDark) {
                        root.classList.add('dark');
                    }
                    root.style.colorScheme = isDark ? 'dark' : 'light';
                }

                try {
                    localStorage.setItem('appearance', appearance);
                } catch (_) {}
            })();
        </script>
        <link rel="icon" href="{{ asset('favicon.png') }}" sizes="32x32">
        <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}" type="image/x-icon">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Caveat:wght@500&display=swap" rel="stylesheet">

        @routes
        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

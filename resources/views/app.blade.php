<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{--
            Tema terang/gelap dipasang SEBELUM CSS & React dimuat.
            Kalau ini ditaruh di React, browser sempat menggambar tema terang
            dulu sepersekian detik baru berubah jadi gelap (efek "kedip putih").
            Script kecil yang blocking di head bikin temanya sudah benar dari
            frame pertama. Kuncinya harus sama dengan yang dipakai komponen
            ThemeToggle.
        --}}
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('visinema-theme');
                    if (stored === 'light' || stored === 'dark') {
                        document.documentElement.setAttribute('data-theme', stored);
                    }
                } catch (e) {
                    /* localStorage diblokir (mode private, dsb) - biarin ikut tema OS */
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="bg-canvas font-sans text-ink antialiased">
        @inertia
    </body>
</html>

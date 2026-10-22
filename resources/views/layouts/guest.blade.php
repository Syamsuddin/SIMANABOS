<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — SIMANABOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="grid min-h-screen wide:grid-cols-2">
        <div class="hidden flex-col justify-between bg-sidebar-bg p-12 text-sidebar-ink wide:flex">
            <div class="flex items-center gap-3">
                <img src="{{ asset('img/logo-hss.png') }}" alt="Lambang Kabupaten Hulu Sungai Selatan" class="size-12 object-contain" />
                <div class="grid leading-snug">
                    <strong class="text-body font-bold">SIMANABOS</strong>
                    <span class="text-caption font-normal text-sidebar-ink-muted">Disdikbud Kab. Hulu Sungai Selatan</span>
                </div>
            </div>
            <div class="max-w-md">
                <p class="text-overline uppercase text-focus">Sistem Informasi Manajemen BOS</p>
                <p class="mt-2 text-display">Pengawasan dana BOS SD dan SMP satu kabupaten.</p>
                <p class="mt-3 text-body text-sidebar-ink-muted">Gunakan akun yang diberikan oleh admin dinas.</p>
            </div>
            <span class="text-caption font-normal text-sidebar-ink-muted">Dinas Pendidikan dan Kebudayaan Kab. Hulu Sungai Selatan</span>
        </div>

        <main class="grid place-items-center p-4 nav:p-8">
            <div class="grid w-full max-w-sm gap-6">
                <div class="flex items-center gap-3 wide:hidden">
                    <img src="{{ asset('img/logo-hss.png') }}" alt="Lambang Kabupaten Hulu Sungai Selatan" class="size-control object-contain" />
                    <div class="grid leading-snug">
                        <strong class="text-body font-bold text-ink">SIMANABOS</strong>
                        <span class="text-caption font-normal text-ink-muted">Disdikbud Kab. Hulu Sungai Selatan</span>
                    </div>
                </div>

                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>

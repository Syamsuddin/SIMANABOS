@php
    $user = auth()->user();
    $isSchoolUser = $user->hasAnyRole([\App\Enums\RoleName::OperatorSekolah->value, \App\Enums\RoleName::KepalaSekolah->value]);
    $menu = [
        [
            'title' => $isSchoolUser ? 'Sekolah' : 'Utama',
            'items' => [
                ['label' => 'Beranda', 'icon' => 'home', 'route' => 'home'],
                ...(! $isSchoolUser ? [['label' => 'Dasbor kabupaten', 'icon' => 'dashboard', 'route' => 'dashboard.*'], ['label' => 'Daftar perhatian', 'icon' => 'alert', 'route' => 'attention.*']] : []),
            ],
        ],
        ...($user->hasRole(\App\Enums\RoleName::AdminDinas->value) ? [[
            'title' => 'Pengaturan',
            'items' => [
                ['label' => 'Tahun anggaran', 'icon' => 'calendar', 'route' => 'admin.fiscal-years.*'],
                ['label' => 'Pagu alokasi', 'icon' => 'wallet', 'route' => 'admin.allocations.*'],
                ['label' => 'Sekolah', 'icon' => 'building', 'route' => 'admin.schools.*'],
                ['label' => 'Akun pengguna', 'icon' => 'users', 'route' => 'admin.users.*'],
                ['label' => 'Kecamatan', 'icon' => 'map-pin', 'route' => 'admin.districts.*'],
                ['label' => 'Sumber dana', 'icon' => 'wallet', 'route' => 'admin.fund-sources.*'],
                ['label' => 'Pengaturan aplikasi', 'icon' => 'settings', 'route' => 'admin.settings.*'],
            ],
        ]] : []),
        ...($user->hasRole(\App\Enums\RoleName::PemeriksaDinas->value) ? [[
            'title' => 'Pemeriksaan',
            'items' => [['label' => 'Antrean pemeriksaan', 'icon' => 'file-text', 'route' => 'review.reports.*'], ['label' => 'Verifikasi saldo awal', 'icon' => 'check-circle', 'route' => 'examiner.opening-balances.*']],
        ]] : []),
        ...($isSchoolUser ? [[
            'title' => 'Data sekolah',
            'items' => [
                ['label' => 'Profil sekolah', 'icon' => 'building', 'route' => 'school.profile.show'],
                ['label' => 'RKAS', 'icon' => 'wallet', 'route' => 'school.budget.*'],
                ['label' => 'Saldo awal', 'icon' => 'wallet', 'route' => 'school.opening-balance.*'],
                ['label' => 'Laporan bulanan', 'icon' => 'file-text', 'route' => 'school.reports.*'],
            ],
        ]] : []),
        [
            'title' => 'Akun',
            'items' => [['label' => 'Ganti sandi', 'icon' => 'shield', 'route' => 'password.change']],
        ],
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — SIMANABOS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <x-pm.app-shell :groups="$menu" :user="$user">
        @if (session('success'))
            <x-pm.toast tone="success">{{ session('success') }}</x-pm.toast>
        @endif

        @if (session('warning'))
            <x-pm.toast tone="warning">{{ session('warning') }}</x-pm.toast>
        @endif

        @if (session('error'))
            <x-pm.toast tone="danger">{{ session('error') }}</x-pm.toast>
        @endif

        @yield('content')
    </x-pm.app-shell>
</body>
</html>

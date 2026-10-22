@props(['name', 'size' => 18, 'title' => null])
@php
    // Ikon garis Pamong: grid 24px, stroke 1.75, tanpa isian (docs/26).
    $icons = [
    'home' => ['M3 10.5 12 3l9 7.5', 'M5 9.5V21h14V9.5', 'M10 21v-6h4v6'],
    'dashboard' => ['M4 4h7v7H4z', 'M13 4h7v4h-7z', 'M13 10h7v10h-7z', 'M4 13h7v7H4z'],
    'file-text' => ['M14 3H6v18h12V7z', 'M14 3v4h4', 'M9 12h6', 'M9 16h6'],
    'inbox' => ['M3 13l3-8h12l3 8', 'M3 13v6h18v-6', 'M3 13h5l1.5 3h5L16 13h5'],
    'users' => ['M5.5 8a3.5 3.5 0 1 0 7 0a3.5 3.5 0 1 0 -7 0', 'M2.5 20c.6-3.6 3.2-5.5 6.5-5.5s5.9 1.9 6.5 5.5', 'M16 4.6a3.5 3.5 0 0 1 0 6.8', 'M18 14.8c2 .7 3.2 2.5 3.5 5.2'],
    'user' => ['M8 8a4 4 0 1 0 8 0a4 4 0 1 0 -8 0', 'M4 21c.8-4 4-6 8-6s7.2 2 8 6'],
    'building' => ['M3 21h18', 'M12 3 3.5 8h17z', 'M4 10h16', 'M6 10v8', 'M10 10v8', 'M14 10v8', 'M18 10v8', 'M4 18h16'],
    'chart' => ['M4 4v16h16', 'M8 16v-5', 'M12 16V8', 'M16 16v-3', 'M20 16V6'],
    'wallet' => ['M3 7h15a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z', 'M3 7l12-3v3', 'M15.5 13.5h1'],
    'settings' => ['M4 6h9', 'M19 6h1', 'M4 12h3', 'M13 12h7', 'M4 18h11', 'M21 18h-1', 'M13.5 6a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0', 'M7.5 12a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0', 'M15 18a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0'],
    'bell' => ['M6 16v-5a6 6 0 0 1 12 0v5l1.5 2h-15z', 'M10 20.5a2 2 0 0 0 4 0'],
    'search' => ['M4.5 11a6.5 6.5 0 1 0 13 0a6.5 6.5 0 1 0 -13 0', 'M16 16l4.5 4.5'],
    'chevron-down' => ['M6 9l6 6 6-6'],
    'chevron-up' => ['M6 15l6-6 6 6'],
    'chevron-right' => ['M9 6l6 6-6 6'],
    'chevron-left' => ['M15 6l-6 6 6 6'],
    'plus' => ['M12 5v14', 'M5 12h14'],
    'download' => ['M12 4v11', 'M7 10l5 5 5-5', 'M4 20h16'],
    'upload' => ['M12 15V4', 'M7 9l5-5 5 5', 'M4 20h16'],
    'filter' => ['M4 5h16l-6 7.5V19l-4 1.5v-8z'],
    'check' => ['M5 12.5l4.5 4.5L19 7.5'],
    'x' => ['M6 6l12 12', 'M18 6L6 18'],
    'alert' => ['M12 3.5 2.5 20h19z', 'M12 10v4.5', 'M12 17.2v.3'],
    'info' => ['M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0', 'M12 11v6', 'M12 7.6v.3'],
    'check-circle' => ['M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0', 'M8 12.5l2.8 2.8 5.2-5.6'],
    'x-circle' => ['M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0', 'M9 9l6 6', 'M15 9l-6 6'],
    'more' => ['M4.6 12a0.9 0.9 0 1 0 1.8 0a0.9 0.9 0 1 0 -1.8 0', 'M11.1 12a0.9 0.9 0 1 0 1.8 0a0.9 0.9 0 1 0 -1.8 0', 'M17.6 12a0.9 0.9 0 1 0 1.8 0a0.9 0.9 0 1 0 -1.8 0'],
    'logout' => ['M9 4H5v16h4', 'M14 8l4 4-4 4', 'M18 12H9'],
    'calendar' => ['M4 6h16v14H4z', 'M4 10h16', 'M8 3v4', 'M16 3v4'],
    'menu' => ['M4 7h16', 'M4 12h16', 'M4 17h16'],
    'eye' => ['M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z', 'M9.2 12a2.8 2.8 0 1 0 5.6 0a2.8 2.8 0 1 0 -5.6 0'],
    'edit' => ['M4 20h4L19 9l-4-4L4 16z', 'M13.5 6.5l4 4'],
    'trash' => ['M4 7h16', 'M9 7V4h6v3', 'M6 7l1 13h10l1-13', 'M10 11v6', 'M14 11v6'],
    'clock' => ['M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0', 'M12 7v5l3.5 2'],
    'shield' => ['M12 3l8 3v6c0 4.5-3.4 8-8 9-4.6-1-8-4.5-8-9V6z', 'M8.5 12l2.5 2.5 4.5-5'],
    'trend-up' => ['M3 17l6-6 4 4 8-8', 'M15 7h6v6'],
    'trend-down' => ['M3 7l6 6 4-4 8 8', 'M15 17h6v-6'],
    'mail' => ['M3 5h18v14H3z', 'M3 6l9 7 9-7'],
    'send' => ['M21 3L10 14', 'M21 3l-7 18-4-7-7-4z'],
    'help' => ['M3 12a9 9 0 1 0 18 0a9 9 0 1 0 -18 0', 'M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5v.7', 'M12 17.2v.3'],
    'folder' => ['M3 6h6l2 2h10v11H3z'],
    'paperclip' => ['M20 11.5l-8.2 8.2a5 5 0 0 1-7.1-7.1l8.5-8.5a3.3 3.3 0 0 1 4.7 4.7l-8.5 8.5a1.7 1.7 0 0 1-2.4-2.4l7.8-7.8'],
    'map-pin' => ['M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z', 'M9.5 9.5a2.5 2.5 0 1 0 5 0a2.5 2.5 0 1 0 -5 0'],
    'refresh' => ['M20 11a8 8 0 0 0-14.6-4.5', 'M4 4v3.5h3.5', 'M4 13a8 8 0 0 0 14.6 4.5', 'M20 20v-3.5h-3.5'],
    ];

    throw_unless(isset($icons[$name]), \InvalidArgumentException::class, "Ikon Pamong tidak dikenal: {$name}");
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="{{ $size }}" height="{{ $size }}" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->class(['shrink-0']) }} @if ($title) role="img" aria-label="{{ $title }}" @else aria-hidden="true" @endif>
    @if ($title)<title>{{ $title }}</title>@endif
    @foreach ($icons[$name] as $path)
        <path d="{{ $path }}" />
    @endforeach
</svg>

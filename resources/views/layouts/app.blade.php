<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8f3">
    <title>@hasSection('title')@yield('title')@else @yield('judul', 'Beranda') | Green Plant @endif</title>
    <meta name="description" content="Temukan tanaman pilihan dan layanan perawatan tanaman dari Green Plant.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('component.navbar')

    <main>
        @if (session('status'))
            <div class="page-container" role="status">
                <p class="my-4 rounded-lg border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ session('status') }}</p>
            </div>
        @endif
        @if (session('error'))
            <div class="page-container" role="alert">
                <p class="my-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @hasSection('content')
            @yield('content')
        @else
            @yield('isi')
        @endif
    </main>

    @include('component.footer')
</body>
</html>

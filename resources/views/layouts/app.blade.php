<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f7f8f3">
    <title>@yield('title', 'Green Plant')</title>
    <meta name="description" content="Temukan tanaman pilihan dan layanan perawatan tanaman dari Green Plant.">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    @include('component.navbar')

    <main>
        @yield('content')
    </main>

    @include('component.footer')
</body>
</html>

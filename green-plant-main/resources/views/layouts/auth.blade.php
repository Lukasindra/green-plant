<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') | Green Plant</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-brand-50 font-sans text-gray-800 antialiased">
    <div class="mx-auto flex min-h-screen max-w-5xl items-center p-4">
        <div class="grid w-full overflow-hidden rounded-2xl bg-white shadow-lg md:grid-cols-2">

            {{-- Panel kiri (dekoratif) --}}
            <div class="relative hidden flex-col justify-center bg-gradient-to-br from-brand-100 via-brand-50 to-white p-10 md:flex">
                <x-logo-icon class="absolute bottom-6 right-6 h-16 w-16 text-brand-500/30" />
                <h1 class="text-5xl font-bold leading-tight text-brand-800">Green<br>Plant</h1>
                <p class="mt-3 max-w-[12rem] text-sm text-gray-600">Solusi hijau untuk taman impian Anda.</p>
            </div>

            {{-- Panel kanan (form) --}}
            <div class="p-8 sm:p-10">
                @yield('isi')
            </div>
        </div>
    </div>
</body>
</html>

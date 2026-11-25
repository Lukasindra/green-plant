<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul', 'Invoice') | Green Plant</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .tanpa-cetak { display: none !important; }
            body { background: #fff !important; }
            .kertas { box-shadow: none !important; border: 0 !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 font-sans text-gray-800 antialiased">
    <main class="mx-auto max-w-3xl px-4 py-8">
        <div class="tanpa-cetak mb-4 flex items-center justify-between gap-2">
            <a href="{{ url()->previous() }}" class="text-sm text-brand-700 hover:underline">&larr; Kembali</a>
            <button type="button" onclick="window.print()"
                class="rounded-lg bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800">
                Cetak / Simpan PDF
            </button>
        </div>

        <div class="kertas rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            @yield('isi')
        </div>
    </main>
</body>
</html>

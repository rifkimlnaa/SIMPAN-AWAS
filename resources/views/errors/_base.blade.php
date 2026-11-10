<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | {{ config('arsip.nama') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
</head>
<body class="flex h-full items-center justify-center bg-slate-50 px-6 text-slate-700" style="font-family:'Plus Jakarta Sans',system-ui,sans-serif">
    <div class="max-w-md text-center">
        <p class="text-5xl font-semibold text-slate-300">{{ $code }}</p>
        <h1 class="mt-4 text-xl font-semibold text-slate-900">{{ $title }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ $message }}</p>
        <a href="{{ url('/') }}" class="mt-6 inline-flex rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900">Kembali ke beranda</a>
    </div>
</body>
</html>

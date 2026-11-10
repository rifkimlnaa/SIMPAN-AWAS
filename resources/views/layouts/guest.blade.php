<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    @include('partials.head')
    <title>@yield('title', 'Masuk') | {{ config('arsip.nama') }}</title>
</head>
<body class="h-full bg-slate-50 font-sans text-slate-700 antialiased">
    @yield('content')
    @include('partials.scripts')
</body>
</html>

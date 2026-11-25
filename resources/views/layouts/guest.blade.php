<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>@yield('title', 'Masuk') | {{ config('arsip.nama') }}</title>
</head>
<body class="tb-body">
    @yield('content')
    @include('partials.scripts')
</body>
</html>

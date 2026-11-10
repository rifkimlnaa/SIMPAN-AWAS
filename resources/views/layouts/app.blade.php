<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    @include('partials.head')
    <title>@yield('title', 'Dashboard') | {{ config('arsip.nama') }}</title>
</head>
<body class="h-full bg-slate-50 font-sans text-slate-700 antialiased"
      x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false" :class="{ 'no-scroll': sidebar }">

    <div x-show="sidebar" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-black/50 lg:hidden" @click="sidebar = false"></div>

    @include('partials.sidebar')

    <div class="lg:pl-64">
        @include('partials.topbar')

        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
            @if ($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                    <i data-lucide="triangle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                    <span>Periksa kembali isian yang ditandai merah.</span>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-6 pb-6 text-center text-xs text-slate-400">
            {{ config('arsip.nama') }} &middot; Bawaslu Kabupaten Banyumas &middot; {{ date('Y') }}
        </footer>
    </div>

    @include('partials.scripts')
    @include('partials.scripts-app')
    @stack('scripts')
</body>
</html>

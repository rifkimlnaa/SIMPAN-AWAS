<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    @include('partials.head')

    <title>@yield('title', 'Dashboard') | {{ config('arsip.nama') }}</title>
</head>
<body class="tb-body">
    <div class="tb-page">
        @include('partials.sidebar')

        <div class="tb-wrapper">
            <div class="tb-page-header">
                <div class="container-xl">
                    <div>
                        @hasSection('pretitle')
                            <p class="tb-pretitle">@yield('pretitle')</p>
                        @endif
                        <h1 class="tb-page-title">@yield('title', 'Dashboard')</h1>
                    </div>
                    @hasSection('actions')
                        <div class="tb-page-actions">@yield('actions')</div>
                    @endif
                </div>
            </div>

            <main class="tb-page-body">
                <div class="container-xl">
                    @if ($errors->any())
                        <div class="mb-4 flex items-start gap-3 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                            <i class="ti ti-alert-triangle mt-0.5"></i>
                            <span>Periksa kembali isian yang ditandai merah.</span>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>

            <footer class="tb-footer">
                <div class="container-xl">
                    <span>{{ config('arsip.nama') }} &middot; Bawaslu Kabupaten Banyumas</span>
                    <span>&copy; {{ date('Y') }}</span>
                </div>
            </footer>
        </div>
    </div>

    @include('partials.scripts')
    @include('partials.scripts-app')
    @include('partials.backup-auto')
    <script src="{{ asset('js/tabler-nav.js') }}?v={{ @filemtime(public_path('js/tabler-nav.js')) }}"></script>
    @stack('scripts')

</body>
</html>

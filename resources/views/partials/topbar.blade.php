<header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200 bg-surface/90 px-4 backdrop-blur sm:px-6">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="btn-icon lg:hidden" @click="sidebar = true" aria-label="Buka menu">
            <i data-lucide="menu" class="h-4 w-4"></i>
        </button>
        <h1 class="truncate text-base font-semibold text-slate-900 sm:text-lg">@yield('title', 'Dashboard')</h1>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('profil.edit') }}" class="flex items-center gap-3" title="Profil saya">
            <span class="hidden text-right leading-tight sm:block">
                <span class="block text-sm font-medium text-slate-900">{{ auth()->user()->name }}</span>
                <span class="block text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</span>
            </span>
            @include('partials.avatar', ['user' => auth()->user(), 'size' => 'sm'])
        </a>
    </div>
</header>

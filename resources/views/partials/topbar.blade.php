<header class="app-topbar gs-topbar sticky top-0 z-20 flex h-[4.5rem] items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-5 backdrop-blur sm:px-8">
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" class="btn-icon lg:hidden" @click="sidebar = true" aria-label="Buka menu">
            <i data-lucide="menu" class="h-4 w-4"></i>
        </button>
        <h1 class="truncate text-[1.0625rem] font-bold tracking-[-0.02em] text-slate-900 sm:text-xl">@yield('title', 'Dashboard')</h1>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('profil.edit') }}" class="gs-topbar-user flex items-center gap-3" title="Profil saya">
            <span class="hidden text-right leading-tight sm:block">
                <span class="block text-sm font-medium text-slate-900">{{ auth()->user()->name }}</span>
                <span class="block text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</span>
            </span>
            @include('partials.avatar', ['user' => auth()->user(), 'size' => 'sm'])
        </a>
    </div>
</header>

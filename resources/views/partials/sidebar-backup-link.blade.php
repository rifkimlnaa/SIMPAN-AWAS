@php($backupMenuAktif = request()->routeIs('backup.*'))
<a href="{{ route('backup.index') }}"
   class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-[15px] font-medium transition {{ $backupMenuAktif ? 'bg-white/15 text-white shadow-sm' : 'text-brand-100 hover:bg-white/10 hover:text-white' }}"
   title="Backup & Pemulihan Data">
    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $backupMenuAktif ? 'bg-white/20' : 'bg-white/5 group-hover:bg-white/10' }}">
        <i data-lucide="database" class="h-[18px] w-[18px]"></i>
    </span>
    <span>Backup &amp; Pemulihan</span>
</a>

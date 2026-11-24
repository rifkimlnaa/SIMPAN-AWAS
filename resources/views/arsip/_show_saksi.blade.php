<section class="card p-6">
    <h2 class="text-base font-semibold text-slate-900">Saksi-saksi</h2>
    @if (empty($arsip->saksi))
        <p class="mt-3 text-sm text-slate-500">Tidak ada saksi yang dicatat.</p>
    @else
        <ul class="mt-3 divide-y divide-slate-100">
            @foreach ($arsip->saksi as $i => $s)
                <li class="py-3 text-sm">
                    <p class="font-medium text-slate-900">{{ $i + 1 }}. {{ $s['nama'] ?? '—' }}</p>
                    <p class="text-slate-500">
                        {{ $s['alamat'] ?? 'Alamat tidak dicatat' }}@if (! empty($s['telp'])), {{ $s['telp'] }}@endif
                    </p>
                </li>
            @endforeach
        </ul>
    @endif
</section>

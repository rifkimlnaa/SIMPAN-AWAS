<section class="card p-6">
    <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
    @if (empty($arsip->dokumen))
        <p class="mt-3 text-sm text-slate-500">Belum ada data.</p>
    @else
        <ol class="mt-3 divide-y divide-slate-100">
            @foreach ($arsip->dokumen as $i => $r)
                <li class="py-3 text-sm">
                    <p class="font-medium text-slate-900">
                        @if (! empty($barisDef['nomor_awalan']))<span class="text-slate-500">{{ $barisDef['nomor_awalan'].($i + 1) }}</span>@else{{ $i + 1 }}.@endif
                    </p>
                    <dl class="mt-1 grid gap-x-6 gap-y-2 sm:grid-cols-2">
                        @foreach ($barisDef['kolom'] as $kunci => $k)
                            @php($isi = $r[$kunci] ?? null)
                            <div class="{{ (($k['type'] ?? 'text') === 'textarea' || ($k['wide'] ?? false)) ? 'sm:col-span-2' : '' }}">
                                <dt class="text-xs text-slate-500">{{ $k['label'] }}</dt>
                                <dd class="text-slate-900">@if (filled($isi)){!! nl2br(e($isi)) !!}@else<span class="text-slate-400">&mdash;</span>@endif</dd>
                            </div>
                        @endforeach
                    </dl>
                </li>
            @endforeach
        </ol>
    @endif
</section>

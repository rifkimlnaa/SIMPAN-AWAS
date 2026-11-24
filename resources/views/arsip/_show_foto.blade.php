@if ($arsip->fotos->isNotEmpty())
    <section class="card p-6">
        <h2 class="text-base font-semibold text-slate-900">{{ $judul }}</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach ($arsip->fotos as $f)
                <a href="{{ route('arsip.foto', [$arsip, $f]) }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-slate-200">
                    <img src="{{ route('arsip.foto', [$arsip, $f]) }}" alt="Foto {{ $loop->iteration }}" class="h-32 w-full object-cover" loading="lazy">
                </a>
            @endforeach
        </div>
    </section>
@endif

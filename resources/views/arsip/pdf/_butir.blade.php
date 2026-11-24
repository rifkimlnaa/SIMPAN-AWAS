{{-- Isi sel tabel: baris pertama = judul, baris berikutnya = butir a, b, c (rapi dengan tabel). --}}
@php($p = \App\Support\Formulir::pecah($teks ?? '', $judulPertama ?? true))
@if ($p['judul'] !== null){{ $p['judul'] }}@endif
@if ($p['butir'])
    <table class="butir">
        @foreach ($p['butir'] as $i => $b)
            <tr><td class="h">{{ \App\Support\Formulir::huruf($i) }}.</td><td>{{ $b }}</td></tr>
        @endforeach
    </table>
@endif

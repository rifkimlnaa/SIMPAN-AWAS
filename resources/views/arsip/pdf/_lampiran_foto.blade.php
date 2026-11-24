{{-- Lampiran bukti foto: halaman baru di akhir formulir (PDF dan Word), foto besar di tengah. --}}
@if (! empty($fotos))
    <div class="lampiran-foto" style="page-break-before: always; break-before: page;">
        <div class="judul-lampiran" style="page-break-after: avoid;">LAMPIRAN BUKTI</div>

        @foreach ($fotos as $i => $f)
            <table class="foto-blok" style="width:100%; margin-top:16pt; page-break-inside: avoid;">
                <tr><td style="text-align:center;"><img src="{{ $f['src'] }}" width="{{ $f['w'] }}" height="{{ $f['h'] }}" alt="Bukti {{ $i + 1 }}"></td></tr>
            </table>
        @endforeach
    </div>
@endif

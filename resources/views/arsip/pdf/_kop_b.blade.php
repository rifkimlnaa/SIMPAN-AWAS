{{-- Kop formulir Model B: logo di kiri, kotak kode formulir di kanan. --}}
<table class="kop">
    <tr>
        <td class="logo">@if ($logo)<img src="{{ $logo }}" alt="Bawaslu" width="220">@endif</td>
        <td class="model"><span>{{ $def['kode'] }}</span></td>
    </tr>
</table>

{{-- Skrip inti: dipakai di halaman login maupun halaman aplikasi --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<script>
    // Warna pop up mengikuti tema aktif.
    function swalTema() {
        return {}; // tampilan pop up diatur di tabler.css
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if (session('success'))
            Swal.fire(Object.assign({ toast: true, position: 'top-end', icon: 'success', text: @json(session('success')), showConfirmButton: false, timer: 3500, timerProgressBar: true }, swalTema()));
        @endif
        @if (session('error'))
            Swal.fire(Object.assign({ icon: 'error', title: 'Gagal', text: @json(session('error')), confirmButtonText: 'Tutup' }, swalTema()));
        @endif
    });

    // Konfirmasi hapus (pop up) untuk semua form ber-class "js-confirm-delete".
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.classList || !form.classList.contains('js-confirm-delete')) return;
        e.preventDefault();
        var message = form.dataset.message || 'Data yang dihapus tidak dapat dikembalikan.';
        Swal.fire(Object.assign({
            icon: 'warning',
            title: 'Hapus data ini?',
            text: message,
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#d63939',
            reverseButtons: true,
            focusCancel: true
        }, swalTema())).then(function (result) { if (result.isConfirmed) form.submit(); });
    });

    // Cegah submit ganda (mis. upload dua kali).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute || !form.hasAttribute('data-once') || e.defaultPrevented) return;
        setTimeout(function () {
            form.querySelectorAll('button[type="submit"]').forEach(function (b) { b.disabled = true; });
        }, 0);
    });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        document.querySelectorAll('form[data-once] button[type="submit"]').forEach(function (b) { b.disabled = false; });
    });
</script>

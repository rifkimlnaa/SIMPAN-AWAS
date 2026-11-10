{{-- Skrip halaman aplikasi: DataTables, jam 24 jam, saksi dinamis, live preview berkas --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script>
(function () {
    'use strict';
    var $ = window.jQuery;

    /* ---------- DataTables: pencarian & pagination ---------- */
    if ($ && $.fn && $.fn.DataTable) {
        var bahasa = {
            search: 'Cari:',
            lengthMenu: 'Tampilkan _MENU_ data',
            info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
            infoEmpty: 'Tidak ada data',
            infoFiltered: '(disaring dari _MAX_ data)',
            zeroRecords: 'Data tidak ditemukan',
            emptyTable: 'Belum ada data',
            paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
        };

        $('table.js-datatable').each(function () {
            var numbered = this.hasAttribute('data-numbered');
            var dt = $(this).DataTable({
                language: bahasa,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                columnDefs: [{ targets: 'no-sort', orderable: false, searchable: false }],
                drawCallback: function () { if (window.lucide) window.lucide.createIcons(); }
            });

            if (numbered) {
                // Nomor urut mengikuti hasil pencarian/pengurutan.
                dt.on('order.dt search.dt', function () {
                    var i = 1;
                    dt.cells(null, 0, { search: 'applied', order: 'applied' }).every(function () { this.data(i++); });
                }).draw();
            }
        });
    }

    /* ---------- Input jam 24 jam (HH:MM): titik dua otomatis ---------- */
    function formatTime24(raw, final) {
        var d = String(raw).replace(/\D/g, '').slice(0, 4);
        if (final) {
            if (d.length === 1 || d.length === 2) d = d.padStart(2, '0') + '00';
            else if (d.length === 3) d = '0' + d;
        }
        return d.length > 2 ? d.slice(0, 2) + ':' + d.slice(2) : d;
    }
    document.querySelectorAll('input[data-time24]').forEach(function (input) {
        input.addEventListener('input', function () { input.value = formatTime24(input.value, false); });
        input.addEventListener('blur', function () { input.value = formatTime24(input.value, true); });
    });

    /* ---------- Baris saksi dinamis di form arsip ---------- */
    var saksiList = document.getElementById('saksiList');
    var saksiTemplate = document.getElementById('saksiTemplate');
    var saksiAdd = document.getElementById('saksiAdd');

    if (saksiList && saksiTemplate && saksiAdd) {
        var MAKS_SAKSI = 10;
        var berikutnya = 0;

        saksiList.querySelectorAll('input[name^="saksi["]').forEach(function (el) {
            var m = el.name.match(/^saksi\[(\d+)\]/);
            if (m) berikutnya = Math.max(berikutnya, parseInt(m[1], 10) + 1);
        });

        var segarkan = function () {
            var baris = saksiList.querySelectorAll('[data-saksi-row]');
            baris.forEach(function (row, i) {
                var judul = row.querySelector('[data-saksi-title]');
                if (judul) judul.textContent = 'Saksi ' + (i + 1);
            });
            saksiAdd.disabled = baris.length >= MAKS_SAKSI;
            var kosong = document.getElementById('saksiEmpty');
            if (kosong) kosong.classList.toggle('hidden', baris.length > 0);
        };

        saksiAdd.addEventListener('click', function () {
            saksiList.insertAdjacentHTML('beforeend', saksiTemplate.innerHTML.split('__INDEX__').join(String(berikutnya++)));
            if (window.lucide) window.lucide.createIcons();
            segarkan();
            var baru = saksiList.querySelectorAll('[data-saksi-row]');
            var input = baru[baru.length - 1].querySelector('input');
            if (input) input.focus();
        });

        saksiList.addEventListener('click', function (e) {
            var tombol = e.target.closest('[data-saksi-remove]');
            if (!tombol) return;
            tombol.closest('[data-saksi-row]').remove();
            segarkan();
        });

        segarkan();
    }

    /* ---------- Live preview berkas yang dipilih di form arsip ---------- */
    var fileInput = document.getElementById('file');
    var previewBox = document.getElementById('filePreview');
    var fileInfo = document.getElementById('fileInfo');

    if (fileInput && previewBox) {
        var objectUrl = null;

        var formatSize = function (bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        };

        fileInput.addEventListener('change', function () {
            if (objectUrl) { URL.revokeObjectURL(objectUrl); objectUrl = null; }

            var file = fileInput.files[0];
            if (!file) return;

            var maxKb = parseInt(fileInput.dataset.maxKb || '0', 10);
            if (maxKb && file.size > maxKb * 1024) {
                fileInput.value = '';
                if (fileInfo) fileInfo.textContent = 'Ukuran berkas melebihi batas ' + (maxKb / 1024).toFixed(0) + ' MB.';
                return;
            }

            objectUrl = URL.createObjectURL(file);
            previewBox.replaceChildren();
            previewBox.classList.add('preview-solid');

            if (file.type.indexOf('image/') === 0) {
                var img = document.createElement('img');
                img.src = objectUrl;
                img.alt = 'Pratinjau berkas yang dipilih';
                previewBox.appendChild(img);
            } else if (file.type === 'application/pdf') {
                var frame = document.createElement('iframe');
                frame.src = objectUrl;
                frame.title = 'Pratinjau PDF yang dipilih';
                previewBox.appendChild(frame);
            } else {
                previewBox.textContent = 'Jenis berkas ini tidak dapat dipratinjau.';
            }

            if (fileInfo) fileInfo.textContent = file.name + ' · ' + formatSize(file.size);
        });
    }
})();
</script>

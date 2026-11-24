{{-- Skrip halaman aplikasi: DataTables, jam 24 jam, saksi & baris dinamis, live preview berkas --}}
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

    /* ---------- Pilihan dokumen PSPP-03 + penghitung cetak/digital ---------- */
    var pspp03Root = document.getElementById('pspp03DocumentPicker');
    if (pspp03Root) {
        var pspp03Rows = pspp03Root.querySelector('[data-pspp03-rows]');
        var pspp03Choices = Array.prototype.slice.call(pspp03Root.querySelectorAll('[data-pspp03-choice]'));
        var pspp03PresetTemplate = document.getElementById('pspp03PresetRowTemplate');
        var pspp03CustomTemplate = document.getElementById('pspp03CustomRowTemplate');
        var pspp03AddCustom = pspp03Root.querySelector('[data-pspp03-add-custom]');
        var pspp03Max = parseInt(pspp03Root.dataset.maks || '30', 10);
        var pspp03Count = pspp03Root.querySelector('[data-pspp03-count]');
        var pspp03Empty = pspp03Root.querySelector('[data-pspp03-empty]');
        var pspp03NextIndex = 0;

        pspp03Rows.querySelectorAll('[name^="dokumen["]').forEach(function (el) {
            var match = el.name.match(/^dokumen\[(\d+)\]/);
            if (match) pspp03NextIndex = Math.max(pspp03NextIndex, parseInt(match[1], 10) + 1);
        });

        var pspp03AllRows = function () {
            return Array.prototype.slice.call(pspp03Rows.querySelectorAll('[data-pspp03-row]'));
        };

        var pspp03Refresh = function () {
            var rows = pspp03AllRows();
            if (pspp03Count) pspp03Count.textContent = rows.length + ' dipilih';
            if (pspp03Empty) pspp03Empty.classList.toggle('hidden', rows.length > 0);
            if (pspp03AddCustom) pspp03AddCustom.disabled = rows.length >= pspp03Max;
            rows.forEach(function (row) {
                row.querySelectorAll('[data-pspp03-stepper]').forEach(function (stepper) {
                    var input = stepper.querySelector('input');
                    var minus = stepper.querySelector('[data-pspp03-step="-1"]');
                    var plus = stepper.querySelector('[data-pspp03-step="1"]');
                    if (!input) return;
                    var value = Math.max(0, parseInt(input.value || '0', 10));
                    var max = parseInt(input.max || '9999', 10);
                    if (minus) minus.disabled = value <= 0;
                    if (plus) plus.disabled = value >= max;
                });
            });
        };

        var pspp03Insert = function (template, presetKey, presetLabel, focus) {
            if (!template || pspp03AllRows().length >= pspp03Max) return null;
            var html = template.innerHTML.split('__INDEX__').join(String(pspp03NextIndex++));
            html = html.split('__PRESET_KEY__').join(presetKey || '');
            pspp03Rows.insertAdjacentHTML('beforeend', html);
            var row = pspp03Rows.lastElementChild;
            if (presetKey) {
                var hidden = row.querySelector('[data-pspp03-kind-input]');
                var title = row.querySelector('[data-pspp03-row-title]');
                row.dataset.presetKey = presetKey;
                if (hidden) hidden.value = presetLabel || '';
                if (title) title.textContent = presetLabel || '';
            }
            if (window.lucide) window.lucide.createIcons();
            pspp03Refresh();
            if (focus) {
                var target = row.querySelector('[data-pspp03-kind-input], textarea');
                if (target) target.focus();
            }
            return row;
        };

        pspp03Choices.forEach(function (choice) {
            choice.addEventListener('change', function () {
                var key = choice.value;
                var existing = pspp03AllRows().find(function (row) { return row.dataset.presetKey === key; });
                if (choice.checked && !existing) {
                    if (pspp03AllRows().length >= pspp03Max) {
                        choice.checked = false;
                        window.alert('Maksimal ' + pspp03Max + ' dokumen dalam satu tanda terima.');
                        return;
                    }
                    pspp03Insert(pspp03PresetTemplate, key, choice.parentElement.querySelector('span')?.textContent.trim() || '', false);
                } else if (!choice.checked && existing) {
                    existing.remove();
                }
                pspp03Refresh();
            });
        });

        if (pspp03AddCustom) {
            pspp03AddCustom.addEventListener('click', function () {
                var row = pspp03Insert(pspp03CustomTemplate, '', '', true);
                if (!row && pspp03AllRows().length >= pspp03Max) {
                    window.alert('Maksimal ' + pspp03Max + ' dokumen dalam satu tanda terima.');
                }
            });
        }

        pspp03Rows.addEventListener('click', function (event) {
            var remove = event.target.closest('[data-pspp03-remove]');
            if (remove) {
                var row = remove.closest('[data-pspp03-row]');
                if (!row) return;
                var key = row.dataset.presetKey;
                if (key) {
                    var choice = pspp03Choices.find(function (item) { return item.value === key; });
                    if (choice) choice.checked = false;
                }
                row.remove();
                pspp03Refresh();
                return;
            }

            var stepButton = event.target.closest('[data-pspp03-step]');
            if (stepButton) {
                var stepper = stepButton.closest('[data-pspp03-stepper]');
                var input = stepper && stepper.querySelector('input');
                if (!input) return;
                var current = parseInt(input.value || '0', 10);
                var min = parseInt(input.min || '0', 10);
                var max = parseInt(input.max || '9999', 10);
                var next = Math.min(max, Math.max(min, current + parseInt(stepButton.dataset.pspp03Step, 10)));
                input.value = String(next);
                input.dispatchEvent(new Event('change', { bubbles: true }));
                pspp03Refresh();
            }
        });

        pspp03Refresh();
    }

    /* ---------- Baris dinamis (dokumen / alat bukti) di form arsip ---------- */
    var barisWrap = document.getElementById('barisWrap');
    var barisList = document.getElementById('barisList');
    var barisTemplate = document.getElementById('barisTemplate');
    var barisAdd = document.getElementById('barisAdd');

    if (barisWrap && barisList && barisTemplate && barisAdd) {
        var MAKS_BARIS = parseInt(barisWrap.dataset.maks || '30', 10);
        var butir = barisWrap.dataset.butir || 'Dokumen';
        var awalan = barisWrap.dataset.awalan || '';
        var nextBaris = 0;

        barisList.querySelectorAll('[name^="dokumen["]').forEach(function (el) {
            var m = el.name.match(/^dokumen\[(\d+)\]/);
            if (m) nextBaris = Math.max(nextBaris, parseInt(m[1], 10) + 1);
        });

        var segarkanBaris = function () {
            var rows = barisList.querySelectorAll('[data-baris-row]');
            rows.forEach(function (row, i) {
                var judul = row.querySelector('[data-baris-title]');
                if (judul) judul.textContent = awalan ? awalan + (i + 1) : butir + ' ' + (i + 1);
            });
            barisAdd.disabled = rows.length >= MAKS_BARIS;
            var kosong = document.getElementById('barisEmpty');
            if (kosong) kosong.classList.toggle('hidden', rows.length > 0);
        };

        var tambahBaris = function (fokus) {
            barisList.insertAdjacentHTML('beforeend', barisTemplate.innerHTML.split('__INDEX__').join(String(nextBaris++)));
            if (window.lucide) window.lucide.createIcons();
            segarkanBaris();
            if (fokus) {
                var rows = barisList.querySelectorAll('[data-baris-row]');
                var el = rows[rows.length - 1].querySelector('input, textarea');
                if (el) el.focus();
            }
        };

        barisAdd.addEventListener('click', function () { tambahBaris(true); });

        barisList.addEventListener('click', function (e) {
            var tombol = e.target.closest('[data-baris-remove]');
            if (!tombol) return;
            tombol.closest('[data-baris-row]').remove();
            segarkanBaris();
        });

        // Form baru: langsung sediakan satu baris kosong.
        if (!barisList.querySelector('[data-baris-row]')) tambahBaris(false);
        segarkanBaris();
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

            var extension = (file.name.split('.').pop() || '').toLowerCase();
            var isVideo = file.type.indexOf('video/') === 0 || ['mp4', 'webm'].indexOf(extension) !== -1;
            var isImage = file.type.indexOf('image/') === 0 || ['jfif', 'jpg', 'jpeg', 'png', 'webp'].indexOf(extension) !== -1;
            // Form arsip tetap memakai batas lama berbasis data-max-kb.
            // Form media latar login memakai batas terpisah untuk gambar dan video.
            var legacyMaxKb = parseInt(fileInput.dataset.maxKb || '0', 10);
            var maxMb = isVideo
                ? parseFloat(fileInput.dataset.maxVideoMb || '100')
                : parseFloat(fileInput.dataset.maxImageMb || '10');

            if (legacyMaxKb > 0 && file.size > legacyMaxKb * 1024) {
                fileInput.value = '';
                if (fileInfo) fileInfo.textContent = 'Ukuran berkas melebihi batas ' + (legacyMaxKb / 1024).toFixed(0) + ' MB.';
                return;
            }

            if (!legacyMaxKb && (isVideo || isImage) && file.size > maxMb * 1024 * 1024) {
                fileInput.value = '';
                if (fileInfo) fileInfo.textContent = 'Ukuran file melebihi batas ' + maxMb + ' MB.';
                return;
            }

            objectUrl = URL.createObjectURL(file);
            previewBox.replaceChildren();
            previewBox.classList.add('preview-solid');

            if (isVideo) {
                var video = document.createElement('video');
                video.src = objectUrl;
                video.controls = true;
                video.muted = true;
                video.loop = true;
                video.playsInline = true;
                video.preload = 'metadata';
                video.setAttribute('aria-label', 'Pratinjau video latar yang dipilih');
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'contain';
                previewBox.appendChild(video);
            } else if (isImage) {
                var img = document.createElement('img');
                img.src = objectUrl;
                img.alt = 'Pratinjau gambar latar yang dipilih';
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

<script>
(function () {
    'use strict';

    // Gentle entrance for existing cards; no markup or data behavior is changed.
    var main = document.querySelector('main');
    if (main && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        main.classList.add('ui-content-enter');
        main.querySelectorAll('.card').forEach(function (card, index) {
            card.style.setProperty('--ui-enter-delay', Math.min(index * 42, 210) + 'ms');
        });
    }

    // Reusable skeleton overlay for normal same-origin page navigation.
    var loadingLayer = document.createElement('div');
    loadingLayer.className = 'ui-page-loading';
    loadingLayer.setAttribute('aria-hidden', 'true');
    loadingLayer.innerHTML =
        '<aside class="ui-page-loading__sidebar">' +
            '<div class="ui-page-loading__brand ui-skeleton-shimmer"></div>' +
            '<div class="ui-page-loading__nav">' +
                '<div class="ui-page-loading__nav-item ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__nav-item ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__nav-item ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__nav-item ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__nav-item ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__nav-item ui-skeleton-shimmer"></div>' +
            '</div>' +
        '</aside>' +
        '<section class="ui-page-loading__main">' +
            '<header class="ui-page-loading__topbar">' +
                '<div class="ui-page-loading__title ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__avatar ui-skeleton-shimmer"></div>' +
            '</header>' +
            '<div class="ui-page-loading__content">' +
                '<div class="ui-page-loading__welcome ui-skeleton-shimmer"></div>' +
                '<div class="ui-page-loading__stats">' +
                    '<div class="ui-page-loading__stat ui-skeleton-shimmer"></div>' +
                    '<div class="ui-page-loading__stat ui-skeleton-shimmer"></div>' +
                    '<div class="ui-page-loading__stat ui-skeleton-shimmer"></div>' +
                    '<div class="ui-page-loading__stat ui-skeleton-shimmer"></div>' +
                '</div>' +
                '<div class="ui-page-loading__columns">' +
                    '<div class="ui-page-loading__panel ui-skeleton-shimmer"></div>' +
                    '<div class="ui-page-loading__panel ui-skeleton-shimmer"></div>' +
                '</div>' +
            '</div>' +
        '</section>';
    document.body.appendChild(loadingLayer);

    function showLoadingSkeleton() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        loadingLayer.classList.add('is-visible');
    }

    document.addEventListener('click', function (event) {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        var link = event.target.closest && event.target.closest('a[href]');
        if (!link || link.hasAttribute('download') || link.target === '_blank' || link.hasAttribute('data-no-page-skeleton')) return;

        var rawHref = link.getAttribute('href') || '';
        if (!rawHref || rawHref.charAt(0) === '#' || /^javascript:/i.test(rawHref)) return;

        var destination;
        try { destination = new URL(link.href, window.location.href); } catch (error) { return; }
        if (destination.origin !== window.location.origin) return;
        if (destination.pathname === window.location.pathname && destination.search === window.location.search) return;
        // File/form downloads and PDF previews should not display a page-navigation skeleton.
        if (/\/(download|pdf)(\/|$)/i.test(destination.pathname) || /\.(pdf|zip|xlsx?|docx?|png|jpe?g|gif|webp|svg|mp4|mp3)$/i.test(destination.pathname)) return;

        window.requestAnimationFrame(showLoadingSkeleton);
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (event.defaultPrevented || !form || form.tagName !== 'FORM') return;
        if (form.classList.contains('js-confirm-delete') || form.hasAttribute('data-no-page-skeleton')) return;
        if (form.target && form.target !== '_self') return;
        window.requestAnimationFrame(showLoadingSkeleton);
    });

    // Hide the skeleton when a page is restored from the browser back-forward cache.
    window.addEventListener('pageshow', function () {
        loadingLayer.classList.remove('is-visible');
    });
})();
</script>

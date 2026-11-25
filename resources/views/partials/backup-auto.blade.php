@if (auth()->check() && auth()->user()->isAdmin())
<script>
(function () {
    'use strict';
    // Unduh otomatis backup terjadwal: dicek sekali tiap halaman dibuka oleh admin.
    var key = 'backupAutoCek';
    try {
        var t = parseInt(localStorage.getItem(key) || '0', 10);
        if (Date.now() - t < 60000) return;
        localStorage.setItem(key, String(Date.now()));
    } catch (e) { /* abaikan */ }

    fetch(@json(route('backup.status')), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.ok ? r.json() : { due: false }; })
        .then(function (d) {
            if (!d.due) return;

            var info = document.createElement('div');
            info.setAttribute('role', 'status');
            info.style.cssText = 'position:fixed;right:1rem;bottom:1rem;z-index:9999;max-width:20rem;padding:.75rem 1rem;border-radius:.75rem;background:#0f172a;color:#fff;font-size:.8125rem;box-shadow:0 8px 24px rgba(0,0,0,.25)';
            info.textContent = 'Backup terjadwal sedang disiapkan dan akan terunduh otomatis…';
            document.body.appendChild(info);
            setTimeout(function () { info.remove(); }, 20000);

            var name = 'backupAutoFrame';
            var frame = document.createElement('iframe');
            frame.name = name;
            frame.style.display = 'none';
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = @json(route('backup.otomatis'));
            form.target = name;
            var token = document.createElement('input');
            token.type = 'hidden'; token.name = '_token';
            token.value = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
            form.appendChild(token);
            document.body.appendChild(frame);
            document.body.appendChild(form);
            form.submit();
        })
        .catch(function () { /* abaikan */ });
})();
</script>
@endif

@php
    $noticeToasts = [];
    $flash = session('toastr');
    if (is_array($flash) && ! empty($flash['message'])) {
        $noticeToasts[] = [$flash['type'] ?? 'info', $flash['message']];
    }
    if (session('success')) {
        $noticeToasts[] = ['success', session('success')];
    }
    if (session('message')) {
        $noticeToasts[] = ['success', session('message')];
    }
    if (session('error')) {
        $noticeToasts[] = ['error', session('error')];
    }
    if ($errors->any()) {
        $noticeToasts[] = ['error', $errors->count() > 1
            ? 'Revisa los campos marcados. '.$errors->first()
            : $errors->first()];
    }

    $statusMessages = [
        'profile-updated' => 'Cambios guardados.',
        'password-updated' => 'Contraseña actualizada.',
        'verification-link-sent' => 'Enviamos un nuevo enlace de verificación a tu correo.',
    ];
    if (session('status')) {
        $status = session('status');
        $noticeToasts[] = ['success', $statusMessages[$status] ?? $status];
    }
@endphp
<script>
    (function () {
        const palettes = {
            light: {
                success: { background: '#e7f6ee', color: '#1c5c3a', iconColor: '#5aaf84' },
                error: { background: '#fdeeee', color: '#7a3333', iconColor: '#e08a8a' },
                warning: { background: '#fff5e8', color: '#7a5418', iconColor: '#e2b36a' },
                info: { background: '#eaf3fb', color: '#24527a', iconColor: '#7eb0d8' },
            },
            dark: {
                success: { background: '#1e3329', color: '#d5f3e4', iconColor: '#8dceb0' },
                error: { background: '#3a2626', color: '#f8dede', iconColor: '#e7a3a3' },
                warning: { background: '#3a3122', color: '#f8e7c8', iconColor: '#e7c48a' },
                info: { background: '#1e2c3a', color: '#d7e8f6', iconColor: '#9ec4e4' },
            },
        };

        function themeName() {
            return document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'dark' : 'light';
        }

        function confirmColors() {
            return themeName() === 'dark'
                ? { background: '#2c3136', color: '#f8f9fa' }
                : { background: '#fffdfb', color: '#243028' };
        }

        window.coteToast = function (type, message) {
            if (!window.Swal || message === undefined || message === null || message === '') return;
            const kind = palettes[themeName()][type] ? type : 'info';
            const colors = palettes[themeName()][kind];
            Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: kind === 'error' || kind === 'warning' ? 5200 : 3800,
                timerProgressBar: true,
                customClass: { popup: 'cote-toast cote-toast-' + kind },
            }).fire({
                icon: kind,
                title: String(message),
                background: colors.background,
                color: colors.color,
                iconColor: colors.iconColor,
            });
        };

        window.coteConfirm = function (message, options) {
            options = options || {};
            const colors = confirmColors();
            const tone = options.tone === 'ok' ? 'ok' : 'danger';
            return Swal.fire({
                title: options.title || 'Confirmar',
                text: message,
                icon: tone === 'ok' ? 'question' : 'warning',
                iconColor: tone === 'ok' ? '#5aaf84' : '#e2b36a',
                showCancelButton: true,
                focusCancel: tone !== 'ok',
                confirmButtonText: options.confirmText || 'Sí, continuar',
                cancelButtonText: options.cancelText || 'Cancelar',
                reverseButtons: true,
                buttonsStyling: false,
                background: colors.background,
                color: colors.color,
                customClass: {
                    popup: 'cote-confirm',
                    confirmButton: 'cote-btn cote-btn-' + tone,
                    cancelButton: 'cote-btn cote-btn-cancel',
                },
            }).then(function (result) { return !!result.isConfirmed; });
        };

        window.cotePrompt = function (title) {
            const colors = confirmColors();
            return Swal.fire({
                title: title,
                input: 'textarea',
                inputPlaceholder: 'Escribe el motivo',
                showCancelButton: true,
                confirmButtonText: 'Rechazar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                buttonsStyling: false,
                background: colors.background,
                color: colors.color,
                iconColor: '#e2b36a',
                customClass: {
                    popup: 'cote-confirm',
                    confirmButton: 'cote-btn cote-btn-danger',
                    cancelButton: 'cote-btn cote-btn-cancel',
                },
                inputValidator: function (value) {
                    if (!value || !value.trim()) return 'Escribe un motivo para continuar';
                },
            }).then(function (result) {
                return result.isConfirmed ? result.value.trim() : null;
            });
        };

        window.toastr = {
            success: function (message) { coteToast('success', message); },
            error: function (message) { coteToast('error', message); },
            warning: function (message) { coteToast('warning', message); },
            info: function (message) { coteToast('info', message); },
        };

        window.addEventListener('cote-toast', function (event) {
            const raw = event.detail || {};
            const detail = raw.message ? raw : (raw[0] || {});
            coteToast(detail.type, detail.message);
        });

        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.dataset.coteBypass === '1') {
                delete form.dataset.coteBypass;
                return;
            }
            const trigger = event.submitter;
            const message = (trigger && trigger.getAttribute('data-cote-confirm')) || form.getAttribute('data-cote-confirm');
            if (!message) return;
            event.preventDefault();
            event.stopPropagation();
            coteConfirm(message, {
                confirmText: (trigger && trigger.getAttribute('data-cote-confirm-text')) || form.getAttribute('data-cote-confirm-text') || undefined,
                tone: (trigger && trigger.getAttribute('data-cote-confirm-tone')) || form.getAttribute('data-cote-confirm-tone') || undefined,
            }).then(function (ok) {
                if (!ok) return;
                form.dataset.coteBypass = '1';
                form.requestSubmit(trigger || undefined);
            });
        }, true);

        const queued = @json($noticeToasts);
        document.addEventListener('DOMContentLoaded', function () {
            queued.forEach(function (item) { coteToast(item[0], item[1]); });
        });
    })();
</script>

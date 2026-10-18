{{-- Va en <head>, antes del CSS: aplica el tema guardado (o el del sistema) sin parpadeo --}}
<script>
    (function() {
        try {
            var theme = localStorage.getItem('theme');
            if (!theme && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)
                theme = 'dark';
            theme = theme || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.classList.toggle('dark', theme === 'dark');
        } catch (e) { /* ignore */ }
    })();
</script>

{{-- Alterna claro/oscuro en cualquier botón con data-theme-toggle y guarda la preferencia --}}
<script>
    document.addEventListener('click', function (event) {
        if (!event.target.closest('[data-theme-toggle]')) return;
        var html = document.documentElement;
        var next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', next);
        html.classList.toggle('dark', next === 'dark');
        try { localStorage.setItem('theme', next); } catch (e) {}
    });
</script>

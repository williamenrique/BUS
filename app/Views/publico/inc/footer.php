    <footer style="text-align:center; padding:1.5rem; font-size:.75rem; color:#94a3b8; border-top:1px solid #e2e8f0; margin-top:2rem; background:#fff;">
        <p style="margin:0;">
            Sistema Integral de Gestión de Transporte · SITGO
            · <span id="lastUpdate">Última actualización: --</span>
        </p>
    </footer>

    <script>
        window.SITGO_BASE = "<?= BASE_URL ?>";
        function _updateLastUpdate() {
            const el = document.getElementById('lastUpdate');
            if (el) el.textContent = 'Última actualización: ' + new Date().toLocaleTimeString('es-VE');
        }
        _updateLastUpdate();
        setInterval(_updateLastUpdate, 30000);
        if (window.lucide) lucide.createIcons();

    document.addEventListener('DOMContentLoaded', () => {
        // Marcar sección activa según el scroll
        const secciones = document.querySelectorAll('.pub-sec');
        const links = document.querySelectorAll('.publico-nav-links a[href^="#"]');

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const id = entry.target.id;
                    links.forEach(l => {
                        l.classList.toggle('active', l.getAttribute('href') === '#' + id);
                    });
                }
            });
        }, { rootMargin: '-40% 0px -55% 0px' });

        secciones.forEach(s => observer.observe(s));
    });
    </script>

    <!-- ⬇️ IMPORTANTE: pdf.js para SITGO_PDF -->
    <script src="<?= asset('js/pdf.js') ?>"></script>
    <script src="<?= asset('js/movimientos.js') ?>"></script>
</body>
</html>
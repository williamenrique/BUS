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
        const secciones = document.querySelectorAll('.pub-sec');
        const links = document.querySelectorAll('.publico-nav-links a[href^="#"]');
        if (!secciones.length || !links.length) return;

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

<?php
/* ============================================================
   CARGA CONDICIONAL DE SCRIPTS
   Cada vista declara qué necesita antes de incluir el footer.
   ============================================================ */

// DataTable propio (solo si la vista lo pidió)
if (!empty($cargarDataTable)): ?>
<script src="<?= asset('js/components/DataTable.js') ?>"></script>
<?php endif; ?>

<?php
// pdf.js centralizado (lo usan Movimientos y Bienes públicos)
if (!empty($cargarPdf)): ?>
<script src="<?= asset('js/pdf.js') ?>"></script>
<?php endif; ?>

<?php
// Script específico de la vista (se declara por nombre de archivo)
if (!empty($pageScript)): ?>
<script src="<?= asset('js/' . $pageScript) ?>"></script>
<?php endif; ?>
</body>
</html>
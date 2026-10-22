</main>

        <footer class="main-footer">
            <?= date('Y') ?> · <?= e(SITGO_APP_FULLNAME) ?> v<?= e(SITGO_VERSION) ?>
        </footer>
    </div><!-- /#content -->
</div><!-- /#app -->

<?php require VIEWS_PATH . 'inc/confirm_modal.php'; ?>

<script>
    // Variable global para que todos los módulos puedan construir URLs
    window.SITGO_BASE = "<?= BASE_URL ?>";
    console.log('SITGO_BASE =', window.SITGO_BASE);
    window.SITGO_INST = <?= (int)($instId ?? 1) ?>;
</script>
<script src="https://cdn.jsdelivr.net/npm/qz-tray@2.2.5/qz-tray.min.js"></script>
<script src="<?= asset('js/components/DataTable.js') ?>"></script>
<script src="<?= asset('js/components/SearchSelect.js') ?>"></script>
<script src="<?= asset('js/utils.js') ?>"></script>
<script src="<?= asset('js/pdf.js') ?>"></script>
<script src="<?= asset('js/app.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>
<script src="<?= asset('js/function.imprimirQZ.js') ?>"></script>
<script src="<?= asset('js/notifications.js') ?>"></script>

<?php if (!empty($scripts)): ?>
    <?php foreach ($scripts as $s): ?>
        <script src="<?= asset('js/' . $s) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
</body>
</html>
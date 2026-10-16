        </main>

        <footer class="main-footer">
            <?= date('Y') ?> · <?= e(SITGO_APP_FULLNAME) ?> v<?= e(SITGO_VERSION) ?>
        </footer>
    </div><!-- /#content -->
</div><!-- /#app -->

<script>
    // Variable global para que todos los módulos puedan construir URLs
    window.SITGO_BASE = "<?= BASE_URL ?>";
    console.log('SITGO_BASE =', window.SITGO_BASE);
</script>

<script src="<?= asset('js/components/DataTable.js') ?>"></script>
<script src="<?= asset('js/components/SearchSelect.js') ?>"></script>
<script src="<?= asset('js/utils.js') ?>"></script>       <!-- 👈 IMPORTANTE -->
<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>

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
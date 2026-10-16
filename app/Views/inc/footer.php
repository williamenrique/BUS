        </main>

        <footer class="main-footer">
            <?= date('Y') ?> · <?= e(SITGO_APP_FULLNAME) ?> v<?= e(SITGO_VERSION) ?>
        </footer>
    </div><!-- /#content -->
</div><!-- /#app -->

<script src="<?= asset('js/app.js') ?>"></script>
<script src="<?= asset('js/dashboard.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
</body>
</html>
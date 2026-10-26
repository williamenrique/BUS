<!-- ============================================================ -->
<!-- MODAL DE CONFIRMACIÓN REUTILIZABLE (SITGO)                   -->
<!-- Disponible globalmente vía footer.php                        -->
<!-- Uso: await sitgoConfirm({ title, message, okText, type })    -->
<!-- ============================================================ -->
<div class="confirm-backdrop" id="sitgoConfirmModal">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="sitgoConfirmTitle">
        <div class="confirm-icon-wrap" id="sitgoConfirmIconWrap">
            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
        </div>

        <h3 class="confirm-title" id="sitgoConfirmTitle">¿Confirmar acción?</h3>
        <p class="confirm-message" id="sitgoConfirmMessage">Esta acción no se puede deshacer.</p>

        <div class="confirm-actions">
            <button type="button" class="btn btn-ghost" id="sitgoConfirmCancel">
                <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                <span id="sitgoConfirmCancelText">Cancelar</span>
            </button>
            <button type="button" class="btn btn-danger" id="sitgoConfirmOk">
                <?= icon_tag('check', 'check', 'w-4 h-4') ?>
                <span id="sitgoConfirmOkText">Confirmar</span>
            </button>
        </div>
    </div>
</div>
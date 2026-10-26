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

<!-- ============================================================ -->
<!-- MODAL DE PROMPT (input) · SITGO                               -->
<!-- Disponible globalmente vía footer.php                         -->
<!-- Uso: await sitgoPrompt({ title, message, inputType, ... })    -->
<!-- ============================================================ -->
<div class="confirm-backdrop" id="sitgoPromptModal">
    <div class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="sitgoPromptTitle">
        <div class="confirm-icon-wrap" id="sitgoPromptIconWrap">
            <i data-lucide="key-round" class="w-6 h-6"></i>
        </div>

        <h3 class="confirm-title" id="sitgoPromptTitle">Ingresa un valor</h3>
        <p class="confirm-message" id="sitgoPromptMessage">—</p>

        <div class="confirm-input-wrap">
            <input type="text" id="sitgoPromptInput" class="confirm-input" autocomplete="off">
            <p id="sitgoPromptError" class="confirm-input-error" style="display:none;"></p>
        </div>

        <div class="confirm-actions" id="sitgoPromptActions">
            <button type="button" class="btn btn-ghost" id="sitgoPromptCancel">
                <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                <span id="sitgoPromptCancelText">Cancelar</span>
            </button>
            <button type="button" class="btn btn-primary" id="sitgoPromptOk">
                <?= icon_tag('check', 'check', 'w-4 h-4') ?>
                <span id="sitgoPromptOkText">Aceptar</span>
            </button>
        </div>
    </div>
</div>
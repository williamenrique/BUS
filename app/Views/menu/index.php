<!-- ============================================ -->
<!-- PAGE HEADER                                   -->
<!-- ============================================ -->
<div class="page-header">
    <div>
        <h2 class="page-title">Gestión de Menús</h2>
        <p class="page-subtitle">
            Administra menús, submenús y sus accesos por usuario o departamento
        </p>
    </div>
    <div style="display:flex; gap:.5rem;">
        <button id="btnNuevoMenu" class="btn btn-primary">
            <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
            <span id="btnNuevoMenuText">Nuevo Menú</span>
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- SECCIÓN FORMULARIO (colapsable)               -->
<!-- ============================================ -->
<section id="seccionForm" class="card form-section collapsed">
    <header class="form-section-header" id="toggleForm">
        <div class="form-section-title">
            <span id="formSectionIconWrap">
                <?= icon_tag('menu', 'menu', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formSectionTitle">Nuevo Menú</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Configura los datos básicos. Los submenús se gestionan aparte.
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body">
        <form id="formMenu" novalidate>
            <input type="hidden" name="menu_id" id="menu_id">

            <!-- Selector de tipo -->
            <div class="menu-tipo-selector">
                <label class="menu-tipo-opt active" data-tipo="link">
                    <input type="radio" name="menu_tipo" value="link" checked>
                    <span class="menu-tipo-icon">
                        <?= icon_tag('link', 'link', 'w-4 h-4') ?>
                    </span>
                    <div>
                        <strong>Menú de Link</strong>
                        <small>Un enlace directo a una sección</small>
                    </div>
                </label>
                <label class="menu-tipo-opt" data-tipo="grupo">
                    <input type="radio" name="menu_tipo" value="grupo">
                    <span class="menu-tipo-icon">
                        <?= icon_tag('folder-tree', 'folder-tree', 'w-4 h-4') ?>
                    </span>
                    <div>
                        <strong>Menú con Submenús</strong>
                        <small>Agrupa varias opciones</small>
                    </div>
                </label>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nombre <span class="req">*</span></label>
                    <input type="text" name="menu_nombre" id="menu_nombre" maxlength="100"
                           autocomplete="off" placeholder="Ej: Flota">
                    <span class="error" data-error="menu_nombre"></span>
                </div>

                <div class="form-group">
                    <label>Icono (Lucide)</label>
                    <div class="icon-input-wrap">
                        <span class="icon-preview" id="iconPreview">
                            <?= icon_tag('circle', 'circle', 'w-4 h-4') ?>
                        </span>
                        <input type="text" name="menu_icono" id="menu_icono" maxlength="50"
                            placeholder="Selecciona un icono...">
                    </div>
                    <small class="text-muted">
                        Haz clic en el botón <i data-lucide="layout-grid" style="width:12px;height:12px;display:inline-block;vertical-align:middle;"></i> para abrir el catálogo
                    </small>
                </div>

                <div class="form-group" id="rutaGroup">
                    <label>Ruta <span class="req" id="rutaReq">*</span></label>
                    <input type="text" name="menu_ruta" id="menu_ruta" maxlength="255"
                           placeholder="Ej: flota/i/1">
                    <span class="error" data-error="menu_ruta"></span>
                </div>

                <div class="form-group">
                    <label>Menú Padre</label>
                    <select name="menu_padre_id" id="menu_padre_id"></select>
                </div>

                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="menu_orden" id="menu_orden" value="0" min="0" max="9999">
                </div>

                <div class="form-group">
                    <label>Scope</label>
                    <input type="text" name="menu_scope" id="menu_scope" maxlength="50"
                           placeholder="general" value="general">
                </div>

                <div class="form-group">
                    <label>Estado</label>
                    <select name="menu_estado" id="menu_estado">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
            </div>

            <!-- Bloques dinámicos (solo en modo edición) -->
            <div class="menu-block" id="blockUsuarios" style="display:none;">
                <div class="menu-block-head">
                    <h4>
                        <?= icon_tag('users', 'users', 'w-4 h-4') ?>
                        Usuarios con acceso directo
                    </h4>
                </div>
                <div class="menu-ruta-add">
                    <select id="selUsuarioAdd" style="flex:1;"></select>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddUsuario">
                        <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
                        <span>Asignar</span>
                    </button>
                </div>
                <ul id="listaUsuarios" class="menu-lista">
                    <li class="text-muted" style="padding:.5rem; text-align:center;">Sin usuarios asignados.</li>
                </ul>
            </div>

            <div class="menu-block" id="blockDeptos" style="display:none;">
                <div class="menu-block-head">
                    <h4>
                        <?= icon_tag('building-2', 'building-2', 'w-4 h-4') ?>
                        Departamentos con acceso
                    </h4>
                </div>
                <div class="menu-ruta-add">
                    <select id="selDeptoAdd" style="flex:1;"></select>
                    <button type="button" class="btn btn-primary btn-sm" id="btnAddDepto">
                        <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
                        <span>Asignar</span>
                    </button>
                </div>
                <ul id="listaDeptos" class="menu-lista">
                    <li class="text-muted" style="padding:.5rem; text-align:center;">Sin departamentos asignados.</li>
                </ul>
            </div>

            <div class="form-actions">
                <button type="button" id="btnCancelar" class="btn btn-ghost">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cancelar</span>
                </button>
                <button type="submit" id="btnGuardar" class="btn btn-primary">
                    <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                    <span class="btn-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============================================ -->
<!-- BARRA DE FILTROS                              -->
<!-- ============================================ -->
<div class="card mb-4">
    <div class="card-body filters-row">
        <div class="filter-search" style="flex:1;">
            <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
            <input type="text" id="filtroMenu" placeholder="Buscar menú por nombre o ruta...">
        </div>
        <select id="filtroEstadoMenu" class="filter-select">
            <option value="">Todos</option>
            <option value="1">Solo activos</option>
            <option value="0">Solo inactivos</option>
        </select>
        <select id="filtroTipoMenu" class="filter-select">
            <option value="">Todos los tipos</option>
            <option value="link">Solo con link</option>
            <option value="grupo">Solo con submenús</option>
        </select>
        <button id="btnExpandirTodo" class="btn btn-ghost">
            <?= icon_tag('chevrons-down', 'chevrons-down', 'w-4 h-4') ?>
            <span>Expandir</span>
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- GRID DE MENÚS                                 -->
<!-- ============================================ -->
<div id="gridMenus" class="menu-grid">
    <div class="dt-loading" style="grid-column:1/-1; padding:3rem; text-align:center;">
        Cargando menús...
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL · SUBMENÚS                              -->
<!-- ============================================ -->
<div id="modalSubmenu" class="modal-backdrop">
    <div class="modal" style="max-width:640px;">
        <div class="modal-header">
            <h3>
                <?= icon_tag('folder-tree', 'folder-tree', 'w-4 h-4') ?>
                <span>Submenús de <strong id="submenuMenuNombre">—</strong></span>
            </h3>
            <button type="button" class="modal-close" onclick="cerrarModalSubmenu()">&times;</button>
        </div>
        <div class="modal-body" style="padding:1.5rem;">

            <!-- Form submenú -->
            <div class="submenu-form-wrap">
                <h4 id="submenuFormTitle">Nuevo Submenú</h4>
                <form id="formSubmenu" novalidate>
                    <input type="hidden" id="sm_menu_id">
                    <input type="hidden" id="sm_id">

                    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="form-group">
                            <label>Nombre <span class="req">*</span></label>
                            <input type="text" id="sm_nombre" maxlength="100" required>
                        </div>
                        <div class="form-group">
                            <label>Icono</label>
                            <div class="icon-input-wrap">
                                <span class="icon-preview" id="smIconPreview">
                                    <?= icon_tag('circle', 'circle', 'w-4 h-4') ?>
                                </span>
                                <input type="text" id="sm_icono" maxlength="50" placeholder="Selecciona un icono...">
                            </div>
                        </div>
                        <div class="form-group" style="grid-column: span 2;">
                            <label>Ruta <span class="req">*</span></label>
                            <input type="text" id="sm_ruta" maxlength="255" placeholder="flota/i/1/aceite">
                        </div>
                        <div class="form-group">
                            <label>Orden</label>
                            <input type="number" id="sm_orden" value="0" min="0">
                        </div>
                        <div class="form-group">
                            <label>Estado</label>
                            <select id="sm_estado">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top:1rem;">
                        <button type="button" class="btn btn-ghost btn-sm" id="btnSmCancelar">
                            Limpiar
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                            <span id="btnSmGuardarText">Guardar</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de submenús existentes -->
            <div class="submenu-list-wrap">
                <h4>Submenús existentes</h4>
                <ul id="listaSubmenus" class="menu-lista">
                    <li class="text-muted" style="padding:1rem; text-align:center;">
                        Sin submenús.
                    </li>
                </ul>
            </div>

        </div>
    </div>
</div>
<script src="<?= asset('js/components/IconPicker.js') ?>"></script>
<script>
    window.SITGO_BASE = "<?= BASE_URL ?>";
</script>
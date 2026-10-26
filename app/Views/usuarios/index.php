<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">Usuarios</h2>
        <p class="page-subtitle">Gestión de accesos al sistema</p>
    </div>
    <button id="btnNuevo" class="btn btn-primary">
        <?= icon_tag('user-plus', 'user-plus', 'w-4 h-4') ?>
        <span id="btnNuevoText">Nuevo Usuario</span>
    </button>
</div>

<!-- ============================================ -->
<!-- SECCIÓN FORMULARIO (colapsable)               -->
<!-- ============================================ -->
<section id="seccionForm" class="card form-section collapsed">
    <header class="form-section-header" id="toggleForm">
        <div class="form-section-title">
            <span id="formSectionIconWrap">
                <?= icon_tag('user-plus', 'user-plus', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formSectionTitle">Nuevo Usuario</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Selecciona un personal y completa los datos
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body" id="formSectionBody">
        <form id="formUsuario" novalidate enctype="multipart/form-data">
            <input type="hidden" name="usuario_id" id="usuario_id">

            <div class="form-grid">
                <!-- Personal PRIMERO (SearchSelect global) -->
                <div class="form-group">
                    <label>Personal <span class="req">*</span></label>
                    <div id="wrapPersonal"></div>
                    <input type="hidden" name="usuario_id_personal" id="usuario_id_personal">
                    <span class="error" data-error="usuario_id_personal"></span>
                </div>

                <div class="form-group">
                    <label>Rol <span class="req">*</span></label>
                    <div id="wrapRol"></div>
                    <input type="hidden" name="usuario_rol_id" id="usuario_rol_id">
                    <span class="error" data-error="usuario_rol_id"></span>
                </div>

                <div class="form-group">
                    <label>Departamento <span class="req">*</span></label>
                    <div id="wrapDepartamento"></div>
                    <input type="hidden" name="usuario_departamento_id" id="usuario_departamento_id">
                    <span class="error" data-error="usuario_departamento_id"></span>
                </div>

                <div class="form-group">
                    <label>Estación <span class="text-muted" style="font-weight:400;">(opcional)</span></label>
                    <div id="wrapEstacion"></div>
                    <input type="hidden" name="usuario_estacion_id" id="usuario_estacion_id">
                    <span class="error" data-error="usuario_estacion_id"></span>
                </div>

                <div class="form-group">
                    <label>Estatus</label>
                    <select name="usuario_status" id="usuario_status">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Imagen (JPG/PNG/WEBP · máx 2MB · opcional)</label>
                    <input type="file" name="usuario_imagen" id="usuario_imagen"
                           accept="image/jpeg,image/png,image/webp">
                    <span class="error" data-error="usuario_imagen"></span>
                </div>

                <div class="form-group full" id="wrapPreview" style="display:none;">
                    <label>Imagen actual</label>
                    <div style="display:flex; align-items:center; gap:1rem;">
                        <img id="previewImg" src="" alt="Preview"
                             style="width:56px; height:56px; border-radius:50%; object-fit:cover; border:2px solid #e2e8f0;">
                        <span class="text-muted">Se reemplazará solo si subes una nueva imagen.</span>
                    </div>
                </div>
            </div>
                        <!-- ============================================ -->
            <!-- MENÚS ASIGNADOS (solo en modo edición)        -->
            <!-- ============================================ -->
            <div class="form-group full" id="blockMenus" style="display:none; margin-top:1.5rem;">
                <div class="menus-asign-header">
                    <label style="margin:0;">
                        <?= icon_tag('menu', 'menu', 'w-4 h-4') ?>
                        <span>Menús con acceso</span>
                    </label>
                    <div class="menus-asign-actions">
                        <button type="button" class="btn btn-ghost btn-sm" id="btnMenuExpandir">
                            <?= icon_tag('chevrons-down', 'chevrons-down', 'w-4 h-4') ?>
                            <span>Expandir</span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" id="btnMenuMarcarTodos">
                            <?= icon_tag('check-square', 'check-square', 'w-4 h-4') ?>
                            <span>Todos</span>
                        </button>
                        <button type="button" class="btn btn-ghost btn-sm" id="btnMenuLimpiar">
                            <?= icon_tag('square', 'square', 'w-4 h-4') ?>
                            <span>Ninguno</span>
                        </button>
                    </div>
                </div>

                <div class="menus-asign-help">
                    Marca los menús que este usuario puede ver en el sidebar.
                    Si no marcas ningún menú, heredará los del departamento.
                </div>

                <div id="menusTree" class="menus-asign-tree">
                    <div class="menus-asign-loading">Cargando menús...</div>
                </div>
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
<!-- TABLA                                        -->
<!-- ============================================ -->
<div class="card">
    <div class="dt-toolbar">
        <div class="dt-toolbar-left">
            <span class="dt-toolbar-label">Mostrar</span>
            <select id="filtroLimit" class="filter-select" style="min-width:70px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="dt-toolbar-label">registros</span>
        </div>

        <div class="dt-toolbar-right" style="gap:.5rem;">
            <select id="filtroStatus" class="filter-select">
                <option value="">Todos los estatus</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
            <div id="wrapFiltroRol" style="min-width:180px;"></div>
            <input type="hidden" id="filtroRol">
            <div id="wrapFiltroDepartamento" style="min-width:200px;"></div>
            <input type="hidden" id="filtroDepartamento">
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:60px;"></th>
                    <th>Usuario</th>
                    <th>Personal vinculado</th>
                    <th>Rol</th>
                    <th>Departamento</th>
                    <th>Estación</th>
                    <th>Estatus</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyUsuarios">
                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startUsuarios">0</strong>–<strong id="endUsuarios">0</strong>
            de <strong id="totalUsuarios">0</strong> registros
        </div>
        <div class="dt-pagination" id="paginationUsuarios"></div>
    </div>
</div>
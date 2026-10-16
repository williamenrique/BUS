<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">Personal</h2>
        <p class="page-subtitle">Gestión de empleados de la empresa</p>
    </div>
    <button id="btnNuevo" class="btn btn-primary">
        <?= icon_tag('user-plus', 'user-plus', 'w-4 h-4') ?>
        <span id="btnNuevoText">Nuevo Personal</span>
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
                <h3 id="formSectionTitle">Nuevo Personal</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Completa los datos para crear un nuevo registro
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleForm" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body" id="formSectionBody">
        <form id="formPersonal" novalidate>
            <input type="hidden" name="id_personal" id="id_personal">
            <input type="hidden" name="personal_tag" id="personal_tag" value="0">

           <div class="form-grid">
    <div class="form-group">
        <label>Cédula <span class="req">*</span></label>
        <input type="text" name="personal_cedula" id="personal_cedula"
               maxlength="15" autocomplete="off" data-uppercase>
        <span class="error" data-error="personal_cedula"></span>
    </div>
    <div class="form-group">
        <label>Nombre <span class="req">*</span></label>
        <input type="text" name="personal_nombre" id="personal_nombre"
               maxlength="45" autocomplete="off" data-uppercase>
        <span class="error" data-error="personal_nombre"></span>
    </div>
    <div class="form-group">
        <label>Apellido <span class="req">*</span></label>
        <input type="text" name="personal_apellido" id="personal_apellido"
               maxlength="45" autocomplete="off" data-uppercase>
        <span class="error" data-error="personal_apellido"></span>
    </div>
    <div class="form-group">
        <label>Teléfono</label>
        <input type="text" name="personal_tlf" id="personal_tlf"
               maxlength="45" autocomplete="off" data-uppercase>
    </div>

    <div class="form-group">
        <label>Cargo <span class="req">*</span></label>
        <select name="personal_cargo" id="personal_cargo"></select>
        <span class="error" data-error="personal_cargo"></span>
    </div>
    <div class="form-group">
        <label>Departamento</label>
        <select name="personal_departamento" id="personal_departamento"></select>
    </div>
    <div class="form-group">
        <label>Estatus</label>
        <select name="personal_status" id="personal_status">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
        </select>
    </div>
    <div class="form-group">
        <label>Email</label>
        <!-- SIN data-uppercase: el email va tal cual -->
        <input type="email" name="personal_email" id="personal_email" autocomplete="off">
        <span class="error" data-error="personal_email"></span>
    </div>

    <div class="form-group full">
        <label>Dirección</label>
        <textarea name="personal_direccion" id="personal_direccion"
                  rows="1" data-uppercase></textarea>
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

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body filters-row">
        <div class="filter-search">
            <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
            <input type="text" id="filtroBuscar" placeholder="Buscar por nombre, cédula o email...">
        </div>
        <select id="filtroStatus" class="filter-select">
            <option value="">Todos los estatus</option>
            <option value="1">Activos</option>
            <option value="0">Inactivos</option>
        </select>
        <select id="filtroDepartamento" class="filter-select">
            <option value="">Todos los departamentos</option>
        </select>
        <button id="btnLimpiarFiltros" class="btn btn-ghost">
            <?= icon_tag('x', 'x', 'w-4 h-4') ?>
            <span>Limpiar</span>
        </button>
    </div>
</div>

<!-- Tabla con DataTable -->
<div class="card">
    <!-- Toolbar (arriba) -->
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

        <div class="dt-toolbar-right">
            <select id="filtroStatus" class="filter-select">
                <option value="">Todos los estatus</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>

            <select id="filtroDepartamento" class="filter-select">
                <option value="">Todos los departamentos</option>
            </select>

            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar...">
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cédula</th>
                    <th>Nombre</th>
                    <th>Cargo</th>
                    <th>Departamento</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Estatus</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyPersonal">
                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Footer con info + paginación -->
    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startPersonal">0</strong>–<strong id="endPersonal">0</strong>
            de <strong id="totalPersonal">0</strong> registros
        </div>
        <div class="dt-pagination" id="paginationPersonal"></div>
    </div>
</div>
<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">Backup y Restauración</h2>
        <p class="page-subtitle">Respaldo y recuperación de la base de datos</p>
    </div>
    <button id="btnCrearBackup" class="btn btn-primary">
        <?= icon_tag('database', 'database', 'w-4 h-4') ?>
        <span id="btnCrearBackupText">Crear backup ahora</span>
    </button>
</div>

<!-- ============================================ -->
<!-- TARJETAS DE ESTADÍSTICAS                      -->
<!-- ============================================ -->
<div class="aceite-cards" style="grid-template-columns: repeat(4, 1fr); margin-bottom:1.5rem;">
    <div class="aceite-card blue">
        <div class="aceite-card-icon" style="background:#2563eb;">
            <?= icon_tag('file-archive', 'file-archive', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="statArchivos"><?= (int)($stats['archivos'] ?? 0) ?></strong>
            <span>Archivos .sql</span>
        </div>
    </div>
    <div class="aceite-card amber">
        <div class="aceite-card-icon" style="background:#f59e0b;">
            <?= icon_tag('hard-drive', 'hard-drive', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="statTamano"><?php
                $__tam = (int)($stats['tamano'] ?? 0);
                $__fmt = $__tam < 1024 ? $__tam.' B'
                    : ($__tam < 1048576 ? round($__tam/1024,1).' KB'
                    : ($__tam < 1073741824 ? round($__tam/1048576,1).' MB'
                    : round($__tam/1073741824,2).' GB'));
                ?>
            </strong>
            <span>Espacio total</span>
        </div>
    </div>
    <div class="aceite-card green">
        <div class="aceite-card-icon" style="background:#16a34a;">
            <?= icon_tag('check-circle', 'check-circle', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="statRestores"><?= (int)($stats['bd']['restores_ok'] ?? 0) ?></strong>
            <span>Restauraciones OK</span>
        </div>
    </div>
    <div class="aceite-card red">
        <div class="aceite-card-icon" style="background:#dc2626;">
            <?= icon_tag('alert-triangle', 'alert-triangle', 'w-6 h-6') ?>
        </div>
        <div>
            <strong id="statErrores"><?= (int)($stats['bd']['errores'] ?? 0) ?></strong>
            <span>Errores</span>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- SECCIÓN SUBIR .SQL                            -->
<!-- ============================================ -->
<section id="seccionSubir" class="card form-section collapsed">
    <header class="form-section-header" id="toggleSubir">
        <div class="form-section-title">
            <span id="formSectionIconWrap">
                <?= icon_tag('upload', 'upload', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formSectionTitle">Subir archivo .sql</h3>
                <p id="formSectionSubtitle" class="form-section-subtitle">
                    Máx. 200 MB · Solo se permite extensión .sql
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleSubir" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body" id="formSectionBody">
        <form id="formSubir" enctype="multipart/form-data">
            <div class="form-grid">
                <div class="form-group full">
                    <label>Archivo .sql</label>
                    <input type="file" name="archivo" id="archivoSql" accept=".sql">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" id="btnCancelarSubir" class="btn btn-ghost">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cancelar</span>
                </button>
                <button type="submit" id="btnGuardarSubir" class="btn btn-primary">
                    <?= icon_tag('upload', 'upload', 'w-4 h-4') ?>
                    <span class="btn-text">Subir archivo</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============================================ -->
<!-- TABLA DE HISTORIAL                            -->
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
            <select id="filtroTipo" class="filter-select">
                <option value="">Todos los tipos</option>
                <option value="manual">Manual</option>
                <option value="automatico">Automático</option>
                <option value="seguridad">Seguridad</option>
                <option value="subido">Subido</option>
            </select>
            <select id="filtroAccion" class="filter-select">
                <option value="">Todas las acciones</option>
                <option value="backup">Backup</option>
                <option value="restore">Restore</option>
            </select>
            <select id="filtroEstado" class="filter-select">
                <option value="">Todos los estados</option>
                <option value="ok">OK</option>
                <option value="error">Error</option>
                <option value="en_progreso">En progreso</option>
            </select>
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar archivo, usuario, mensaje...">
            </div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Archivo</th>
                    <th>Tamaño</th>
                    <th>Tipo</th>
                    <th>Acción</th>
                    <th>Estado</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                    <th>Duración</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyBackups">
                <tr><td colspan="9" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startBackups">0</strong>–<strong id="endBackups">0</strong>
            de <strong id="totalBackups">0</strong> registros
        </div>
        <div class="dt-pagination" id="paginationBackups"></div>
    </div>
</div>
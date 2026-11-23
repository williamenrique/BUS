<?php
$instId = $instId ?? inst_actual();
$instNombreFull = 'Institución #' . $instId;
try {
    $row = \App\Core\Database::getConnection()
        ->query("SELECT nombre FROM table_instituciones WHERE id_institucion = " . (int)$instId)
        ->fetch();
    if ($row && !empty($row['nombre'])) $instNombreFull = $row['nombre'];
} catch (\Throwable $e) {}
$instNombreCorto = inst_nombre_corto($instNombreFull);
?>

<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">
            Sistemas · Equipos y Mantenimiento
            <span class="badge badge-info" style="font-size:.7rem; vertical-align:middle;"
                  title="<?= e($instNombreFull) ?>">
                <?= e($instNombreCorto) ?>
            </span>
        </h2>
        <p class="page-subtitle">Ficha técnica de equipos, soporte, mantenimiento y reemplazo de componentes</p>
    </div>
    <div style="display:flex; gap:.5rem; flex-wrap:wrap; align-items:center;">
        <button type="button" id="btnReporteGlobalPdf" class="btn"
                style="background:#ffffff; color:#0f172a; border:1px solid #cbd5e1; box-shadow:0 1px 2px rgba(15,23,42,.05);"
                onmouseover="this.style.background='#f1f5f9'; this.style.borderColor='#94a3b8';"
                onmouseout="this.style.background='#ffffff'; this.style.borderColor='#cbd5e1';">
            <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
            <span>PDF Global</span>
        </button>
        <button type="button" id="btnNuevaIntervencionTop" class="btn"
                style="background:#0f766e; color:#ffffff; border:1px solid #0f766e; box-shadow:0 1px 2px rgba(15,118,110,.2);"
                onmouseover="this.style.background='#115e59'; this.style.borderColor='#115e59';"
                onmouseout="this.style.background='#0f766e'; this.style.borderColor='#0f766e';">
            <?= icon_tag('wrench', 'wrench', 'w-4 h-4') ?>
            <span>Registrar Soporte / Mant.</span>
        </button>
        <button type="button" id="btnNuevoEquipo" class="btn btn-primary">
            <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
            <span id="btnNuevoEquipoText">Nuevo Equipo</span>
        </button>
    </div>
</div>

<!-- ============================================ -->
<!-- SECCIÓN FORMULARIO EQUIPO / FICHA (colapsable) -->
<!-- ============================================ -->
<section id="seccionFormEquipo" class="card form-section collapsed">
    <header class="form-section-header" id="toggleFormEquipo">
        <div class="form-section-title">
            <span id="formEquipoIconWrap">
                <?= icon_tag('monitor', 'monitor', 'w-5 h-5') ?>
            </span>
            <div>
                <h3 id="formEquipoTitle">Nueva Ficha Técnica de Equipo</h3>
                <p id="formEquipoSubtitle" class="form-section-subtitle">
                    Completa los datos de hardware, red y asignación del equipo
                </p>
            </div>
        </div>
        <button type="button" class="form-section-toggle" id="btnToggleFormEquipo" title="Mostrar/Ocultar">
            <?= icon_tag('chevron-down', 'chevron-down', 'w-5 h-5') ?>
        </button>
    </header>

    <div class="form-section-body">
        <form id="formEquipo" novalidate>
            <input type="hidden" name="id_equipo" id="id_equipo">

            <div class="form-grid">
                <div class="form-group">
                    <label>Código / Etiqueta <span class="req">*</span></label>
                    <input type="text" name="codigo_equipo" id="codigo_equipo"
                           maxlength="40" autocomplete="off" data-uppercase placeholder="EJ. SYS-PC-001">
                    <span class="error" data-error="codigo_equipo"></span>
                </div>

                <div class="form-group">
                    <label>Tipo de equipo <span class="req">*</span></label>
                    <select name="tipo_equipo" id="tipo_equipo">
                        <option value="PC ESCRITORIO">PC Escritorio</option>
                        <option value="LAPTOP">Laptop</option>
                        <option value="SERVIDOR">Servidor</option>
                        <option value="IMPRESORA">Impresora</option>
                        <option value="SWITCH">Switch</option>
                        <option value="ROUTER">Router</option>
                        <option value="UPS">UPS</option>
                        <option value="OTRO">Otro</option>
                    </select>
                    <span class="error" data-error="tipo_equipo"></span>
                </div>

                <div class="form-group">
                    <label>Departamento</label>
                    <div id="wrapEqDepto"></div>
                    <input type="hidden" name="departamento_id" id="eq_departamento_id">
                </div>

                <div class="form-group">
                    <label>Responsable asignado</label>
                    <div id="wrapEqResponsable"></div>
                    <input type="hidden" name="responsable_id" id="eq_responsable_id">
                </div>

                <div class="form-group">
                    <label>Marca</label>
                    <input type="text" name="marca" id="marca" maxlength="60" data-uppercase placeholder="EJ. DELL, HP, LENOVO">
                </div>

                <div class="form-group">
                    <label>Modelo</label>
                    <input type="text" name="modelo" id="modelo" maxlength="80" data-uppercase placeholder="EJ. OPTIPLEX 7050">
                </div>

                <div class="form-group">
                    <label>Serial</label>
                    <input type="text" name="serial_equipo" id="serial_equipo" maxlength="100" data-uppercase>
                </div>

                <div class="form-group">
                    <label>Fecha de adquisición</label>
                    <input type="date" name="fecha_adquisicion" id="fecha_adquisicion">
                </div>

                <div class="form-group">
                    <label>Procesador</label>
                    <input type="text" name="procesador" id="procesador" maxlength="100" data-uppercase placeholder="EJ. INTEL CORE I5-8500">
                </div>

                <div class="form-group">
                    <label>Memoria RAM</label>
                    <input type="text" name="ram_gb" id="ram_gb" maxlength="40" data-uppercase placeholder="EJ. 8 GB DDR4">
                </div>

                <div class="form-group">
                    <label>Almacenamiento</label>
                    <input type="text" name="almacenamiento" id="almacenamiento" maxlength="100" data-uppercase placeholder="EJ. SSD 240 GB">
                </div>

                <div class="form-group">
                    <label>Sistema operativo</label>
                    <input type="text" name="sistema_operativo" id="sistema_operativo" maxlength="100" data-uppercase placeholder="EJ. WINDOWS 10 PRO / DEBIAN">
                </div>

                <div class="form-group">
                    <label>Nombre de host</label>
                    <input type="text" name="nombre_host" id="nombre_host" maxlength="80" data-uppercase>
                </div>

                <div class="form-group">
                    <label>Dirección IP</label>
                    <input type="text" name="direccion_ip" id="direccion_ip" maxlength="45" placeholder="Ej. 192.168.1.50">
                </div>

                <div class="form-group">
                    <label>Dirección MAC</label>
                    <input type="text" name="direccion_mac" id="direccion_mac" maxlength="30" data-uppercase placeholder="EJ. AA:BB:CC:DD:EE:FF">
                </div>

                <div class="form-group">
                    <label>Estatus del equipo</label>
                    <select name="status_equipo" id="status_equipo">
                        <option value="ACTIVO">Activo</option>
                        <option value="EN_MANTENIMIENTO">En mantenimiento</option>
                        <option value="INACTIVO">Inactivo</option>
                        <option value="BAJA">Baja</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Observaciones de ficha técnica</label>
                    <textarea name="observaciones" id="observaciones" rows="2"
                              data-uppercase placeholder="Notas técnicas, periféricos asociados, ubicación exacta..."></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" id="btnCancelarEquipo" class="btn btn-ghost">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cancelar</span>
                </button>
                <button type="submit" id="btnGuardarEquipo" class="btn btn-primary">
                    <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                    <span class="btn-text">Guardar</span>
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============================================ -->
<!-- MODAL FICHA TÉCNICA + BITÁCORA MANTENIMIENTO -->
<!-- ============================================ -->
<div class="modal-backdrop" id="modalFichaEquipo" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:900; overflow-y:auto; padding:1.5rem;">
    <div class="card" style="max-width:980px; margin:0 auto; padding:1.5rem; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:1rem; border-bottom:1px solid #e2e8f0; padding-bottom:1rem; margin-bottom:1rem;">
            <div>
                <h3 id="fichaTitulo" style="margin:0; font-size:1.15rem;">Ficha Técnica de Equipo</h3>
                <p id="fichaSubtitulo" class="text-muted" style="margin:.25rem 0 0; font-size:.85rem;">—</p>
            </div>
            <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                <button type="button" id="btnPdfFicha" class="btn btn-secondary btn-sm">
                    <?= icon_tag('file-text', 'file-text', 'w-4 h-4') ?>
                    <span>Exportar PDF</span>
                </button>
                <button type="button" id="btnCerrarFicha" class="btn btn-ghost btn-sm">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cerrar</span>
                </button>
            </div>
        </div>

        <div id="fichaDetalleGrid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(210px, 1fr)); gap:.75rem; margin-bottom:1.25rem; background:#f8fafc; padding:1rem; border-radius:.5rem; border:1px solid #e2e8f0;">
            <!-- Se rellena dinámicamente -->
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem; flex-wrap:wrap; gap:.5rem;">
            <h4 style="margin:0; font-size:1rem;">Soporte, Mantenimiento y Reemplazo de Componentes</h4>
            <button type="button" id="btnAgregarMantDesdeFicha" class="btn btn-primary btn-sm">
                <?= icon_tag('plus', 'plus', 'w-4 h-4') ?>
                <span>Nueva Intervención</span>
            </button>
        </div>

        <div class="table-wrap" style="max-height:320px; overflow-y:auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Diagnóstico / Acción</th>
                        <th>Reemplazo</th>
                        <th>Técnico</th>
                        <th>Estado</th>
                        <th class="text-right">Acción</th>
                    </tr>
                </thead>
                <tbody id="tbodyFichaMant">
                    <tr><td colspan="7" class="dt-loading">Sin datos</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- MODAL REGISTRAR SOPORTE / MANT. / REEMPLAZO  -->
<!-- ============================================ -->
<div class="modal-backdrop" id="modalMantenimiento" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:950; overflow-y:auto; padding:1.5rem;">
    <div class="card" style="max-width:780px; margin:0 auto; padding:1.5rem; position:relative;">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:.75rem; margin-bottom:1rem;">
            <div>
                <h3 style="margin:0; font-size:1.1rem;">Registrar Soporte / Mantenimiento / Reemplazo</h3>
                <p class="text-muted" style="margin:.2rem 0 0; font-size:.82rem;">Si el tipo es Reemplazo, los cambios de RAM/Disco/Procesador actualizan la ficha</p>
            </div>
            <button type="button" id="btnCerrarModalMant" class="btn btn-ghost btn-sm">
                <?= icon_tag('x', 'x', 'w-4 h-4') ?>
            </button>
        </div>

        <form id="formMantenimiento" novalidate>
            <input type="hidden" name="id_mantenimiento" id="id_mantenimiento">
            <div class="form-grid">
                <div class="form-group">
                    <label>Equipo <span class="req">*</span></label>
                    <div id="wrapMantEquipo"></div>
                    <input type="hidden" name="id_equipo" id="mant_id_equipo">
                    <span class="error" data-error="id_equipo"></span>
                </div>

                <div class="form-group">
                    <label>Tipo de intervención <span class="req">*</span></label>
                    <select name="tipo_intervencion" id="tipo_intervencion">
                        <option value="SOPORTE">Soporte técnico</option>
                        <option value="MANTENIMIENTO_PREVENTIVO" selected>Mantenimiento preventivo</option>
                        <option value="MANTENIMIENTO_CORRECTIVO">Mantenimiento correctivo</option>
                        <option value="REEMPLAZO">Reemplazo de componente</option>
                    </select>
                    <span class="error" data-error="tipo_intervencion"></span>
                </div>

                <div class="form-group">
                    <label>Fecha intervención <span class="req">*</span></label>
                    <input type="date" name="fecha_intervencion" id="fecha_intervencion" value="<?= date('Y-m-d') ?>">
                    <span class="error" data-error="fecha_intervencion"></span>
                </div>

                <div class="form-group">
                    <label>Próximo mantenimiento</label>
                    <input type="date" name="fecha_proximo_mant" id="fecha_proximo_mant">
                </div>

                <div class="form-group">
                    <label>Técnico responsable</label>
                    <div id="wrapMantTecnico"></div>
                    <input type="hidden" name="tecnico_id" id="mant_tecnico_id">
                </div>

                <div class="form-group">
                    <label>Estado de intervención</label>
                    <select name="estado_intervencion" id="estado_intervencion">
                        <option value="CERRADA" selected>Cerrada</option>
                        <option value="EN_PROCESO">En proceso</option>
                        <option value="ABIERTA">Abierta</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Solicitante / Usuario del equipo</label>
                    <input type="text" name="solicitante_nombre" id="solicitante_nombre" data-uppercase>
                </div>

                <div class="form-group">
                    <label>Estatus post-intervención del equipo</label>
                    <select name="status_equipo_post" id="status_equipo_post">
                        <option value="">Mantener estatus actual</option>
                        <option value="ACTIVO">Activo</option>
                        <option value="EN_MANTENIMIENTO">En mantenimiento</option>
                        <option value="INACTIVO">Inactivo</option>
                        <option value="BAJA">Baja</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label>Falla reportada</label>
                    <input type="text" name="falla_reportada" id="falla_reportada" data-uppercase placeholder="EJ. LENTITUD, NO ENCIENDE, RUIDO EN FAN...">
                </div>

                <div class="form-group full">
                    <label>Diagnóstico técnico <span class="req">*</span></label>
                    <textarea name="diagnostico" id="diagnostico" rows="2" data-uppercase required></textarea>
                    <span class="error" data-error="diagnostico"></span>
                </div>

                <div class="form-group full">
                    <label>Acción / trabajo realizado <span class="req">*</span></label>
                    <textarea name="accion_realizada" id="accion_realizada" rows="2" data-uppercase required></textarea>
                    <span class="error" data-error="accion_realizada"></span>
                </div>

                <div class="form-group">
                    <label>Componente reemplazado</label>
                    <select name="tipo_componente" id="tipo_componente">
                        <option value="">— Ninguno —</option>
                        <option value="RAM">Memoria RAM</option>
                        <option value="DISCO">Disco / Almacenamiento</option>
                        <option value="PROCESADOR">Procesador</option>
                        <option value="FUENTE">Fuente de poder</option>
                        <option value="MOTHERBOARD">Tarjeta madre</option>
                        <option value="PANTALLA">Pantalla / Monitor</option>
                        <option value="TECLADO">Teclado / Mouse</option>
                        <option value="BATERIA">Batería / UPS</option>
                        <option value="OTRO">Otro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Componente anterior</label>
                    <input type="text" name="componente_anterior" id="componente_anterior" data-uppercase>
                </div>

                <div class="form-group">
                    <label>Componente nuevo</label>
                    <input type="text" name="componente_nuevo" id="componente_nuevo" data-uppercase>
                </div>

                <div class="form-group">
                    <label>Serial componente nuevo</label>
                    <input type="text" name="serial_nuevo" id="serial_nuevo" data-uppercase>
                </div>

                <div class="form-group full">
                    <label>Observaciones adicionales</label>
                    <textarea name="observaciones" id="mant_observaciones" rows="1" data-uppercase></textarea>
                </div>
            </div>

            <div class="form-actions" style="margin-top:1rem;">
                <button type="button" id="btnCancelarModalMant" class="btn btn-ghost">
                    <?= icon_tag('x', 'x', 'w-4 h-4') ?>
                    <span>Cancelar</span>
                </button>
                <button type="submit" id="btnGuardarMant" class="btn btn-primary">
                    <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                    <span class="btn-text">Guardar intervención</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================ -->
<!-- TABLA PRINCIPAL DE EQUIPOS                   -->
<!-- ============================================ -->
<div class="card">
   <div class="dt-toolbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.6rem;">
        <div class="dt-toolbar-left" style="display:flex; align-items:center; gap:.4rem; flex:0 0 auto;">
            <span class="dt-toolbar-label">Mostrar</span>
            <select id="filtroLimit" class="filter-select" style="width:68px; min-width:68px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="dt-toolbar-label">equipos</span>
        </div>

        <div class="dt-toolbar-right" style="display:flex; align-items:center; justify-content:flex-end; flex-wrap:wrap; gap:.45rem; flex:1 1 auto;">
            <div id="wrapFiltroDepto" style="width:190px; min-width:190px; max-width:190px; flex:0 0 190px;"></div>
            <input type="hidden" id="filtroDepartamento">

            <select id="filtroTipo" class="filter-select" style="width:142px; min-width:130px; flex:0 0 auto;">
                <option value="">Todos los tipos</option>
                <option value="PC ESCRITORIO">PC Escritorio</option>
                <option value="LAPTOP">Laptop</option>
                <option value="SERVIDOR">Servidor</option>
                <option value="IMPRESORA">Impresora</option>
                <option value="SWITCH">Switch</option>
                <option value="ROUTER">Router</option>
                <option value="UPS">UPS</option>
                <option value="OTRO">Otro</option>
            </select>

            <select id="filtroStatus" class="filter-select" style="width:142px; min-width:130px; flex:0 0 auto;">
                <option value="">Todos los estatus</option>
                <option value="ACTIVO">Activo</option>
                <option value="EN_MANTENIMIENTO">En mantenimiento</option>
                <option value="INACTIVO">Inactivo</option>
                <option value="BAJA">Baja</option>
            </select>

            <div style="display:flex; align-items:center; gap:.3rem; flex:0 0 auto;">
                <input type="date" id="filtroFechaDesde" class="filter-select" title="Desde fecha" style="width:128px; min-width:128px; padding:.38rem .45rem;">
                <span class="dt-toolbar-label">—</span>
                <input type="date" id="filtroFechaHasta" class="filter-select" title="Hasta fecha" style="width:128px; min-width:128px; padding:.38rem .45rem;">
            </div>

            <div class="filter-search" style="width:185px; min-width:165px; max-width:200px; flex:0 1 185px;">
                <span class="filter-search-icon"><?= icon_tag('search', 'search', 'w-4 h-4') ?></span>
                <input type="text" id="filtroBuscar" placeholder="Buscar equipo, IP, serial..." style="width:100%;">
            </div>

            <button type="button" id="btnLimpiarFiltros" class="btn btn-sm"
                    title="Limpiar filtros"
                    style="background:#f8fafc; color:#475569; border:1px solid #cbd5e1; flex:0 0 auto;">
                <?= icon_tag('rotate-ccw', 'rotate-ccw', 'w-4 h-4') ?>
            </button>

            <button type="button" id="btnExportarTablaPdf" class="btn btn-sm"
                    title="Exportar a PDF los resultados filtrados en la tabla"
                    style="background:#1e293b; color:#ffffff; border:1px solid #1e293b; box-shadow:0 1px 2px rgba(15,23,42,.15); flex:0 0 auto; white-space:nowrap;">
                <?= icon_tag('file-down', 'file-down', 'w-4 h-4') ?>
                <span>Exportar PDF</span>
            </button>
        </div>
    </div>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Equipo / Specs</th>
                    <th>Departamento</th>
                    <th>Responsable</th>
                    <th>Red / Host</th>
                    <th class="text-center">Interv.</th>
                    <th class="text-center">Estatus</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody id="tbodyEquipos">
                <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
            </tbody>
        </table>
    </div>

    <div class="dt-footer">
        <div class="dt-info">
            Mostrando <strong id="startEquipos">0</strong>–<strong id="endEquipos">0</strong>
            de <strong id="totalEquipos">0</strong> equipos
        </div>
        <div class="dt-pagination" id="paginationEquipos"></div>
    </div>
</div>
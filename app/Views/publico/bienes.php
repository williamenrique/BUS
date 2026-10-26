<?php
/**
 * Vista pública · Bienes por Departamento
 * URL: /bienes-publico/i/{inst}?depto={opcional}
 *
 * Jerarquía:
 *   - Header minimal (logo + "Ir al sistema")
 *   - Hero centrado: nombre institución (negrita), icono, "Bienes del Departamento", nombre depto
 *   - Stats + botón PDF
 *   - DataTable propia (solo lectura)
 *   - Modal propio para detalle
 *
 * Sin select de departamento: la vista pública solo muestra lo que el QR indica.
 */
$pageTitle  = 'Bienes Públicos';
$mostrarNav = false;
require __DIR__ . '/inc/header.php';
?>

<div class="publico-wrap">

    <!-- ============================================================ -->
    <!-- HERO · Institución + departamento                            -->
    <!-- ============================================================ -->
    <section class="bn-hero">
        <div class="bn-hero-inst">
            <?= htmlspecialchars($nombre_institucion) ?>
        </div>

        <div class="bn-hero-icon">
            <i data-lucide="package"></i>
        </div>
        <p class="bn-hero-label">Bienes del Departamento</p>
        <h1 class="bn-hero-depto">
            <?= htmlspecialchars($nombre_depto !== '' ? $nombre_depto : 'Todos los departamentos') ?>
        </h1>
        <p class="bn-hero-meta">Consulta pública · Solo lectura</p>
    </section>

    <!-- ============================================================ -->
    <!-- STATS + BOTÓN PDF                                            -->
    <!-- ============================================================ -->
    <section class="pub-sec" style="margin-bottom:1.25rem;">
        <div class="pub-sec-body" style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
            <div class="pub-stats" style="margin:0; flex:1; min-width:200px;">
                <div class="pub-stat slate">
                    <div class="num" id="bnTotal">0</div>
                    <div class="lbl">Bienes registrados</div>
                </div>
            </div>
            <button class="btn btn-primary btn-sm" id="btnBnPdf" style="white-space:nowrap;">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                <span>Descargar PDF</span>
            </button>
        </div>
    </section>

    <!-- ============================================================ -->
    <!-- TABLA DE BIENES                                              -->
    <!-- ============================================================ -->
    <section class="pub-sec">
        <div class="pub-sec-head">
            <h3>
                <i data-lucide="list" class="w-5 h-5 text-primary"></i>
                Inventario
            </h3>
            <div class="filter-search" style="min-width:240px;">
                <span class="filter-search-icon">
                    <i data-lucide="search" class="w-4 h-4"></i>
                </span>
                <input type="text" id="bnSearch" placeholder="Buscar descripción, grupo, sección...">
            </div>
        </div>

        <div class="pub-sec-body" style="padding:0;">
            <div class="dt-toolbar" style="padding:.75rem 1.1rem;">
                <div class="dt-toolbar-left">
                    <span class="dt-toolbar-label">Mostrar</span>
                    <select id="bnLimit" class="filter-select" style="min-width:70px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span class="dt-toolbar-label">registros</span>
                </div>
            </div>

            <div class="table-wrap" style="max-height:600px; overflow-y:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Descripción</th>
                            <th>Grupo</th>
                            <th>Subgrupo</th>
                            <th>Sección</th>
                            <th>Estado</th>
                            <th>Adquisición</th>
                            <th class="text-center">Ver</th>
                        </tr>
                    </thead>
                    <tbody id="bnBody">
                        <tr><td colspan="8" class="dt-loading">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="dt-footer" style="padding:.75rem 1.1rem;">
                <div class="dt-info">
                    Mostrando <strong id="bnStart">0</strong>–<strong id="bnEnd">0</strong>
                    de <strong id="bnTotalFiltrados">0</strong> bienes
                </div>
                <div class="dt-pagination" id="bnPagination"></div>
            </div>
        </div>
    </section>

</div>

<!-- Modal propia · Detalle del bien -->
<div id="modalDetalleBien" class="pub-modal-backdrop">
    <div class="pub-modal" style="max-width:720px;">
        <div class="pub-modal-head">
            <h3>Detalle del Bien</h3>
            <button type="button" class="pub-modal-close" data-close="1">&times;</button>
        </div>
        <div class="pub-modal-body" id="modalDetalleBody">
            <div class="dt-loading">Cargando...</div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/inc/footer.php'; ?>
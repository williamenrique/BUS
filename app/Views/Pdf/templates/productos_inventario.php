<?php
/**
 * Template PDF · Reporte de Inventario de Productos
 *
 * Modos:
 *   - por_ubicacion (default): agrupa por ubicación
 *   - por_estado: agrupa por estado de stock (Sin stock / Stock bajo / Con stock)
 *
 * Variables:
 *   $title    — título del reporte
 *   $data     — items (array plano) o grupos (array asociativo)
 *   $mode     — 'por_ubicacion' | 'por_estado'
 *   $conteos  — solo en modo por_estado: [sin_stock, bajo, ok, total]
 */

$title = is_string($title ?? null) ? $title : 'Reporte de Inventario';
$mode  = $mode ?? 'por_ubicacion';

$rawData = $data ?? [];
if (is_string($rawData)) {
    $decoded = json_decode($rawData, true);
    $rawData = is_array($decoded) ? $decoded : [];
}
if (!is_array($rawData)) {
    $rawData = [];
}

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');

/* ============================================================
   MODO: POR ESTADO
   ============================================================ */
if ($mode === 'por_estado') {
    $grupos = $rawData;
    $conteos = $conteos ?? [];

    if (!is_array($grupos)) $grupos = [];

    // Asegurar los 3 grupos aunque vengan vacíos
    $orden = ['Sin stock', 'Stock bajo', 'Con stock'];
    $gruposOrdenados = [];
    foreach ($orden as $nombre) {
        $gruposOrdenados[$nombre] = is_array($grupos[$nombre] ?? null) ? $grupos[$nombre] : [];
    }

    $total = 0;
    foreach ($gruposOrdenados as $lista) $total += count($lista);
    ?>
    <div class="pdf-content">
        <h1 class="pdf-titulo"><?= $esc($title) ?></h1>
        <?php
        $filtro = $conteos['filtro'] ?? '';
        ?>
        <p class="pdf-subtitulo">
            <?php if ($filtro !== ''): ?>
                Mostrando: <strong><?= $esc($filtro) ?></strong>
                · Total: <strong><?= $total ?></strong> producto(s)
            <?php else: ?>
                Total: <strong><?= $total ?></strong> producto(s)
                · Sin stock: <strong><?= count($gruposOrdenados['Sin stock']) ?></strong>
                · Stock bajo: <strong><?= count($gruposOrdenados['Stock bajo']) ?></strong>
                · Con stock: <strong><?= count($gruposOrdenados['Con stock']) ?></strong>
            <?php endif; ?>
            · Generado el <?= date('d/m/Y H:i') ?>
        </p>

        <?php if ($total === 0): ?>
            <p class="text-center text-muted" style="padding:20px 0;">No hay productos para mostrar.</p>
        <?php else: ?>
            <?php foreach ($gruposOrdenados as $nombreGrupo => $productos): ?>
                <div class="section-box">
                    <div class="section-title">
                        <?= $esc($nombreGrupo) ?>
                        <span style="float:right; font-weight:normal; text-transform:none; letter-spacing:0;">
                            (<?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?>)
                        </span>
                    </div>

                    <?php if (empty($productos)): ?>
                        <p class="text-center text-muted" style="padding:12px 0; margin:0;">
                            Sin productos en este estado.
                        </p>
                    <?php else: ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th width="7%">ID</th>
                                    <th width="30%">Producto</th>
                                    <th width="13%">Tipo</th>
                                    <th width="18%">Proveedor</th>
                                    <th width="12%">Ubicación</th>
                                    <th width="10%" class="text-center">Present.</th>
                                    <th width="10%" class="text-right">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($productos as $p): ?>
                                    <tr>
                                        <td class="text-center"><?= $esc($p['id_producto'] ?? '') ?></td>
                                        <td class="text-upper"><?= $esc($p['producto'] ?? '') ?></td>
                                        <td><?= $esc($p['enlace_producto'] ?? '—') ?></td>
                                        <td><?= $esc($p['empresa_proveedor'] ?? '—') ?></td>
                                        <td><?= $esc($p['ubicacion'] ?? '—') ?></td>
                                        <td class="text-center"><?= $esc($p['present_producto'] ?? '') ?></td>
                                        <td class="text-right text-bold"><?= $num($p['cant_producto'] ?? 0) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return;
}

/* ============================================================
   MODO: POR UBICACIÓN (comportamiento actual)
   ============================================================ */
if (isset($rawData['data']) && is_array($rawData['data'])) {
    $items = $rawData['data'];
} else {
    $items = $rawData;
}

$grupos = [];
foreach ($items as $p) {
    if (!is_array($p)) continue;
    $ubi = trim((string)($p['ubicacion'] ?? '')) ?: 'Sin Ubicación Asignada';
    $grupos[$ubi][] = $p;
}
ksort($grupos);

$totalItems = count($items);
?>
<div class="pdf-content">
    <h1 class="pdf-titulo"><?= $esc($title) ?></h1>
    <p class="pdf-subtitulo">
        Total: <strong><?= $totalItems ?></strong> producto(s) · <?= count($grupos) ?> ubicación(es)
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <?php if (empty($grupos)): ?>
        <p class="text-center text-muted" style="padding:20px 0;">No hay productos para mostrar.</p>
    <?php else: ?>
        <?php foreach ($grupos as $ubicacion => $productos): ?>
            <div class="section-box">
                <div class="section-title">
                    Ubicación: <?= $esc($ubicacion) ?>
                    <span style="float:right; font-weight:normal; text-transform:none; letter-spacing:0;">
                        (<?= count($productos) ?> producto<?= count($productos) !== 1 ? 's' : '' ?>)
                    </span>
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="10%">ID</th>
                            <th width="55%">Producto</th>
                            <th width="20%" class="text-center">Presentación</th>
                            <th width="15%" class="text-right">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td class="text-center"><?= $esc($p['id_producto'] ?? '') ?></td>
                                <td class="text-upper"><?= $esc($p['producto'] ?? '') ?></td>
                                <td class="text-center"><?= $esc($p['present_producto'] ?? '') ?></td>
                                <td class="text-right text-bold"><?= $num($p['cant_producto'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php
/**
 * Template PDF · Reporte de Inventario de Productos
 */

$title = is_string($title ?? null) ? $title : 'Reporte de Inventario';

$rawData = $data ?? [];
if (is_string($rawData)) {
    $decoded = json_decode($rawData, true);
    $rawData = is_array($decoded) ? $decoded : [];
}
if (!is_array($rawData)) {
    $rawData = [];
}

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

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
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
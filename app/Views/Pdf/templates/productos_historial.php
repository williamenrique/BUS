<?php
/**
 * Template PDF · Historial de Productos (resumen global)
 * Recibe: $title (string), $data (array), $fechaInicio, $fechaFin, $meta
 */

$title = is_string($title ?? null) ? $title : 'Historial de Productos';

$rawData = $data ?? [];
if (is_string($rawData)) {
    $decoded = json_decode($rawData, true);
    $rawData = is_array($decoded) ? $decoded : [];
}
$items = is_array($rawData) ? $rawData : [];

$fechaInicio = $fechaInicio ?? '';
$fechaFin    = $fechaFin ?? '';

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
?>

<div class="pdf-content">

    <h1 class="pdf-titulo"><?= $esc($title) ?></h1>
    <p class="pdf-subtitulo">
        <?php if ($fechaInicio && $fechaFin): ?>
            Período: <strong><?= $esc($fechaInicio) ?></strong> al <strong><?= $esc($fechaFin) ?></strong>
        <?php else: ?>
            Resumen general de productos
        <?php endif; ?>
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <div class="section-box">
        <div class="section-title">Resumen de Productos (<?= count($items) ?>)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%">ID</th>
                    <th width="37%">Producto</th>
                    <th width="12%" class="text-right">Stock</th>
                    <th width="13%" class="text-right">Total Desp.</th>
                    <th width="15%" class="text-center">Primer Desp.</th>
                    <th width="15%" class="text-center">Último Desp.</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="6" class="text-center text-muted">No hay datos para mostrar.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $p): ?>
                        <?php if (!is_array($p)) continue; ?>
                        <tr>
                            <td class="text-center"><?= $esc($p['id_producto'] ?? '') ?></td>
                            <td class="text-upper"><?= $esc($p['producto'] ?? '') ?></td>
                            <td class="text-right"><?= $num($p['stock_actual'] ?? 0) ?></td>
                            <td class="text-right text-bold"><?= $num($p['total_despachado'] ?? 0) ?></td>
                            <td class="text-center" style="font-size:8px;"><?= $esc($p['primer_despacho'] ?? '—') ?></td>
                            <td class="text-center" style="font-size:8px;"><?= $esc($p['ultimo_despacho'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
<?php
/**
 * Template PDF · Historial de Productos
 *
 * Modos:
 *   - 'resumen'  → tabla global de todos los productos (default)
 *   - 'detalle'  → encabezado + timeline de un producto
 *
 * Variables esperadas:
 *   $title, $mode, $data, $producto (solo detalle), $fechaInicio, $fechaFin
 */
$title = is_string($title ?? null) ? $title : 'Historial de Productos';
$mode  = $mode ?? 'resumen';

$rawData = $data ?? [];
if (is_string($rawData)) {
    $decoded = json_decode($rawData, true);
    $rawData = is_array($decoded) ? $decoded : [];
}

$fechaInicio = $fechaInicio ?? '';
$fechaFin    = $fechaFin ?? '';

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$fecha = function ($f) {
    if (!$f) return '—';
    $t = strtotime($f);
    return $t ? date('d/m/Y', $t) : $f;
};

$rangoTxt = '';
if ($fechaInicio && $fechaFin) {
    $rangoTxt = 'Del ' . $fecha($fechaInicio) . ' al ' . $fecha($fechaFin);
} elseif ($fechaInicio) {
    $rangoTxt = 'Desde ' . $fecha($fechaInicio);
} elseif ($fechaFin) {
    $rangoTxt = 'Hasta ' . $fecha($fechaFin);
}
?>

<div class="pdf-content">

<?php if ($mode === 'detalle'): ?>
    <?php
        $producto = $producto ?? [];
        $items    = is_array($rawData) ? $rawData : [];
    ?>

    <h1 class="pdf-titulo"><?= $esc($producto['producto'] ?? 'Historial de Producto') ?></h1>
    <p class="pdf-subtitulo">
        <strong>ID:</strong> #<?= $esc($producto['id_producto'] ?? '') ?>
        · <strong>Presentación:</strong> <?= $esc($producto['present_producto'] ?? '—') ?>
        <?php if ($rangoTxt): ?> · <?= $esc($rangoTxt) ?><?php endif; ?>
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <div class="section-box">
        <div class="section-title">Resumen del producto</div>
        <table class="items-table" style="width:100%;">
            <tr>
                <td width="25%"><strong>Stock actual</strong></td>
                <td width="25%" class="text-right"><?= $num($producto['stock_actual'] ?? 0) ?> <?= $esc($producto['present_producto'] ?? '') ?></td>
                <td width="25%"><strong>Total despachado</strong></td>
                <td width="25%" class="text-right text-bold"><?= $num($producto['total_despachado'] ?? 0) ?></td>
            </tr>
            <tr>
                <td><strong>Total despachos</strong></td>
                <td class="text-right"><?= count($items) ?></td>
                <td><strong>Presentación</strong></td>
                <td class="text-right"><?= $esc($producto['present_producto'] ?? '—') ?></td>
            </tr>
        </table>
    </div>

    <div class="section-box">
        <div class="section-title">Despachos registrados (<?= count($items) ?>)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="6%">#</th>
                    <th width="12%">Fecha</th>
                    <th width="14%">Orden</th>
                    <th width="18%">Unidad</th>
                    <th width="22%">Operador</th>
                    <th width="14%">Mecánico</th>
                    <th width="14%" class="text-right">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Este producto no tiene despachos en el rango seleccionado.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $i => $ev): ?>
                        <?php if (!is_array($ev)) continue; ?>
                        <tr>
                            <td class="text-center"><?= $i + 1 ?></td>
                            <td class="text-center"><?= $fecha($ev['fecha_despacho'] ?? '') ?></td>
                            <td class="text-center">#<?= $esc($ev['numero_orden'] ?? $ev['id_despacho'] ?? '') ?></td>
                            <td>
                                <?= $esc($ev['id_unidad'] ?? '—') ?>
                                <?php if (!empty($ev['marca_unidad']) || !empty($ev['modelo_unidad'])): ?>
                                    <br><small><?= $esc(trim(($ev['marca_unidad'] ?? '') . ' ' . ($ev['modelo_unidad'] ?? ''))) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= $esc($ev['operador_nombre'] ?? '—') ?></td>
                            <td><?= $esc($ev['mecanico_nombre'] ?? '—') ?></td>
                            <td class="text-right text-bold"><?= $num($ev['cant_despacho'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <?php
        $items = is_array($rawData) ? $rawData : [];
    ?>

    <h1 class="pdf-titulo"><?= $esc($title) ?></h1>
    <p class="pdf-subtitulo">
        <?php if ($rangoTxt): ?>
            <?= $esc($rangoTxt) ?>
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
                    <th width="7%">ID</th>
                    <th width="35%">Producto</th>
                    <th width="12%" class="text-right">Stock</th>
                    <th width="14%" class="text-right">Total Desp.</th>
                    <th width="16%" class="text-center">Primer Desp.</th>
                    <th width="16%" class="text-center">Último Desp.</th>
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
                            <td class="text-center" style="font-size:8px;"><?= $fecha($p['primer_despacho'] ?? '') ?></td>
                            <td class="text-center" style="font-size:8px;"><?= $fecha($p['ultimo_despacho'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

</div>
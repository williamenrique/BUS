<?php
/**
 * Template PDF · Compras Costeadas filtradas por rango de fechas
 * Recibe: $fecha_desde, $fecha_hasta, $ordenes (array de despachos costeados)
 */

$ordenes     = $ordenes ?? [];
$fechaDesde  = $fecha_desde ?? '';
$fechaHasta  = $fecha_hasta ?? '';

if (is_string($ordenes)) {
    $decoded = json_decode($ordenes, true);
    $ordenes = is_array($decoded) ? $decoded : [];
}
if (!is_array($ordenes)) $ordenes = [];

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$numBs = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');

$totalDivisa = 0;
$totalBsNominal = 0;
foreach ($ordenes as $o) {
    $totalDivisa     += (float)($o['total_divisa'] ?? 0);
    $totalBsNominal  += (float)($o['total_bs'] ?? 0);
}
?>

<div class="pdf-content">

    <h1 class="pdf-titulo">Compras Costeadas</h1>
    <p class="pdf-subtitulo">
        <?php if ($fechaDesde && $fechaHasta): ?>
            Período: <strong><?= $esc(date('d/m/Y', strtotime($fechaDesde))) ?></strong>
            al <strong><?= $esc(date('d/m/Y', strtotime($fechaHasta))) ?></strong>
        <?php elseif ($fechaDesde): ?>
            Desde: <strong><?= $esc(date('d/m/Y', strtotime($fechaDesde))) ?></strong>
        <?php elseif ($fechaHasta): ?>
            Hasta: <strong><?= $esc(date('d/m/Y', strtotime($fechaHasta))) ?></strong>
        <?php else: ?>
            Todas las órdenes costeadas
        <?php endif; ?>
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <!-- Resumen -->
    <div class="section-box no-break">
        <div class="section-title">Resumen</div>
        <table width="100%" cellpadding="4" cellspacing="0" style="font-size:10px;">
            <tr>
                <td width="33%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Órdenes</div>
                    <div style="font-size:18px; font-weight:900;"><?= count($ordenes) ?></div>
                </td>
                <td width="34%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Total $</div>
                    <div style="font-size:18px; font-weight:900; color:#1e40af;">$ <?= $num($totalDivisa) ?></div>
                </td>
                <td width="33%" style="text-align:center;">
                    <div class="label-min">Suma nominal Bs <span style="color:#dc2626;">(*)</span></div>
                    <div style="font-size:13px; font-weight:700; color:#64748b;">Bs <?= $numBs($totalBsNominal) ?></div>
                </td>
            </tr>
        </table>
        <p style="font-size:7px; color:#94a3b8; text-align:center; margin:6px 0 0;">
            (*) Solo referencia. Las órdenes pueden tener distintas tasas de cambio,
            por lo que la suma en bolívares no representa un monto consolidado real.
        </p>
    </div>

    <!-- Tabla detallada -->
    <div class="section-box">
        <div class="section-title">Detalle de Órdenes</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="10%">N° Orden</th>
                    <th width="11%">Fecha</th>
                    <th width="13%">Unidad</th>
                    <th width="16%">Marca / Modelo</th>
                    <th width="8%" class="text-center">Art.</th>
                    <th width="14%" class="text-right">Monto $</th>
                    <th width="28%" class="text-right">Monto Bs (tasa)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ordenes)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No hay órdenes costeadas en el rango.</td></tr>
                <?php else: ?>
                    <?php foreach ($ordenes as $o): ?>
                        <?php
                            $numOrden = str_pad((string)($o['numero_orden'] ?? $o['id_despacho'] ?? '0'), 6, '0', STR_PAD_LEFT);
                            $fechaOrd = !empty($o['fecha_despacho']) ? date('d/m/Y', strtotime($o['fecha_despacho'])) : '—';

                            // Tasa efectiva: total_bs / total_divisa (si hay divisa)
                            $td = (float)($o['total_divisa'] ?? 0);
                            $tb = (float)($o['total_bs'] ?? 0);
                            $tasa = $td > 0 ? ($tb / $td) : 0;
                        ?>
                        <tr>
                            <td class="text-center text-bold">#<?= $esc($numOrden) ?></td>
                            <td class="text-center" style="font-size:8px;"><?= $esc($fechaOrd) ?></td>
                            <td class="text-bold"><?= $esc($o['id_unidad'] ?? '—') ?></td>
                            <td><?= $esc(trim(($o['marca_unidad'] ?? '') . ' ' . ($o['modelo_unidad'] ?? ''))) ?></td>
                            <td class="text-center"><?= (int)($o['articulos_costeados'] ?? 0) ?></td>
                            <td class="text-right">$ <?= $num($td) ?></td>
                            <td class="text-right">
                                Bs <?= $numBs($tb) ?>
                                <?php if ($tasa > 0): ?>
                                    <span class="text-muted" style="font-size:8px;">
                                        (tasa <?= $num($tasa) ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#e2e8f0;">
                    <td colspan="5" class="text-right text-bold" style="font-size:9px;">TOTAL:</td>
                    <td class="text-right text-bold" style="font-size:11px;">$ <?= $num($totalDivisa) ?></td>
                    <td class="text-right" style="font-size:8px; color:#64748b;">
                        Ver nota (*)
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>
<?php
/**
 * Template PDF · Orden Costeada (individual)
 * Recibe: $orden (array con info + articulos + totales), $meta
 */

$o = $orden ?? [];
$info = $o['info'] ?? [];
$arts = $o['articulos'] ?? [];

$esc   = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num   = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$fecha = !empty($info['fecha_despacho']) ? date('d/m/Y', strtotime($info['fecha_despacho'])) : '—';
$idOrden = str_pad((string)($info['numero_orden'] ?? $o['id'] ?? '0'), 6, '0', STR_PAD_LEFT);

$totalDivisa = (float)($o['total_divisa'] ?? 0);
$totalBs     = (float)($o['total_bs'] ?? 0);
?>

<div class="pdf-content">

    <h1 class="pdf-titulo">Orden Costeada</h1>
    <p class="pdf-subtitulo">N° <?= $idOrden ?> · <?= $fecha ?></p>

    <div class="section-box">
        <div class="section-title">Información de la Unidad</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="25%">
                        <span class="label-min">Unidad</span><br>
                        <span class="val-text"><?= $esc($info['id_unidad'] ?? '—') ?></span>
                    </td>
                    <td width="25%">
                        <span class="label-min">Marca</span><br>
                        <span class="val-text"><?= $esc($info['marca_unidad'] ?? '—') ?></span>
                    </td>
                    <td width="25%">
                        <span class="label-min">Modelo</span><br>
                        <span class="val-text"><?= $esc($info['modelo_unidad'] ?? '—') ?></span>
                    </td>
                    <td width="25%">
                        <span class="label-min">VIN</span><br>
                        <span class="val-text"><?= $esc($info['vim_unidad'] ?? '—') ?></span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-box no-break">
        <div class="section-title">Artículos Costeados</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="38%">Artículo</th>
                    <th width="14%" class="text-right">Cantidad</th>
                    <th width="14%" class="text-right">Tasa (Bs/$)</th>
                    <th width="17%" class="text-right">Monto $</th>
                    <th width="17%" class="text-right">Monto Bs</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($arts)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No hay artículos costeados.</td></tr>
                <?php else: ?>
                    <?php foreach ($arts as $a): ?>
                        <tr>
                            <td class="text-upper">
                                <?= $esc($a['producto'] ?? '') ?>
                                <?php if (!empty($a['present_producto'])): ?>
                                    <span class="text-muted" style="font-size:8px;">
                                        (<?= $esc($a['present_producto']) ?>)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= $num($a['cant_despacho'] ?? 0) ?></td>
                            <td class="text-right"><?= $num($a['tasa_dia'] ?? 0) ?></td>
                            <td class="text-right text-bold">$ <?= $num($a['monto_divisa'] ?? 0) ?></td>
                            <td class="text-right">Bs <?= $num($a['monto_bs'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#e2e8f0;">
                    <td colspan="3" class="text-right text-bold" style="font-size:9px;">TOTALES:</td>
                    <td class="text-right text-bold" style="font-size:10px;">$ <?= $num($totalDivisa) ?></td>
                    <td class="text-right text-bold" style="font-size:10px;">Bs <?= $num($totalBs) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <table class="firmas-table">
        <tr>
            <td class="firma-box" width="50%">
                <div class="firma-linea">Responsable (Compras)</div>
                <p style="font-size:8px; margin:2px 0 0; color:#64748b;">Firma</p>
            </td>
            <td class="firma-box" width="50%">
                <div class="firma-linea">VoBo (Administración)</div>
                <p style="font-size:8px; margin:2px 0 0; color:#64748b;">Firma y Sello</p>
            </td>
        </tr>
    </table>

</div>
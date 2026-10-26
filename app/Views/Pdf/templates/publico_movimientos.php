<?php
/**
 * Template PDF · Orden de Despacho (Público)
 */
$o = $orden ?? [];
$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$fecha = !empty($o['fecha_despacho']) ? date('d/m/Y', strtotime($o['fecha_despacho'])) : '—';
$idOrden = str_pad((string)($o['numero_orden'] ?? $o['id_despacho'] ?? '0'), 6, '0', STR_PAD_LEFT);
?>

<div class="pdf-content">
    <h1 class="pdf-titulo">Orden de Despacho</h1>
    <p class="pdf-subtitulo">N° <?= $idOrden ?> · <?= $fecha ?></p>

    <div class="section-box">
        <div class="section-title">Información de la Unidad</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="25%"><span class="label-min">Unidad</span><br><span class="val-text"><?= $esc($o['id_unidad'] ?? '—') ?></span></td>
                    <td width="25%"><span class="label-min">Marca</span><br><span class="val-text"><?= $esc($o['marca_unidad'] ?? '—') ?></span></td>
                    <td width="25%"><span class="label-min">Modelo</span><br><span class="val-text"><?= $esc($o['modelo_unidad'] ?? '—') ?></span></td>
                    <td width="25%"><span class="label-min">VIN</span><br><span class="val-text"><?= $esc($o['vim_unidad'] ?? '—') ?></span></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-box">
        <div class="section-title">Personal Involucrado</div>
        <div class="section-content">
            <table width="100%">
                <tr>
                    <td width="33%"><span class="label-min">Operador</span><br><span class="val-text"><?= $esc($o['operador_nombre'] ?? '—') ?></span></td>
                    <td width="33%"><span class="label-min">Mecánico</span><br><span class="val-text"><?= $esc($o['mecanico_nombre'] ?? '—') ?></span></td>
                    <td width="33%"><span class="label-min">Despachador</span><br><span class="val-text"><?= $esc($o['despachador_nombre'] ?? '—') ?></span></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-box no-break">
        <div class="section-title">Artículos Despachados</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="15%" class="text-center">Código</th>
                    <th width="50%">Descripción</th>
                    <th width="15%" class="text-center">Cantidad</th>
                    <th width="20%" class="text-center">Ubicación</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($o['articulos'])): ?>
                    <tr><td colspan="4" class="text-center text-muted">No se despacharon artículos.</td></tr>
                <?php else: ?>
                    <?php foreach ($o['articulos'] as $a): ?>
                        <tr>
                            <td class="text-center"><?= $esc($a['id_producto'] ?? '') ?></td>
                            <td class="text-upper"><?= $esc($a['producto'] ?? '') ?></td>
                            <td class="text-center text-bold"><?= $esc($a['cant_despacho'] ?? '') ?></td>
                            <td class="text-center"><?= $esc($a['ubicacion'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-box" style="margin-top:14px;">
        <div class="section-title">Observaciones</div>
        <div class="section-content">
            <p style="font-size:9px; margin:0;">
                <?= !empty($o['observacion']) ? nl2br($esc($o['observacion'])) : '<span class="text-muted">Ninguna.</span>' ?>
            </p>
        </div>
    </div>
</div>
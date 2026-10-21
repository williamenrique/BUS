<?php
/**
 * Template PDF · Reporte de Operatividad de Flota
 * Recibe: $summaryData (array de grupos), $totales (opcional), $meta
 */

$groups = $summaryData ?? [];

// Totales generales
$totOp   = array_sum(array_column($groups, 'operativas'));
$totInop = array_sum(array_column($groups, 'inoperativas'));
$totCrit = array_sum(array_column($groups, 'criticas'));
$totCant = array_sum(array_column($groups, 'cantidad'));

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
?>

<div class="pdf-content">

    <h1 class="pdf-titulo">Reporte de Operatividad de Flota</h1>
    <p class="pdf-subtitulo">Generado el <?= date('d/m/Y H:i') ?></p>

    <div class="section-box no-break">
        <div class="section-title">Resumen General</div>
        <table width="100%" cellpadding="4" cellspacing="0" style="font-size:10px;">
            <tr>
                <td width="25%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Operativas</div>
                    <div style="font-size:18px; font-weight:900; color:#16a34a;"><?= $totOp ?></div>
                </td>
                <td width="25%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Inoperativas</div>
                    <div style="font-size:18px; font-weight:900; color:#b45309;"><?= $totInop ?></div>
                </td>
                <td width="25%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Críticas</div>
                    <div style="font-size:18px; font-weight:900; color:#dc2626;"><?= $totCrit ?></div>
                </td>
                <td width="25%" style="text-align:center;">
                    <div class="label-min">Total Flota</div>
                    <div style="font-size:18px; font-weight:900; color:#0f172a;"><?= $totCant ?></div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section-box">
        <div class="section-title">Detalle por Grupo</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="45%">Grupo (Modelo / Combustible / Transmisión)</th>
                    <th width="12%" class="text-center">Cantidad</th>
                    <th width="14%" class="text-center">Operativas</th>
                    <th width="14%" class="text-center">Inoperativas</th>
                    <th width="15%" class="text-center">Críticas</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($groups)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No hay datos para mostrar.</td></tr>
                <?php else: ?>
                    <?php foreach ($groups as $g): ?>
                        <tr>
                            <td class="text-upper"><?= $esc($g['groupName'] ?? '') ?></td>
                            <td class="text-center text-bold"><?= (int)($g['cantidad'] ?? 0) ?></td>
                            <td class="text-center" style="color:#16a34a; font-weight:700;"><?= (int)($g['operativas'] ?? 0) ?></td>
                            <td class="text-center" style="color:#b45309; font-weight:700;"><?= (int)($g['inoperativas'] ?? 0) ?></td>
                            <td class="text-center" style="color:#dc2626; font-weight:700;"><?= (int)($g['criticas'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
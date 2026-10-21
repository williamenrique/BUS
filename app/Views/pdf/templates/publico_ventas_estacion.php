<?php
/**
 * Template PDF · Ventas de Estación (Público)
 * Header fijo "SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY"
 * (porque este PDF NO usa el header global del sistema)
 */

$data            = $data ?? [];
$fechaDesde      = $fechaInicio ?? '';
$fechaHasta      = $fechaFin ?? '';
$estacionNombre  = $estacionNombre ?? 'Todas las estaciones';

if (is_string($data)) $data = json_decode($data, true) ?: [];
if (!is_array($data)) $data = [];

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v, $d = 2) => number_format((float)($v ?? 0), $d, ',', '.');

$totLitros = 0; $totVentas = 0; $totBs = 0;
$totGas = 0; $totDie = 0;
$aggComb = []; $aggVeh = []; $aggPago = [];

foreach ($data as $item){
    $totLitros += (float)($item['total_litros'] ?? 0);
    $totVentas += (int)($item['total_ventas'] ?? 0);
    $totBs     += (float)($item['total_bs'] ?? 0);
    $totGas    += (float)($item['litros_gasolina'] ?? 0);
    $totDie    += (float)($item['litros_diesel'] ?? 0);

    foreach ($item['tipos_combustible'] ?? [] as $tc){
        $k = $tc['tipo'] ?? 'Otros';
        if (!isset($aggComb[$k])) $aggComb[$k] = ['cant'=>0,'litros'=>0,'monto'=>0];
        $aggComb[$k]['cant']   += (int)($tc['cantidad'] ?? 0);
        $aggComb[$k]['litros'] += (float)($tc['litros'] ?? 0);
        $aggComb[$k]['monto']  += (float)($tc['monto'] ?? 0);
    }
    foreach ($item['tipos_vehiculo'] ?? [] as $tv){
        $k = $tv['tipo'] ?? 'Otros';
        if (!isset($aggVeh[$k])) $aggVeh[$k] = ['cant'=>0,'litros'=>0];
        $aggVeh[$k]['cant']   += (int)($tv['cantidad'] ?? 0);
        $aggVeh[$k]['litros'] += (float)($tv['litros'] ?? 0);
    }
    foreach ($item['tipos_pago'] ?? [] as $tp){
        $k = $tp['tipo'] ?? 'Otros';
        if (!isset($aggPago[$k])) $aggPago[$k] = ['cant'=>0,'monto'=>0];
        $aggPago[$k]['cant']  += (int)($tp['cantidad'] ?? 0);
        $aggPago[$k]['monto'] += (float)($tp['monto'] ?? 0);
    }
}
?>

<div class="pdf-content">

    <!-- Header fijo (este PDF NO usa el header global) -->
    <h1 class="pdf-titulo" style="font-size:13px; font-weight:900; letter-spacing:.03em;">
        SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY
    </h1>
    <h2 style="text-align:center; font-size:11px; font-weight:700; color:#1e40af; margin:3px 0;">
        VENTAS DE ESTACIÓN
    </h2>
    <p class="pdf-subtitulo">
        <?php if ($fechaDesde && $fechaHasta): ?>
            Período: <strong><?= $esc(date('d/m/Y', strtotime($fechaDesde))) ?></strong>
            al <strong><?= $esc(date('d/m/Y', strtotime($fechaHasta))) ?></strong>
        <?php endif; ?>
        · Estación: <strong><?= $esc($estacionNombre) ?></strong>
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <!-- Resumen general -->
    <div class="section-box no-break">
        <div class="section-title">Resumen General</div>
        <table width="100%" style="font-size:10px;">
            <tr>
                <td width="25%" class="text-center"><div class="label-min">Litros</div><div style="font-size:14px; font-weight:900;"><?= $num($totLitros) ?> L</div></td>
                <td width="25%" class="text-center"><div class="label-min">Ventas</div><div style="font-size:14px; font-weight:900;"><?= number_format($totVentas) ?></div></td>
                <td width="25%" class="text-center"><div class="label-min">Gasolina</div><div style="font-size:13px; font-weight:700; color:#b45309;"><?= $num($totGas) ?> L</div></td>
                <td width="25%" class="text-center"><div class="label-min">Diésel</div><div style="font-size:13px; font-weight:700; color:#0f172a;"><?= $num($totDie) ?> L</div></td>
            </tr>
            <tr>
                <td colspan="4" class="text-center" style="padding-top:8px;">
                    <div class="label-min">Total Bs</div>
                    <div style="font-size:18px; font-weight:900; color:#166534;"><?= $num($totBs) ?> Bs</div>
                </td>
            </tr>
        </table>
    </div>

    <?php if ($aggComb): ?>
    <div class="section-box">
        <div class="section-title">Por Tipo de Combustible</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Combustible</th>
                    <th class="text-center">Ventas</th>
                    <th class="text-right">Litros</th>
                    <th class="text-right">Monto Bs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aggComb as $k => $v): ?>
                    <tr>
                        <td><strong><?= $esc($k) ?></strong></td>
                        <td class="text-center"><?= number_format($v['cant']) ?></td>
                        <td class="text-right"><?= $num($v['litros']) ?> L</td>
                        <td class="text-right"><?= $num($v['monto']) ?> Bs</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($aggPago): ?>
    <div class="section-box">
        <div class="section-title">Por Tipo de Pago</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Tipo de Pago</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Monto Bs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($aggPago as $k => $v): ?>
                    <tr>
                        <td><strong><?= $esc($k) ?></strong></td>
                        <td class="text-center"><?= number_format($v['cant']) ?></td>
                        <td class="text-right"><?= $num($v['monto']) ?> Bs</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="section-box">
        <div class="section-title">Detalle por Fecha y Estación</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estación</th>
                    <th class="text-center">Ventas</th>
                    <th class="text-right">Litros</th>
                    <th class="text-right">Gasolina</th>
                    <th class="text-right">Diésel</th>
                    <th class="text-right">Monto Bs</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Sin datos</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $item): ?>
                        <tr>
                            <td class="text-center" style="font-size:8px;"><?= $esc(date('d/m/Y', strtotime($item['fecha'] ?? 'now'))) ?></td>
                            <td><?= $esc($item['estacion'] ?? '') ?></td>
                            <td class="text-center"><?= (int)($item['total_ventas'] ?? 0) ?></td>
                            <td class="text-right"><?= $num($item['total_litros'] ?? 0) ?> L</td>
                            <td class="text-right"><?= $num($item['litros_gasolina'] ?? 0) ?> L</td>
                            <td class="text-right"><?= $num($item['litros_diesel'] ?? 0) ?> L</td>
                            <td class="text-right text-bold"><?= $num($item['total_bs'] ?? 0) ?> Bs</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background:#e2e8f0;">
                    <td colspan="2" class="text-right text-bold">TOTALES:</td>
                    <td class="text-center text-bold"><?= number_format($totVentas) ?></td>
                    <td class="text-right text-bold"><?= $num($totLitros) ?> L</td>
                    <td class="text-right text-bold"><?= $num($totGas) ?> L</td>
                    <td class="text-right text-bold"><?= $num($totDie) ?> L</td>
                    <td class="text-right text-bold"><?= $num($totBs) ?> Bs</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
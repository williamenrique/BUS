<?php
/**
 * Template PDF · Cierre del Día (Estación)
 */

$c         = $cierre ?? [];
$ventas    = $ventas ?? [];
$seccion   = $seccion ?? 'resumen';

if (is_string($ventas)) {
    $decoded = json_decode($ventas, true);
    $ventas = is_array($decoded) ? $decoded : [];
}

if ($seccion === 'gasolina') {
    $ventas = array_values(array_filter($ventas, function ($v) {
        $n = mb_strtolower($v['tipo_combustible'] ?? '');
        return strpos($n, 'gasolina') !== false;
    }));
} elseif ($seccion === 'diesel') {
    $ventas = array_values(array_filter($ventas, function ($v) {
        $n = mb_strtolower($v['tipo_combustible'] ?? '');
        return strpos($n, 'diesel') !== false || strpos($n, 'diésel') !== false;
    }));
}

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');
$fechaFmt = !empty($c['fecha_cierre']) ? date('d/m/Y', strtotime($c['fecha_cierre'])) : '—';
$estacion = $c['estacion'] ?? 'ESTACIÓN';
$empleado = trim(($c['usuario_nombres'] ?? '') . ' ' . ($c['usuario_apellidos'] ?? '')) ?: '—';

$totLitros = 0; $totEfectivo = 0; $totDebito = 0; $totDivisa = 0; $totGeneralBs = 0;
$cantAuto = $cantMoto = $cantCamion = 0;
$litrosAuto = $litrosMoto = $litrosCamion = 0;
$pagoDivisa = $pagoEfectivo = $pagoDebito = 0;
$montoDivisaTotal = $montoEfectivoTotal = $montoDebitoTotal = 0;
$combustibles = [];

foreach ($ventas as $v) {
    $litros    = (float)($v['cantidad_litros'] ?? 0);
    $efectivob = (float)($v['efectivob'] ?? 0);
    $debito    = (float)($v['tarjeta_debito'] ?? 0);
    $divisa    = (float)($v['monto_divisa'] ?? 0);

    $totLitros += $litros;
    $totDebito += $debito;
    $totDivisa += $divisa;
    $totEfectivo += $efectivob;
    $totGeneralBs += $efectivob + $debito;

    $tv = mb_strtolower($v['tipo_vehiculo'] ?? '');
    if (strpos($tv, 'moto') !== false) { $cantMoto++; $litrosMoto += $litros; }
    elseif (strpos($tv, 'camion') !== false || strpos($tv, 'camión') !== false) { $cantCamion++; $litrosCamion += $litros; }
    elseif (strpos($tv, 'auto') !== false || strpos($tv, 'carro') !== false) { $cantAuto++; $litrosAuto += $litros; }

    $tp = mb_strtolower($v['tipo_pago'] ?? '');
    if (strpos($tp, 'divisa') !== false) { $pagoDivisa++; $montoDivisaTotal += $divisa; }
    elseif (strpos($tp, 'efectivo') !== false) { $pagoEfectivo++; $montoEfectivoTotal += $efectivob; }
    elseif (strpos($tp, 'débito') !== false || strpos($tp, 'debito') !== false || strpos($tp, 'punto') !== false || strpos($tp, 'tarjeta') !== false) {
        $pagoDebito++; $montoDebitoTotal += $debito;
    }

    $nombreComb = $v['tipo_combustible'] ?? 'Otros';
    if (!isset($combustibles[$nombreComb])) {
        $combustibles[$nombreComb] = ['cant'=>0,'litros'=>0,'monto_bs'=>0];
    }
    $combustibles[$nombreComb]['cant']++;
    $combustibles[$nombreComb]['litros'] += $litros;
    $combustibles[$nombreComb]['monto_bs'] += $efectivob + $debito;
}

$subtitulo = [
    'resumen'  => 'RESUMEN GENERAL',
    'ventas'   => 'LISTADO UNIFICADO DE VENTAS',
    'gasolina' => 'LISTADO DE VENTAS - GASOLINA',
    'diesel'   => 'LISTADO DE VENTAS - DIESEL',
][$seccion] ?? 'RESUMEN GENERAL';
?>

<style>
    .est-header { text-align: center; margin-bottom: 18px; }
    .est-header .titulo    { font-size: 13px; font-weight: 900; letter-spacing: .03em; color: #0f172a; text-transform: uppercase; }
    .est-header .libro     { font-size: 11px; font-weight: 700; color: #1e40af; margin-top: 3px; }
    .est-header .subtitulo { font-size: 9px;  color: #64748b; margin-top: 4px; letter-spacing: .08em; }
    .est-header .empleado  { font-size: 9px;  color: #475569; margin-top: 5px; }

    .est-info { display: table; width: 100%; font-size: 9px; margin-bottom: 10px; color: #334155; }
    .est-info .izq { display: table-cell; text-align: left; }

    .est-kpi-row { width: 100%; margin-bottom: 12px; }
    .est-kpi { display: inline-block; width: 32%; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 8px; text-align: center; vertical-align: top; margin-right: 1%; }
    .est-kpi .num { font-size: 16px; font-weight: 900; color: #1e40af; display: block; }
    .est-kpi .lbl { font-size: 8px; color: #475569; text-transform: uppercase; letter-spacing: .04em; }

    .est-tabla { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    .est-tabla th { background: #1e40af; color: #fff; padding: 5px 6px; font-size: 7.5px; text-transform: uppercase; text-align: left; }
    .est-tabla th.r, .est-tabla td.r { text-align: right; }
    .est-tabla th.c, .est-tabla td.c { text-align: center; }
    .est-tabla td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 8.5px; }
    .est-tabla tr:nth-child(even) td { background: #f8fafc; }
    .est-tabla .total td { background: #dbeafe; font-weight: 700; border-top: 2px solid #1e40af; }

    .est-sec-title { font-size: 9.5px; font-weight: 800; color: #1e40af; background: #eff6ff; padding: 5px 8px; border-left: 3px solid #1e40af; margin: 12px 0 6px; text-transform: uppercase; letter-spacing: .04em; }

    .est-total-final { background: #dcfce7; border: 2px solid #16a34a; border-radius: 6px; padding: 12px; text-align: center; margin-top: 15px; }
    .est-total-final .lbl   { font-size: 9px;  color: #475569; text-transform: uppercase; }
    .est-total-final .monto { font-size: 20px; font-weight: 900; color: #166534; margin-top: 3px; }
</style>

<div class="est-header">
    <div class="titulo">SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY</div>
    <div class="libro">LIBRO DE VENTAS GASOLINA - <?= $esc(strtoupper($estacion)) ?></div>
    <div class="subtitulo"><?= $esc($subtitulo) ?></div>
    <div class="empleado">Responsable: <?= $esc($empleado) ?></div>
</div>

<div class="est-info">
    <div class="izq"><strong>Fecha del Reporte:</strong> <?= $esc($fechaFmt) ?></div>
</div>

<?php if ($seccion === 'resumen'): ?>

    <div class="est-kpi-row">
        <div class="est-kpi">
            <span class="num"><?= count($ventas) ?></span>
            <span class="lbl">Vehículos Atendidos</span>
        </div>
        <div class="est-kpi">
            <span class="num"><?= $fmt($totLitros) ?> L</span>
            <span class="lbl">Litros Vendidos</span>
        </div>
        <div class="est-kpi">
            <span class="num"><?= $fmt($totGeneralBs) ?></span>
            <span class="lbl">Total Bs</span>
        </div>
    </div>

    <?php if (!empty($combustibles)): ?>
        <div class="est-sec-title">Desglose por Tipo de Combustible</div>
        <table class="est-tabla">
            <thead>
                <tr>
                    <th>Combustible</th>
                    <th class="c">Vehículos</th>
                    <th class="r">Litros</th>
                    <th class="r">Total Bs</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($combustibles as $nombre => $info): ?>
                    <tr>
                        <td><strong><?= $esc($nombre) ?></strong></td>
                        <td class="c"><?= $info['cant'] ?></td>
                        <td class="r"><?= $fmt($info['litros']) ?> L</td>
                        <td class="r"><?= $fmt($info['monto_bs']) ?> Bs</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td>TOTAL</td>
                    <td class="c"><?= count($ventas) ?></td>
                    <td class="r"><?= $fmt($totLitros) ?> L</td>
                    <td class="r"><?= $fmt($totGeneralBs) ?> Bs</td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="est-sec-title">Desglose por Tipo de Vehículo</div>
    <table class="est-tabla">
        <thead>
            <tr>
                <th>Tipo de Vehículo</th>
                <th class="c">Cantidad</th>
                <th class="r">Litros</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($cantAuto > 0): ?>
                <tr><td>Automóvil</td><td class="c"><?= $cantAuto ?></td><td class="r"><?= $fmt($litrosAuto) ?> L</td></tr>
            <?php endif; ?>
            <?php if ($cantMoto > 0): ?>
                <tr><td>Motocicleta</td><td class="c"><?= $cantMoto ?></td><td class="r"><?= $fmt($litrosMoto) ?> L</td></tr>
            <?php endif; ?>
            <?php if ($cantCamion > 0): ?>
                <tr><td>Camión</td><td class="c"><?= $cantCamion ?></td><td class="r"><?= $fmt($litrosCamion) ?> L</td></tr>
            <?php endif; ?>
            <tr class="total">
                <td>TOTAL</td>
                <td class="c"><?= count($ventas) ?></td>
                <td class="r"><?= $fmt($totLitros) ?> L</td>
            </tr>
        </tbody>
    </table>

    <div class="est-sec-title">Desglose por Tipo de Pago</div>
    <table class="est-tabla">
        <thead>
            <tr>
                <th>Tipo de Pago</th>
                <th class="c">Cantidad</th>
                <th class="r">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pagoDivisa > 0): ?>
                <tr><td>Efectivo Divisa ($)</td><td class="c"><?= $pagoDivisa ?></td><td class="r"><?= $fmt($montoDivisaTotal) ?> $</td></tr>
            <?php endif; ?>
            <?php if ($pagoEfectivo > 0): ?>
                <tr><td>Efectivo (Bs)</td><td class="c"><?= $pagoEfectivo ?></td><td class="r"><?= $fmt($montoEfectivoTotal) ?> Bs</td></tr>
            <?php endif; ?>
            <?php if ($pagoDebito > 0): ?>
                <tr><td>Punto de Venta (Bs)</td><td class="c"><?= $pagoDebito ?></td><td class="r"><?= $fmt($montoDebitoTotal) ?> Bs</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="est-total-final">
        <div class="lbl">Total General en Bolívares</div>
        <div class="monto"><?= $fmt($totGeneralBs) ?> Bs</div>
    </div>

<?php else: ?>

    <?php $conColumnaComb = ($seccion === 'ventas'); ?>

    <table class="est-tabla">
        <thead>
            <tr>
                <th class="c" width="10%">Ticket</th>
                <th width="22%">Vehículo</th>
                <?php if ($conColumnaComb): ?><th width="15%">Combustible</th><?php endif; ?>
                <th class="c" width="12%">Litros</th>
                <th class="r" width="15%">Efectivo (Bs)</th>
                <th class="r" width="14%">Débito (Bs)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($ventas)): ?>
                <tr><td colspan="<?= $conColumnaComb ? 6 : 5 ?>" class="c" style="padding: 20px; color:#94a3b8;">No hay ventas para mostrar.</td></tr>
            <?php else: ?>
                <?php foreach ($ventas as $v): ?>
                    <tr>
                        <td class="c"><?= $esc($v['numero_venta'] ?? '') ?></td>
                        <td><?= $esc($v['tipo_vehiculo'] ?? '') ?></td>
                        <?php if ($conColumnaComb): ?>
                            <td><?= $esc($v['tipo_combustible'] ?? '') ?></td>
                        <?php endif; ?>
                        <td class="c"><?= $fmt($v['cantidad_litros'] ?? 0) ?> L</td>
                        <td class="r"><?= $fmt($v['efectivob'] ?? 0) ?></td>
                        <td class="r"><?= $fmt($v['tarjeta_debito'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="<?= $conColumnaComb ? 3 : 2 ?>" class="r">TOTAL GENERAL:</td>
                    <td class="c"><?= $fmt($totLitros) ?> L</td>
                    <td class="r"><?= $fmt($totEfectivo) ?></td>
                    <td class="r"><?= $fmt($totDebito) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="est-sec-title">Resumen Final</div>
    <table class="est-tabla">
        <tr>
            <th width="30%">Total Ventas:</th>
            <td width="20%"><?= count($ventas) ?></td>
            <th width="30%">Total Litros:</th>
            <td width="20%"><?= $fmt($totLitros) ?> L</td>
        </tr>
        <tr>
            <th>Efectivo (Bs):</th>
            <td><?= $fmt($totEfectivo) ?> Bs</td>
            <th>Débito (Bs):</th>
            <td><?= $fmt($totDebito) ?> Bs</td>
        </tr>
        <tr class="total">
            <th colspan="2">TOTAL GENERAL (Bs):</th>
            <td colspan="2"><strong><?= $fmt($totGeneralBs) ?> Bs</strong></td>
        </tr>
    </table>

<?php endif; ?>
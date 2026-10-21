<?php
/**
 * Template PDF · Reporte de Litros (Estación)
 * Recibe: $data (array), $total (float), $fecha, $type, $fechaFin,
 *         $litrosGasolina, $litrosDiesel, $meta
 */

$data    = $data ?? [];
$total   = (float)($total ?? 0);
$fecha   = $fecha ?? '';
$type    = $type ?? 'day';
$fechaFin= $fechaFin ?? null;
$lg      = (float)($litrosGasolina ?? 0);
$ld      = (float)($litrosDiesel ?? 0);

if (is_string($data)) {
    $decoded = json_decode($data, true);
    $data = is_array($decoded) ? $decoded : [];
}
if (!is_array($data)) $data = [];

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$fmt = fn($v) => number_format((float)($v ?? 0), 2, ',', '.');

function _meses(): array {
    return [
        '01'=>'Enero','02'=>'Febrero','03'=>'Marzo','04'=>'Abril','05'=>'Mayo','06'=>'Junio',
        '07'=>'Julio','08'=>'Agosto','09'=>'Septiembre','10'=>'Octubre','11'=>'Noviembre','12'=>'Diciembre'
    ];
}
$formatearMes = function($ym){
    $p = explode('-', (string)$ym);
    if (count($p) === 2 && isset(_meses()[$p[1]])) return _meses()[$p[1]] . ' ' . $p[0];
    return $ym;
};

if ($type === 'month') {
    if (!empty($fechaFin) && $fechaFin !== $fecha) {
        $titulo = 'REPORTE DE VENTAS POR RANGO DE MESES';
        $periodo = 'Desde: ' . $formatearMes($fecha) . ' Hasta: ' . $formatearMes($fechaFin);
    } else {
        $titulo = 'REPORTE MENSUAL DE LITROS VENDIDOS';
        $periodo = 'Mes: ' . $formatearMes($fecha);
    }
} else {
    if (!empty($fechaFin) && $fechaFin !== $fecha) {
        $titulo = 'REPORTE DE VENTAS POR RANGO DE DÍAS';
        $periodo = 'Desde: ' . date('d/m/Y', strtotime($fecha)) . ' Hasta: ' . date('d/m/Y', strtotime($fechaFin));
    } else {
        $titulo = 'REPORTE DIARIO DE LITROS VENDIDOS';
        $periodo = 'Fecha: ' . date('d/m/Y', strtotime($fecha));
    }
}
?>

<style>
    .lit-header { text-align: center; margin-bottom: 25px; }
    .lit-header .titulo { font-size: 13px; font-weight: 900; letter-spacing: .03em; color: #0f172a; }
    .lit-header .subtitulo { font-size: 11px; font-weight: 700; color: #1e40af; margin-top: 4px; }
    .lit-header .periodo { font-size: 10px; color: #475569; margin-top: 5px; }

    .lit-tabla { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .lit-tabla th { background: #1e40af; color: #fff; padding: 7px 8px; font-size: 8.5px; text-transform: uppercase; text-align: center; }
    .lit-tabla td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9.5px; text-align: center; }
    .lit-tabla tr:nth-child(even) td { background: #f8fafc; }
    .lit-tabla .total td { background: #dbeafe; font-weight: 800; border-top: 2px solid #1e40af; font-size: 10.5px; }

    .lit-desglose { background: #dcfce7; border: 2px solid #16a34a; border-radius: 8px; padding: 12px; margin-top: 20px; }
    .lit-desglose .tit { font-size: 10px; font-weight: 800; color: #166534; text-transform: uppercase; margin-bottom: 6px; letter-spacing: .04em; }
    .lit-desglose .item { font-size: 11px; color: #0f172a; padding: 3px 0; }
    .lit-desglose .item strong { color: #166534; }
</style>

<div class="lit-header">
    <div class="titulo">SERVICIO SOCIALISTA DE ABASTECIMIENTO DEL ESTADO YARACUY</div>
    <div class="subtitulo"><?= $esc($titulo) ?></div>
    <div class="periodo"><?= $esc($periodo) ?></div>
</div>

<table class="lit-tabla">
    <thead>
        <tr>
            <?php if ($type === 'month'): ?>
                <?php if (!empty($fechaFin) && $fechaFin !== $fecha): ?>
                    <th>Mes</th>
                    <th>Litros Vendidos</th>
                <?php else: ?>
                    <th>Fecha</th>
                    <th>Litros Vendidos</th>
                <?php endif; ?>
            <?php else: ?>
                <th>Tipo Vehículo</th>
                <th>Cantidad Ventas</th>
                <th>Litros Vendidos</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($data)): ?>
            <tr><td colspan="<?= $type === 'month' ? 2 : 3 ?>">No hay datos registrados.</td></tr>
        <?php else: ?>
            <?php foreach ($data as $row): ?>
                <tr>
                    <?php if ($type === 'month'): ?>
                        <td><?= $esc(!empty($fechaFin) && $fechaFin !== $fecha ? $formatearMes($row['mes'] ?? '') : ($row['fecha_venta'] ?? '')) ?></td>
                        <td><?= $fmt($row['total_litros'] ?? 0) ?> L</td>
                    <?php else: ?>
                        <td><?= $esc($row['tipo_vehiculo'] ?? '') ?></td>
                        <td><?= (int)($row['cantidad_ventas'] ?? 0) ?></td>
                        <td><?= $fmt($row['total_litros'] ?? 0) ?> L</td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        <tr class="total">
            <?php if ($type === 'month'): ?>
                <td>TOTAL</td>
                <td><?= $fmt($total) ?> L</td>
            <?php else: ?>
                <td colspan="2">TOTAL</td>
                <td><?= $fmt($total) ?> L</td>
            <?php endif; ?>
        </tr>
    </tbody>
</table>

<?php if ($lg > 0 || $ld > 0): ?>
    <div class="lit-desglose">
        <div class="tit">⛽ Desglose por Tipo de Combustible</div>
        <?php if ($lg > 0): ?>
            <div class="item"><strong>Gasolina:</strong> <?= $fmt($lg) ?> L</div>
        <?php endif; ?>
        <?php if ($ld > 0): ?>
            <div class="item"><strong>Diesel:</strong> <?= $fmt($ld) ?> L</div>
        <?php endif; ?>
    </div>
<?php endif; ?>
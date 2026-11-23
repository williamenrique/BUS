<?php
$eq    = $equipo ?? [];
$mants = $mantenimientos ?? [];

$totalInt    = count($mants);
$abiertasInt = 0;
$reemplazos  = 0;

foreach ($mants as $item) {
    $est = strtoupper((string)($item['estado_intervencion'] ?? 'CERRADA'));
    if ($est !== 'CERRADA') {
        $abiertasInt++;
    }
    if (!empty($item['componente_nuevo']) || strtoupper((string)($item['tipo_intervencion'] ?? '')) === 'REEMPLAZO') {
        $reemplazos++;
    }
}

$tipoLabels = [
    'SOPORTE'                    => 'SOPORTE TÉCNICO',
    'MANTENIMIENTO_PREVENTIVO' => 'MANTENIMIENTO PREVENTIVO',
    'MANTENIMIENTO_CORRECTIVO' => 'MANTENIMIENTO CORRECTIVO',
    'REEMPLAZO'                  => 'REEMPLAZO DE COMPONENTE',
];

$tipoEquipo    = strtoupper(trim((string)($eq['tipo_equipo'] ?? 'OTRO')));
$esComputo     = in_array($tipoEquipo, ['PC ESCRITORIO', 'LAPTOP', 'SERVIDOR'], true);
$esRed         = in_array($tipoEquipo, ['SWITCH', 'ROUTER'], true);
$esPeriferico  = !$esComputo && !$esRed;

$estatusEquipo = strtoupper((string)($eq['status_equipo'] ?? '—'));
$badgeEstatusClass = match ($estatusEquipo) {
    'ACTIVO'           => 'st-activo',
    'EN_MANTENIMIENTO' => 'st-mant',
    'BAJA'             => 'st-baja',
    default            => 'st-neutro',
};

$hostIpMac = implode(' / ', array_filter([
    $eq['nombre_host']  ?? '',
    $eq['direccion_ip']   ?? '',
    $eq['direccion_mac']  ?? '',
]));
$ramDisco = trim(implode(' · ', array_filter([
    $eq['ram_gb']         ?? '',
    $eq['almacenamiento'] ?? '',
])), ' ·');
$marcaModelo = trim(implode(' ', array_filter([
    $eq['marca']  ?? '',
    $eq['modelo'] ?? '',
])));
?>
<style>
    .pdf-wrap {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        color: #0f172a;
        font-size: 10.5px;
        line-height: 1.4;
        margin-top: 14px;
    }
    .pdf-banner {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-left: 4px solid #1d4ed8;
        border-radius: 5px;
        padding: 10px 12px;
        margin-bottom: 12px;
    }
    .pdf-banner-table {
        width: 100%;
        border-collapse: collapse;
    }
    .pdf-banner-table td {
        padding: 0;
        vertical-align: middle;
        border: none;
    }
    .pdf-banner-right {
        text-align: right;
        white-space: nowrap;
    }
    .pdf-eq-code {
        font-size: 13px;
        font-weight: bold;
        color: #0f172a;
    }
    .pdf-eq-sub {
        font-size: 9.5px;
        color: #475569;
        margin-top: 4px;
    }
    .st-pill {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 10px;
        font-size: 8.5px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .st-activo { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
    .st-mant   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .st-baja   { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .st-neutro { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }

    .kpi-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 6px 0;
        margin: 0 -6px 12px -6px;
    }
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        padding: 6px 8px;
        width: 33.33%;
        vertical-align: top;
    }
    .kpi-label {
        font-size: 8px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: bold;
    }
    .kpi-val {
        font-size: 12px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 2px;
    }

    .sec-title {
        font-size: 11px;
        font-weight: bold;
        color: #1e293b;
        margin: 10px 0 6px 0;
        padding-bottom: 3px;
        border-bottom: 1.5px solid #e2e8f0;
    }

    .ficha-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
        margin-bottom: 12px;
    }
    .ficha-table td {
        padding: 5px 7px;
        border: 1px solid #e2e8f0;
        vertical-align: top;
    }
    .ficha-table td.lbl {
        background: #f8fafc;
        font-weight: bold;
        color: #334155;
        width: 16%;
    }
    .ficha-table td.val {
        color: #0f172a;
        width: 34%;
    }

    .mant-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    .mant-table th {
        background: #0f172a;
        color: #ffffff;
        font-weight: bold;
        padding: 6px 6px;
        border: 1px solid #0f172a;
        text-align: left;
        font-size: 8.5px;
    }
    .mant-table td {
        padding: 6px 6px;
        border: 1px solid #cbd5e1;
        vertical-align: top;
    }
    .mant-table tr:nth-child(even) td {
        background: #f8fafc;
    }
    .tipo-badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 7.5px;
        font-weight: bold;
        background: #e0f2fe;
        color: #075985;
        margin-bottom: 3px;
    }
    .estado-cerrada {
        color: #166534;
        font-weight: bold;
        font-size: 8px;
    }
    .estado-abierta {
        color: #b45309;
        font-weight: bold;
        font-size: 8px;
    }
    .rep-box {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        border-radius: 4px;
        padding: 4px 6px;
        color: #1e3a8a;
        font-size: 8.5px;
    }
    .sin-rep {
        color: #64748b;
        font-style: italic;
        font-size: 8.5px;
    }
    .muted-small {
        color: #475569;
        font-size: 8.5px;
    }
</style>

<div class="pdf-wrap">
    <div class="pdf-banner">
        <table class="pdf-banner-table">
            <tr>
                <td>
                    <span class="pdf-eq-code"><?= e($eq['codigo_equipo'] ?? 'EQUIPO') ?> — <?= e($tipoEquipo) ?></span>
                </td>
                <td class="pdf-banner-right">
                    <span class="st-pill <?= $badgeEstatusClass ?>"><?= e($estatusEquipo) ?></span>
                </td>
            </tr>
        </table>
        <div class="pdf-eq-sub">
            Departamento: <strong><?= e($eq['departamento_nombre'] ?? 'No asignado') ?></strong>
            &nbsp;|&nbsp;
            Responsable: <strong><?= e($eq['responsable_nombre'] ?? 'Sin asignar') ?></strong>
        </div>
    </div>

    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Total Intervenciones</div>
                <div class="kpi-val"><?= (int)$totalInt ?></div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Reemplazos de Hardware</div>
                <div class="kpi-val"><?= (int)$reemplazos ?></div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Pendientes / Abiertas</div>
                <div class="kpi-val"><?= (int)$abiertasInt ?></div>
            </td>
        </tr>
    </table>

    <div class="sec-title">Datos de Ficha Técnica</div>
    <table class="ficha-table">
        <tr>
            <td class="lbl">Marca / Modelo</td>
            <td class="val"><?= e($marcaModelo ?: 'No especificado') ?></td>
            <td class="lbl">Serial</td>
            <td class="val"><?= e($eq['serial_equipo'] ?: 'No registrado') ?></td>
        </tr>

        <?php if ($esComputo): ?>
            <tr>
                <td class="lbl">Procesador</td>
                <td class="val"><?= e($eq['procesador'] ?: 'No especificado') ?></td>
                <td class="lbl">RAM / Almac.</td>
                <td class="val"><?= e($ramDisco ?: 'No especificado') ?></td>
            </tr>
            <tr>
                <td class="lbl">Sistema Operativo</td>
                <td class="val"><?= e($eq['sistema_operativo'] ?: 'No especificado') ?></td>
                <td class="lbl">Host / IP / MAC</td>
                <td class="val"><?= e($hostIpMac ?: 'Sin datos de red') ?></td>
            </tr>
        <?php elseif ($esRed): ?>
            <tr>
                <td class="lbl">IP / Host</td>
                <td class="val"><?= e(implode(' / ', array_filter([$eq['direccion_ip'] ?? '', $eq['nombre_host'] ?? ''])) ?: 'No asignada') ?></td>
                <td class="lbl">Dirección MAC</td>
                <td class="val"><?= e($eq['direccion_mac'] ?: 'No registrada') ?></td>
            </tr>
        <?php elseif ($esPeriferico && $hostIpMac !== ''): ?>
            <tr>
                <td class="lbl">Host / IP / MAC</td>
                <td class="val" colspan="3"><?= e($hostIpMac) ?></td>
            </tr>
        <?php endif; ?>

        <tr>
            <td class="lbl">Adquisición</td>
            <td class="val"><?= e($eq['fecha_adquisicion'] ?: 'No registrada') ?></td>
            <td class="lbl">Observaciones</td>
            <td class="val"><?= e($eq['observaciones'] ?: 'Sin observaciones adicionales') ?></td>
        </tr>
    </table>

    <div class="sec-title">Historial de Soporte, Mantenimiento y Reemplazo</div>
    <table class="mant-table">
        <thead>
            <tr>
                <th style="width:11%;">Fecha</th>
                <th style="width:17%;">Tipo / Estado</th>
                <th style="width:32%;">Diagnóstico y Acción</th>
                <th style="width:24%;">Reemplazo de Componente</th>
                <th style="width:16%;">Técnico</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mants)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding:10px; color:#64748b;">
                        No se han registrado intervenciones para este equipo.
                    </td>
                </tr>
            <?php else: foreach ($mants as $m): ?>
                <?php
                    $tipoRaw  = strtoupper((string)($m['tipo_intervencion'] ?? ''));
                    $tipoTxt  = $tipoLabels[$tipoRaw] ?? ($tipoRaw ?: 'INTERVENCIÓN');
                    $estRaw   = strtoupper((string)($m['estado_intervencion'] ?? 'CERRADA'));
                    $estClass = $estRaw === 'CERRADA' ? 'estado-cerrada' : 'estado-abierta';

                    $tieneComp = !empty($m['componente_nuevo']) || !empty($m['componente_anterior']) || !empty($m['tipo_componente']);
                ?>
                <tr>
                    <td><?= e($m['fecha_intervencion'] ?? '') ?></td>
                    <td>
                        <span class="tipo-badge"><?= e($tipoTxt) ?></span><br>
                        <span class="<?= $estClass ?>">Estado: <?= e($estRaw) ?></span>
                    </td>
                    <td>
                        <strong><?= e($m['diagnostico'] ?? '') ?></strong><br>
                        <span class="muted-small"><?= e($m['accion_realizada'] ?? '') ?></span>
                    </td>
                    <td>
                        <?php if ($tieneComp): ?>
                            <div class="rep-box">
                                <strong><?= e($m['tipo_componente'] ?: 'COMPONENTE') ?>:</strong><br>
                                Anterior: <?= e($m['componente_anterior'] ?: 'N/A') ?><br>
                                Nuevo: <strong><?= e($m['componente_nuevo'] ?: 'N/A') ?></strong>
                                <?php if (!empty($m['serial_nuevo'])): ?>
                                    <br><span class="muted-small">S/N: <?= e($m['serial_nuevo']) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <span class="sin-rep">No aplica reemplazo (soporte / software / preventivo)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($m['tecnico_nombre'] ?: 'No asignado') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
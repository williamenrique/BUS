<?php
$equipos        = $equipos ?? [];
$mantenimientos = $mantenimientos ?? [];
$filtros        = $filtros ?? [];

$totalEquipos = count($equipos);
$activos      = 0;
$enMant       = 0;
$bajaInact    = 0;

foreach ($equipos as $eq) {
    $st = strtoupper((string)($eq['status_equipo'] ?? ''));
    if ($st === 'ACTIVO') {
        $activos++;
    } elseif ($st === 'EN_MANTENIMIENTO') {
        $enMant++;
    } else {
        $bajaInact++;
    }
}

$totalMant      = count($mantenimientos);
$mantAbiertas   = 0;
$mantReemplazos = 0;

foreach ($mantenimientos as $m) {
    if (strtoupper((string)($m['estado_intervencion'] ?? 'CERRADA')) !== 'CERRADA') {
        $mantAbiertas++;
    }
    if (!empty($m['componente_nuevo']) || strtoupper((string)($m['tipo_intervencion'] ?? '')) === 'REEMPLAZO') {
        $mantReemplazos++;
    }
}

$tipoLabels = [
    'SOPORTE'                    => 'SOPORTE TÉCNICO',
    'MANTENIMIENTO_PREVENTIVO' => 'MANTENIMIENTO PREVENTIVO',
    'MANTENIMIENTO_CORRECTIVO' => 'MANTENIMIENTO CORRECTIVO',
    'REEMPLAZO'                  => 'REEMPLAZO DE COMPONENTE',
];

$filtrosActivos = [];
if (!empty($filtros['departamento_nombre'])) {
    $filtrosActivos[] = 'Departamento: ' . $filtros['departamento_nombre'];
} elseif (!empty($filtros['departamento'])) {
    $filtrosActivos[] = 'Depto ID: ' . $filtros['departamento'];
}
if (!empty($filtros['tipo']))        $filtrosActivos[] = 'Tipo: ' . $filtros['tipo'];
if (!empty($filtros['status']))      $filtrosActivos[] = 'Estatus: ' . $filtros['status'];
if (!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta'])) {
    $desdeTxt = !empty($filtros['fecha_desde']) ? $filtros['fecha_desde'] : 'Inicio';
    $hastaTxt = !empty($filtros['fecha_hasta']) ? $filtros['fecha_hasta'] : 'Hoy';
    $filtrosActivos[] = "Rango de fechas: {$desdeTxt} al {$hastaTxt}";
}
if (!empty($filtros['q']))           $filtrosActivos[] = 'Búsqueda: "' . $filtros['q'] . '"';
$resumenFiltros = !empty($filtrosActivos) ? implode(' · ', $filtrosActivos) : 'Todos los registros de la institución (sin filtros)';
?>
<style>
    .gpdf-wrap {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        color: #0f172a;
        font-size: 9.5px;
        line-height: 1.35;
        margin-top: 14px;
    }
    .gpdf-meta {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-left: 4px solid #0f172a;
        border-radius: 4px;
        padding: 7px 10px;
        margin-bottom: 10px;
        font-size: 8.5px;
        color: #334155;
    }
    .gpdf-kpis {
        width: 100%;
        border-collapse: separate;
        border-spacing: 5px 0;
        margin: 0 -5px 10px -5px;
    }
    .gpdf-kpi {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        padding: 5px 7px;
        width: 25%;
        vertical-align: top;
    }
    .gpdf-kpi-lbl {
        font-size: 7.5px;
        text-transform: uppercase;
        color: #64748b;
        font-weight: bold;
    }
    .gpdf-kpi-val {
        font-size: 11.5px;
        font-weight: bold;
        color: #0f172a;
        margin-top: 2px;
    }
    .gpdf-sec {
        font-size: 10.5px;
        font-weight: bold;
        color: #0f172a;
        margin: 10px 0 5px 0;
        padding-bottom: 3px;
        border-bottom: 1.5px solid #cbd5e1;
    }
    .gpdf-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.2px;
        margin-bottom: 10px;
    }
    .gpdf-table th {
        background: #0f172a;
        color: #ffffff;
        padding: 5px 5px;
        border: 1px solid #0f172a;
        text-align: left;
        font-size: 7.8px;
    }
    .gpdf-table td {
        padding: 4.5px 5px;
        border: 1px solid #cbd5e1;
        vertical-align: top;
    }
    .gpdf-table tr:nth-child(even) td {
        background: #f8fafc;
    }
    .tag-ok   { color: #166534; font-weight: bold; }
    .tag-warn { color: #b45309; font-weight: bold; }
    .tag-bad  { color: #991b1b; font-weight: bold; }
    .muted-xs { color: #64748b; font-size: 7.5px; }
</style>

<div class="gpdf-wrap">
    <div class="gpdf-meta">
        <strong>Alcance del reporte:</strong> <?= e($resumenFiltros) ?>
    </div>

    <table class="gpdf-kpis">
        <tr>
            <td class="gpdf-kpi">
                <div class="gpdf-kpi-lbl">Equipos Listados</div>
                <div class="gpdf-kpi-val"><?= (int)$totalEquipos ?></div>
            </td>
            <td class="gpdf-kpi">
                <div class="gpdf-kpi-lbl">Operativos / Activos</div>
                <div class="gpdf-kpi-val tag-ok"><?= (int)$activos ?></div>
            </td>
            <td class="gpdf-kpi">
                <div class="gpdf-kpi-lbl">Intervenciones (Abiertas)</div>
                <div class="gpdf-kpi-val"><?= (int)$totalMant ?> <span class="tag-warn">(<?= (int)$mantAbiertas ?>)</span></div>
            </td>
            <td class="gpdf-kpi">
                <div class="gpdf-kpi-lbl">Reemplazos Hardware</div>
                <div class="gpdf-kpi-val"><?= (int)$mantReemplazos ?></div>
            </td>
        </tr>
    </table>

    <div class="gpdf-sec">1. Inventario y Ficha Técnica de Equipos</div>
    <table class="gpdf-table">
        <thead>
            <tr>
                <th style="width:13%;">Código / Tipo</th>
                <th style="width:26%;">Especificaciones</th>
                <th style="width:16%;">Departamento</th>
                <th style="width:18%;">Responsable</th>
                <th style="width:14%;">Red / Host</th>
                <th style="width:13%;">Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($equipos)): ?>
                <tr><td colspan="6" style="text-align:center; padding:8px; color:#64748b;">Sin equipos para el criterio seleccionado.</td></tr>
            <?php else: foreach ($equipos as $eq): ?>
                <?php
                    $tipoEq = strtoupper((string)($eq['tipo_equipo'] ?? ''));
                    $esComp = in_array($tipoEq, ['PC ESCRITORIO', 'LAPTOP', 'SERVIDOR'], true);

                    $specsPartes = array_filter([
                        trim(($eq['marca'] ?? '') . ' ' . ($eq['modelo'] ?? '')),
                        $esComp && !empty($eq['procesador']) ? 'CPU: ' . $eq['procesador'] : '',
                        $esComp && !empty($eq['ram_gb']) ? 'RAM: ' . $eq['ram_gb'] : '',
                        $esComp && !empty($eq['almacenamiento']) ? 'Alm: ' . $eq['almacenamiento'] : '',
                        !empty($eq['serial_equipo']) ? 'S/N: ' . $eq['serial_equipo'] : '',
                    ]);
                    $redPartes = implode(' / ', array_filter([
                        $eq['nombre_host'] ?? '',
                        $eq['direccion_ip']  ?? '',
                    ]));

                    $stEq = strtoupper((string)($eq['status_equipo'] ?? ''));
                    $stClass = $stEq === 'ACTIVO' ? 'tag-ok' : ($stEq === 'EN_MANTENIMIENTO' ? 'tag-warn' : 'tag-bad');
                    $abEq = (int)($eq['intervenciones_abiertas'] ?? 0);
                ?>
                <tr>
                    <td>
                        <strong><?= e($eq['codigo_equipo'] ?? '') ?></strong><br>
                        <span class="muted-xs"><?= e($tipoEq) ?></span>
                    </td>
                    <td><?= e(implode(' · ', $specsPartes) ?: '—') ?></td>
                    <td><?= e($eq['departamento_nombre'] ?? '—') ?></td>
                    <td><?= e($eq['responsable_nombre'] ?? '—') ?></td>
                    <td><?= e($redPartes ?: '—') ?></td>
                    <td>
                        <span class="<?= $stClass ?>"><?= e($stEq ?: '—') ?></span>
                        <?php if ($abEq > 0): ?>
                            <br><span class="tag-warn"><?= $abEq ?> abierta(s)</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="gpdf-sec">2. Bitácora General de Soporte, Mantenimiento y Reemplazos</div>
    <table class="gpdf-table">
        <thead>
            <tr>
                <th style="width:10%;">Fecha</th>
                <th style="width:13%;">Equipo</th>
                <th style="width:17%;">Tipo / Estado</th>
                <th style="width:28%;">Diagnóstico y Acción</th>
                <th style="width:18%;">Reemplazo</th>
                <th style="width:14%;">Técnico</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($mantenimientos)): ?>
                <tr><td colspan="6" style="text-align:center; padding:8px; color:#64748b;">Sin intervenciones registradas.</td></tr>
            <?php else: foreach ($mantenimientos as $m): ?>
                <?php
                    $tRaw = strtoupper((string)($m['tipo_intervencion'] ?? ''));
                    $tTxt = $tipoLabels[$tRaw] ?? ($tRaw ?: 'INTERVENCIÓN');
                    $eRaw = strtoupper((string)($m['estado_intervencion'] ?? 'CERRADA'));
                    $eCls = $eRaw === 'CERRADA' ? 'tag-ok' : 'tag-warn';
                    $tieneR = !empty($m['componente_nuevo']) || !empty($m['componente_anterior']) || !empty($m['tipo_componente']);
                ?>
                <tr>
                    <td><?= e($m['fecha_intervencion'] ?? '') ?></td>
                    <td>
                        <strong><?= e($m['codigo_equipo'] ?? '') ?></strong><br>
                        <span class="muted-xs"><?= e($m['tipo_equipo'] ?? '') ?></span>
                    </td>
                    <td>
                        <strong><?= e($tTxt) ?></strong><br>
                        <span class="<?= $eCls ?>"><?= e($eRaw) ?></span>
                    </td>
                    <td>
                        <strong><?= e($m['diagnostico'] ?? '') ?></strong><br>
                        <span class="muted-xs"><?= e($m['accion_realizada'] ?? '') ?></span>
                    </td>
                    <td>
                        <?php if ($tieneR): ?>
                            <strong><?= e($m['tipo_componente'] ?: 'COMP.') ?>:</strong>
                            <?= e($m['componente_anterior'] ?: 'N/A') ?> → <strong><?= e($m['componente_nuevo'] ?: 'N/A') ?></strong>
                        <?php else: ?>
                            <span class="muted-xs">Sin reemplazo de hardware</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($m['tecnico_nombre'] ?? '—') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
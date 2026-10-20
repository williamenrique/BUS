<?php
/**
 * Template PDF · Hoja de Vida de Unidad (Timeline de eventos)
 * Recibe: $items (array de eventos), $counts (array), $unidadData (array), $meta
 */

$items      = $items ?? [];
$counts     = $counts ?? [];
$unidad     = $unidadData ?? [];

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

$TIPO_LABEL = [
    'despacho'      => 'Despacho',
    'aceite'        => 'Cambio de aceite',
    'mantenimiento' => 'Mantenimiento',
    'status'        => 'Cambio de estado',
];

$STATUS_TEXTO = [
    0 => 'Desincorporada',
    1 => 'Operativa',
    2 => 'Inoperativa',
    3 => 'Mantenimiento',
    4 => 'Por Desincorporar',
    5 => 'Crítica',
];
?>

<div class="pdf-content">

    <h1 class="pdf-titulo">Hoja de Vida de Unidad</h1>
    <p class="pdf-subtitulo"><?= $esc($unidad['id_unidad'] ?? '') ?> · Generado el <?= date('d/m/Y H:i') ?></p>

    <!-- Info de la unidad -->
    <div class="section-box no-break">
        <div class="section-title">Información de la Unidad</div>
        <table width="100%" cellpadding="4" cellspacing="0">
            <tr>
                <td width="25%"><span class="label-min">Unidad</span><br><span class="val-text"><?= $esc($unidad['id_unidad'] ?? '—') ?></span></td>
                <td width="25%"><span class="label-min">Marca</span><br><span class="val-text"><?= $esc($unidad['marca'] ?? '—') ?></span></td>
                <td width="25%"><span class="label-min">Modelo</span><br><span class="val-text"><?= $esc($unidad['modelo'] ?? '—') ?></span></td>
                <td width="25%"><span class="label-min">VIN</span><br><span class="val-text"><?= $esc($unidad['vin'] ?? '—') ?></span></td>
            </tr>
        </table>
    </div>

    <!-- Resumen de eventos -->
    <div class="section-box no-break">
        <div class="section-title">Resumen de Eventos</div>
        <table width="100%" cellpadding="4" cellspacing="0" style="font-size:10px;">
            <tr>
                <td width="20%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Despachos</div>
                    <div style="font-size:16px; font-weight:900;"><?= (int)($counts['despacho'] ?? 0) ?></div>
                </td>
                <td width="20%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Aceite</div>
                    <div style="font-size:16px; font-weight:900;"><?= (int)($counts['aceite'] ?? 0) ?></div>
                </td>
                <td width="20%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Mantenimiento</div>
                    <div style="font-size:16px; font-weight:900;"><?= (int)($counts['mantenimiento'] ?? 0) ?></div>
                </td>
                <td width="20%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Estados</div>
                    <div style="font-size:16px; font-weight:900;"><?= (int)($counts['status'] ?? 0) ?></div>
                </td>
                <td width="20%" style="text-align:center;">
                    <div class="label-min">Total</div>
                    <div style="font-size:16px; font-weight:900;"><?= count($items) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Timeline de eventos -->
    <div class="section-box">
        <div class="section-title">Detalle de Eventos</div>

        <?php if (empty($items)): ?>
            <p class="text-center text-muted" style="padding:15px 0;">No se encontraron eventos registrados.</p>
        <?php else: ?>
            <table class="items-table">
                <thead>
                    <tr>
                        <th width="12%">Fecha</th>
                        <th width="15%">Tipo</th>
                        <th width="53%">Detalle</th>
                        <th width="20%">Registrado por</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $ev): ?>
                        <?php
                            $tipo = $ev['tipo'] ?? '';
                            $tipoLabel = $TIPO_LABEL[$tipo] ?? ucfirst($tipo);
                            $fecha = !empty($ev['fecha']) ? date('d/m/Y H:i', strtotime($ev['fecha'])) : '—';
                            $usuario = $ev['usuario_nombre'] ?? 'Sistema';
                            $titulo = $ev['titulo'] ?? '';
                            $detalle = '';

                            switch ($tipo) {
                                case 'despacho':
                                    $arts = $ev['articulos'] ?? [];
                                    $lineas = [];
                                    foreach ($arts as $a) {
                                        $lineas[] = '• ' . ($a['cant_despacho'] ?? '') . 'x ' . ($a['producto'] ?? '');
                                    }
                                    $detalle = $titulo;
                                    if (!empty($ev['descripcion'])) $detalle .= '<br><em>' . $esc($ev['descripcion']) . '</em>';
                                    if (!empty($lineas)) $detalle .= '<br>' . implode('<br>', array_map('htmlspecialchars', $lineas));
                                    break;

                                case 'aceite':
                                    $km = (int)($ev['km_cambio'] ?? 0);
                                    $ant = (int)($ev['km_anterior'] ?? 0);
                                    $detalle = 'Cambio registrado';
                                    if ($ant > 0) $detalle .= ' de ' . number_format($ant, 0, ',', '.') . ' km';
                                    $detalle .= ' a <strong>' . number_format($km, 0, ',', '.') . ' km</strong>';
                                    if (!empty($ev['observaciones'])) $detalle .= '<br><em>' . $esc($ev['observaciones']) . '</em>';
                                    break;

                                case 'mantenimiento':
                                    $tipoMant = ($ev['tipo_mantenimiento'] ?? '') === 'P' ? 'Preventivo' : 'Correctivo';
                                    $detalle = '<strong>Tipo:</strong> ' . $tipoMant;
                                    if (!empty($ev['diagnostico'])) $detalle .= '<br><strong>Diagnóstico:</strong> ' . $esc($ev['diagnostico']);
                                    if (!empty($ev['mecanico']))    $detalle .= '<br><strong>Mecánico:</strong> ' . $esc($ev['mecanico']);
                                    if (!empty($ev['recomendacion'])) $detalle .= '<br><strong>Recomendación:</strong> ' . $esc($ev['recomendacion']);
                                    break;

                                case 'status':
                                    $nuevo = $STATUS_TEXTO[$ev['status_codigo'] ?? -1] ?? '—';
                                    $detalle = 'Estado cambiado a <strong>' . $esc($nuevo) . '</strong>';
                                    if (!empty($ev['motivo'])) $detalle .= '<br><strong>Motivo:</strong> ' . $esc($ev['motivo']);
                                    break;

                                default:
                                    $detalle = $esc($titulo);
                            }
                        ?>
                        <tr>
                            <td style="font-size:8px;"><?= $fecha ?></td>
                            <td><span class="badge badge-info"><?= $esc($tipoLabel) ?></span></td>
                            <td style="font-size:8.5px;"><?= $detalle ?></td>
                            <td style="font-size:8px;"><?= $esc($usuario) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
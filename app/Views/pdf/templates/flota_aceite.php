<?php
/**
 * Template PDF · Reporte de Estado de Aceite
 * Recibe: $items (array de unidades), $counts (array), $filtro (string), $meta
 */

$items  = $items  ?? [];
$counts = $counts ?? [];
$filtro = $filtro ?? 'Todos';

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v) => number_format((float)($v ?? 0), 0, ',', '.');
?>

<div class="pdf-content">

    <h1 class="pdf-titulo">Reporte de Estado de Aceite</h1>
    <p class="pdf-subtitulo">
        Filtro aplicado: <strong><?= $esc($filtro) ?></strong> · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <!-- Resumen -->
    <div class="section-box no-break">
        <div class="section-title">Resumen por Estado</div>
        <table width="100%" cellpadding="4" cellspacing="0" style="font-size:10px;">
            <tr>
                <td width="25%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Cambio Requerido</div>
                    <div style="font-size:16px; font-weight:900; color:#dc2626;"><?= (int)($counts['Requerido'] ?? 0) ?></div>
                </td>
                <td width="25%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Cambio Próximo</div>
                    <div style="font-size:16px; font-weight:900; color:#b45309;"><?= (int)($counts['Próximo'] ?? 0) ?></div>
                </td>
                <td width="25%" style="text-align:center; border-right:1px solid #e2e8f0;">
                    <div class="label-min">Estado Óptimo</div>
                    <div style="font-size:16px; font-weight:900; color:#16a34a;"><?= (int)($counts['Bien'] ?? 0) ?></div>
                </td>
                <td width="25%" style="text-align:center;">
                    <div class="label-min">Sin Registro</div>
                    <div style="font-size:16px; font-weight:900; color:#64748b;"><?= (int)($counts['Sin Registro'] ?? 0) ?></div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de unidades -->
    <div class="section-box">
        <div class="section-title">Detalle de Unidades (<?= count($items) ?>)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="15%">Unidad</th>
                    <th width="20%">Marca / Modelo</th>
                    <th width="13%" class="text-right">KM Actual</th>
                    <th width="13%" class="text-right">Último Cambio</th>
                    <th width="13%" class="text-right">Próximo</th>
                    <th width="13%" class="text-right">Restantes</th>
                    <th width="13%" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="7" class="text-center text-muted">No se encontraron unidades.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $u): ?>
                        <?php
                            $estado = $u['estado'] ?? 'Sin Registro';
                            $badgeMap = [
                                'Requerido'    => 'badge-danger',
                                'Próximo'      => 'badge-warning',
                                'Bien'         => 'badge-success',
                                'Sin Registro' => 'badge-neutral',
                            ];
                            $badgeClass = $badgeMap[$estado] ?? 'badge-neutral';
                            $resta = (int)($u['km_restantes'] ?? 0);
                            $restColor = $resta < 0 ? 'color:#dc2626; font-weight:700;' : ($resta <= 1000 && $resta >= 0 ? 'color:#b45309; font-weight:700;' : '');
                        ?>
                        <tr>
                            <td class="text-bold"><?= $esc($u['id_unidad'] ?? '') ?></td>
                            <td><?= $esc(($u['marca_unidad'] ?? '') . ' ' . ($u['modelo_unidad'] ?? '')) ?></td>
                            <td class="text-right"><?= $num($u['kilometraje_actual'] ?? 0) ?></td>
                            <td class="text-right"><?= !empty($u['ultimo_cambio_km']) ? $num($u['ultimo_cambio_km']) : '—' ?></td>
                            <td class="text-right"><?= !empty($u['proximo_cambio_km']) ? $num($u['proximo_cambio_km']) : '—' ?></td>
                            <td class="text-right" style="<?= $restColor ?>"><?= !empty($u['proximo_cambio_km']) ? $num($resta) : '—' ?></td>
                            <td class="text-center"><span class="badge <?= $badgeClass ?>"><?= $esc($estado) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>
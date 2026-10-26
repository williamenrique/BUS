<?php
/**
 * Template PDF · Reporte de Aceite (Público)
 * Modos: 'por_estado' (solo un estado) | 'completo' (todas las unidades)
 * El header con institución+logo lo pone pdf/inc/header.php
 */

$items  = $items  ?? [];
$filtro = $filtro ?? 'todos';
$modo   = $modo   ?? 'por_estado';

if (is_string($items)) $items = json_decode($items, true) ?: [];
if (!is_array($items)) $items = [];

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = fn($v, $d = 0) => number_format((float)($v ?? 0), $d, ',', '.');

/* Calcular estado de cada unidad */
$procesados = [];
$counts = ['Bien'=>0, 'Próximo'=>0, 'Requerido'=>0, 'Sin Registro'=>0];

foreach ($items as $u){
    $km = (int)($u['km_actual'] ?? 0);
    $uc = (int)($u['ultimo_cambio_aceite'] ?? 0);
    $pc = (int)($u['proximo_cambio_aceite'] ?? 0);

    $estado = 'Sin Registro';
    $resta = 0;
    if ($pc > 0){
        $resta = $pc - $km;
        if ($resta <= 0) $estado = 'Requerido';
        elseif ($resta <= 1000) $estado = 'Próximo';
        else $estado = 'Bien';
    }

    $counts[$estado]++;

    $procesados[] = [
        'id_unidad'      => $u['id_unidad'] ?? '',
        'marca_unidad'   => $u['marca_unidad'] ?? '',
        'modelo_unidad'  => $u['modelo_unidad'] ?? '',
        'km_actual'      => $km,
        'ultimo_cambio'  => $uc,
        'proximo_cambio' => $pc,
        'restantes'      => $resta,
        'estado'         => $estado,
    ];
}
?>

<div class="pdf-content">
    <h1 class="pdf-titulo">Reporte de Estado de Aceite</h1>
    <p class="pdf-subtitulo">
        <?php if ($modo === 'completo'): ?>
            Reporte <strong>completo</strong> · <?= count($procesados) ?> unidad(es)
        <?php else: ?>
            Filtro: <strong><?= $esc($filtro) ?></strong> · <?= count($procesados) ?> unidad(es)
        <?php endif; ?>
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <!-- Resumen por estado -->
    <div class="section-box no-break">
        <div class="section-title">Resumen por Estado</div>
        <?php if ($modo === 'completo'): ?>
            <table width="100%" style="font-size:10px;">
                <tr>
                    <td width="25%" class="text-center"><div class="label-min">Bien</div><div style="font-size:16px; font-weight:900; color:#16a34a;"><?= $counts['Bien'] ?></div></td>
                    <td width="25%" class="text-center"><div class="label-min">Próximo</div><div style="font-size:16px; font-weight:900; color:#b45309;"><?= $counts['Próximo'] ?></div></td>
                    <td width="25%" class="text-center"><div class="label-min">Requerido</div><div style="font-size:16px; font-weight:900; color:#dc2626;"><?= $counts['Requerido'] ?></div></td>
                    <td width="25%" class="text-center"><div class="label-min">Sin Registro</div><div style="font-size:16px; font-weight:900; color:#64748b;"><?= $counts['Sin Registro'] ?></div></td>
                </tr>
            </table>
        <?php else: ?>
            <?php
                $estadoSel = $filtro;
                $colorMap = [
                    'Bien'         => '#16a34a',
                    'Próximo'      => '#b45309',
                    'Requerido'    => '#dc2626',
                    'Sin Registro' => '#64748b',
                ];
                $color = $colorMap[$estadoSel] ?? '#64748b';
                $total = $counts[$estadoSel] ?? count($procesados);
            ?>
            <table width="100%" style="font-size:10px;">
                <tr>
                    <td class="text-center">
                        <div class="label-min"><?= $esc($estadoSel) ?></div>
                        <div style="font-size:20px; font-weight:900; color:<?= $color ?>;"><?= $total ?></div>
                    </td>
                </tr>
            </table>
        <?php endif; ?>
    </div>

    <!-- Tabla de detalle -->
    <div class="section-box">
        <div class="section-title">Detalle de Unidades (<?= count($procesados) ?>)</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="12%">Unidad</th>
                    <th width="20%">Marca / Modelo</th>
                    <th width="13%" class="text-right">KM Actual</th>
                    <th width="13%" class="text-right">Últ. Cambio</th>
                    <th width="13%" class="text-right">Próx. Cambio</th>
                    <th width="13%" class="text-right">Restantes</th>
                    <th width="16%" class="text-center">Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($procesados)): ?>
                    <tr><td colspan="7" class="text-center text-muted">Sin unidades para mostrar.</td></tr>
                <?php else: ?>
                    <?php
                    $badgeMap = ['Bien'=>'badge-success','Próximo'=>'badge-warning','Requerido'=>'badge-danger','Sin Registro'=>'badge-neutral'];
                    foreach ($procesados as $u):
                    ?>
                        <tr>
                            <td class="text-bold"><?= $esc($u['id_unidad']) ?></td>
                            <td><?= $esc(trim($u['marca_unidad'] . ' ' . $u['modelo_unidad'])) ?></td>
                            <td class="text-right"><?= $num($u['km_actual']) ?></td>
                            <td class="text-right"><?= $u['ultimo_cambio'] ? $num($u['ultimo_cambio']) : '—' ?></td>
                            <td class="text-right"><?= $u['proximo_cambio'] ? $num($u['proximo_cambio']) : '—' ?></td>
                            <td class="text-right"><?= $u['proximo_cambio'] ? $num($u['restantes']) : '—' ?></td>
                            <td class="text-center">
                                <span class="badge <?= $badgeMap[$u['estado']] ?>"><?= $esc($u['estado']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
/**
 * Template PDF · Reporte de Bienes
 * Recibe: $items (array), $meta
 *
 * $meta['subtitulo']              → texto debajo del título (ej: "Departamento: ADMINISTRACIÓN")
 * $meta['agrupar_por_departamento'] → bool (true = agrupa por depto, false = tabla plana)
 */

$items = $items ?? [];
if (is_string($items)) {
    $decoded = json_decode($items, true);
    $items = is_array($decoded) ? $decoded : [];
}
if (!is_array($items)) $items = [];

$agrupar = !empty($meta['agrupar_por_departamento']);

$esc = fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

$STATUS_TEXTO = [
    '1' => 'Activo',
    '2' => 'Inactivo',
    '3' => 'En reparación',
    '4' => 'Dado de baja',
    'EN USO' => 'En uso',
    'DAÑADO' => 'Dañado',
    'EXTRAVIADO' => 'Extraviado',
    'FALTANTE POR UBICAR' => 'Faltante por ubicar',
];

// Agrupar por departamento si aplica
if ($agrupar) {
    $grupos = [];
    foreach ($items as $b) {
        $dep = trim((string)($b['departamento_bien'] ?? '')) ?: 'SIN DEPARTAMENTO';
        $grupos[$dep][] = $b;
    }
    ksort($grupos);
} else {
    $grupos = ['Todos los bienes' => $items];
}
?>

<div class="pdf-content">
    <h1 class="pdf-titulo">Reporte de Bienes</h1>
    <p class="pdf-subtitulo">
        <?= $esc($meta['subtitulo'] ?? 'Inventario general') ?>
        · Total: <strong><?= count($items) ?></strong> bien(es)
        · Generado el <?= date('d/m/Y H:i') ?>
    </p>

    <?php if (empty($items)): ?>
        <p class="text-center text-muted" style="padding:20px 0;">No hay bienes para mostrar.</p>
    <?php else: ?>
        <?php foreach ($grupos as $titulo => $bienes): ?>
            <div class="section-box">
                <div class="section-title">
                    <?= $esc($titulo) ?>
                    <span style="float:right; font-weight:normal; text-transform:none; letter-spacing:0;">
                        (<?= count($bienes) ?> bien<?= count($bienes) !== 1 ? 'es' : '' ?>)
                    </span>
                </div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th width="7%">ID</th>
                            <th width="38%">Descripción</th>
                            <th width="16%">Grupo</th>
                            <th width="14%">Subgrupo</th>
                            <th width="8%">Sección</th>
                            <th width="9%">Adquisición</th>
                            <th width="8%">Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bienes as $b): ?>
                            <?php
                                $statusKey = (string)($b['status_bien'] ?? '');
                                $statusTxt = $STATUS_TEXTO[$statusKey] ?? ($statusKey !== '' ? $statusKey : '—');
                                $fechaAdq = !empty($b['fecha_adquisicion'])
                                    ? date('d/m/Y', strtotime($b['fecha_adquisicion']))
                                    : '—';
                            ?>
                            <tr>
                                <td class="text-center"><?= $esc($b['id_bien'] ?? '') ?></td>
                                <td class="text-upper"><?= $esc($b['descripcion_bien'] ?? '') ?></td>
                                <td><?= $esc($b['grupo'] ?? '—') ?></td>
                                <td><?= $esc($b['subgrupo'] ?? '—') ?></td>
                                <td><?= $esc($b['seccion_id'] ?? '—') ?></td>
                                <td class="text-center" style="font-size:8px;"><?= $fechaAdq ?></td>
                                <td class="text-center" style="font-size:8px;"><?= $esc($statusTxt) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
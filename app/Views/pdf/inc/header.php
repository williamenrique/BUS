<?php
/**
 * Header común para todos los PDFs (excepto Estación).
 */

$inst = $meta['institucion'] ?? ['nombre' => 'INSTITUCIÓN', 'rif' => ''];
$nombreInstitucion = strtoupper($inst['nombre'] ?? 'INSTITUCIÓN');
$rif = $inst['rif'] ?? '';
$color = $meta['doc_color'] ?? '#1e40af';
$tituloDoc = strtoupper($meta['titulo_documento'] ?? '');
$docNumero = $meta['doc_numero'] ?? '';
$extraHeader = $meta['extra_header'] ?? '';

// Logo en base64 (usando variable $logoData para no colisionar con $data)
$logoPath = dirname(__DIR__, 4) . '/public_html/img/logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $tipo = pathinfo($logoPath, PATHINFO_EXTENSION);
    $logoData = file_get_contents($logoPath);
    $logoBase64 = 'data:image/' . $tipo . ';base64,' . base64_encode($logoData);
}

$direccion = 'Av. Principal, Sector Industrial, San Felipe, Edo. Yaracuy';
$telefono  = '0253-0000000';
?>
<table class="pdf-header" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td class="header-col-logo" width="70">
            <?php if ($logoBase64): ?>
                <img src="<?= $logoBase64 ?>" alt="Logo" class="header-logo">
            <?php endif; ?>
        </td>

        <td class="header-col-info">
            <div class="header-nombre"><?= htmlspecialchars($nombreInstitucion) ?></div>
            <div class="header-meta">
                <?php if ($rif): ?>RIF: <?= htmlspecialchars($rif) ?> · <?php endif; ?>
                Tel: <?= htmlspecialchars($telefono) ?>
            </div>
            <div class="header-meta"><?= htmlspecialchars($direccion) ?></div>
            <div class="header-meta">Fecha de Emisión: <?= date('d/m/Y') ?></div>
            <?php if ($extraHeader): ?>
                <div class="header-extra"><?= $extraHeader ?></div>
            <?php endif; ?>
        </td>

        <td class="header-col-doc" width="150">
            <div class="header-doc-tipo"><?= htmlspecialchars($tituloDoc) ?></div>
            <?php if ($docNumero): ?>
                <div class="header-doc-numero"><?= htmlspecialchars($docNumero) ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>
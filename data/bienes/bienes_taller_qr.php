<?php
require_once '../../system/core/Config/config.system.php';

function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

function obtenerBienesTallerPorDepartamento($departamento_id) {
    $conn = conectarDB();
    $sql = "SELECT bt.id_bien_taller, bt.descripcion_bien, bt.bien_depatamento_id, bt.grupo_id, bt.subgrupo_id, bt.seccion_id,
            d.departamento_bien, g.grupo, s.subgrupo, bt.status_bien, bt.fecha_adquisicion, bt.edo, bt.org
            FROM table_bienes_taller_inventario bt
            INNER JOIN table_bienes_departamentos d ON bt.bien_depatamento_id = d.depatamento_bien_id
            LEFT JOIN table_bienes_grupo g ON bt.grupo_id = g.id_grupo
            LEFT JOIN table_bienes_subgrupo s ON bt.subgrupo_id = s.subgrupo_id
            WHERE bt.bien_depatamento_id = ? AND bt.status = 1
            ORDER BY bt.id_bien_taller ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bienes = [];
    while ($row = $result->fetch_assoc()) {
        $bienes[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $bienes;
}

function obtenerDepartamento($departamento_id) {
    $conn = conectarDB();
    $sql = "SELECT * FROM table_bienes_departamentos WHERE depatamento_bien_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $departamento = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $departamento;
}

$departamento_id = isset($_GET['departamento_id']) ? $_GET['departamento_id'] : '';
if (empty($departamento_id)) {
    die('
        <div style="padding: 20px; text-align: center;">
            <h2>Error: Departamento no válido</h2>
            <p>El ID de departamento proporcionado no es válido.</p>
            <a href="javascript:history.back()">← Volver atrás</a>
        </div>
    ');
}

$departamento = obtenerDepartamento($departamento_id);
$bienes = obtenerBienesTallerPorDepartamento($departamento_id);

if (!$departamento) {
    die('
        <div style="padding: 20px; text-align: center;">
            <h2>Error: Departamento no encontrado</h2>
            <p>El departamento solicitado no existe en el sistema.</p>
            <a href="javascript:history.back()">← Volver atrás</a>
        </div>
    ');
}

$fecha_generacion = date('d/m/Y H:i');
$page_title = 'Bienes de Taller del Departamento: ' . htmlspecialchars($departamento['departamento_bien']);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .page-header {
        margin-bottom: 30px;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
    }

    .table-container {
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .badge-estado {
        font-size: 0.85em;
        padding: 5px 10px;
        border-radius: 15px;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="page-header">
            <h1 class="text-center">Bienes de Taller del Departamento:
                <?= htmlspecialchars($departamento['departamento_bien']) ?></h1>
            <p class="text-center text-muted">Código QR generado el: <?= $fecha_generacion ?></p>
        </div>
        <?php if(empty($bienes)): ?>
        <div class="alert alert-info text-center">No hay bienes de taller asignados a este departamento</div>
        <?php else: ?>
        <div class="table-container">
            <table id="tableBienesQR" class="table table-striped table-bordered table-hover" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descripción</th>
                        <th>Grupo</th>
                        <th>Subgrupo</th>
                        <th>Estado</th>
                        <th>Fecha Adquisición</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($bienes as $bien): ?>
                    <tr>
                        <td><?= htmlspecialchars($bien['id_bien_taller']) ?></td>
                        <td><?= htmlspecialchars($bien['descripcion_bien']) ?></td>
                        <td><?= htmlspecialchars($bien['grupo'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($bien['subgrupo'] ?? 'N/A') ?></td>
                        <td>
                            <?php
                                    $estado = $bien['status_bien'] ?? '';
                                    $badgeClass = 'bg-secondary';
                                    switch(strtoupper($estado)) {
                                        case 'EN USO': $badgeClass = 'bg-success'; break;
                                        case 'EN REPARACION': $badgeClass = 'bg-warning text-dark'; break;
                                        case 'DAÑADO': $badgeClass = 'bg-danger'; break;
                                        case 'EXTRAVIADO': $badgeClass = 'bg-info text-dark'; break;
                                    }
                                    ?>
                            <span class="badge badge-estado <?= $badgeClass ?>"><?= htmlspecialchars($estado) ?></span>
                        </td>
                        <td><?= htmlspecialchars($bien['fecha_adquisicion'] ?? 'N/A') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#tableBienesQR').DataTable({
            responsive: true,
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            pageLength: 10,
            order: [
                [0, 'asc']
            ]
        });
    });
    </script>
</body>

</html>
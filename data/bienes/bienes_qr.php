<?php
require_once '../system/core/Config/config.system.php';
function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}
function obtenerBienesPorDepartamento($departamento_id) {
    $conn = conectarDB();
    $sql = "SELECT b.id_bien, b.descripcion_bien, b.bien_depatamento_id, b.grupo_id, b.subgrupo_id, b.seccion_id,
            d.departamento_bien, g.grupo, s.subgrupo, b.status_bien, b.fecha_adquisicion, b.edo, b.org
            FROM table_bienes_inventario b
            INNER JOIN table_bienes_departamentos d ON b.bien_depatamento_id = d.depatamento_bien_id
            LEFT JOIN table_bienes_grupo g ON b.grupo_id = g.id_grupo
            LEFT JOIN table_bienes_subgrupo s ON b.subgrupo_id = s.subgrupo_id
            WHERE b.bien_depatamento_id = ?
            ORDER BY b.id_bien ASC";
    // $sql = "SELECT b.id_bien, b.codigo_bien, b.nombre_bien, b.descripcion_bien, 
    //         g.grupo, s.subgrupo, b.status_bien, b.fecha_adquisicion
    //         FROM table_bienes_inventario b
    //         LEFT JOIN table_bienes_grupo g ON b.grupo_id = g.id_grupo
    //         LEFT JOIN table_bienes_subgrupo s ON b.subgrupo_id = s.subgrupo_id
    //         WHERE b.bien_depatamento_id = ? /*AND b.status_bien != 0*/
    //         ORDER BY b.nombre_bien";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $departamento_id);
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
    $stmt->bind_param("i", $departamento_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $departamento = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $departamento;
}
$departamento_id = isset($_GET['departamento_id']) ? intval($_GET['departamento_id']) : 0;
if ($departamento_id <= 0) {
    die('
        <div style="padding: 20px; text-align: center;">
            <h2>Error: Departamento no válido</h2>
            <p>El ID de departamento proporcionado no es válido.</p>
            <a href="javascript:history.back()">← Volver atrás</a>
        </div>
    ');
}
$departamento = obtenerDepartamento($departamento_id);
$bienes = obtenerBienesPorDepartamento($departamento_id);
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
$page_title = 'Bienes del Departamento: ' . htmlspecialchars($departamento['departamento_bien']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .page-header { margin-bottom: 30px; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .table-container { background-color: white; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 20px; }
        .badge-estado { font-size: 0.85em; padding: 5px 10px; border-radius: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="text-center">Bienes del Departamento: <?= htmlspecialchars($departamento['departamento_bien']) ?></h1>
            <p class="text-center text-muted">Código QR generado el: <?= $fecha_generacion ?></p>
        </div>
        <?php if(empty($bienes)): ?>
            <div class="alert alert-info text-center">No hay bienes asignados a este departamento</div>
        <?php else: ?>
            <div class="table-container">
                <table id="tblBienesDepartamento" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Fecha Adquisición</th>
                            <th data-priority="1">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bienes as $bien): ?>
                            <tr>
                                <td><?= htmlspecialchars($bien['id_bien']) ?></td>
                                <td><?= htmlspecialchars(substr($bien['descripcion_bien'], 0, 150)) . (strlen($bien['descripcion_bien']) > 150 ? '...' : '') ?></td>
                                <td>
                                    <?php 
                                    $estadoText = '';
                                    $estadoClass = '';
                                    switch($bien['status_bien']) {
                                        case 'EN USO': 
                                            $estadoText = 'EN USO';
                                            $estadoClass = 'color: #4ae709;';
                                            break;
                                        case 'EXTRAVIADO': 
                                            $estadoText = 'EXTRAVIADO';
                                            $estadoClass = 'color: #e75d09;';
                                            break;
                                        case 'EN REPARACION': 
                                            $estadoText = 'EN REPARACIÓN';
                                            $estadoClass = 'color: #0993e7;';
                                            break;
                                        case 'DAÑADO': 
                                            $estadoText = 'DAÑADO';
                                            $estadoClass = 'color: #e7091b;';
                                            break;
                                        default: 
                                            $estadoText = htmlspecialchars($bien['status_bien']);
                                            $estadoClass = 'color: #000;';
                                    }
                                    ?>
                                    <span style="font-weight: bold; <?= $estadoClass ?>"><?= $estadoText ?></span>
                                </td>
                                <td><?= $bien['fecha_adquisicion'] ? date('d/m/Y', strtotime($bien['fecha_adquisicion'])) : 'N/A' ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="verDetalleBien(<?= $bien['id_bien'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <!-- Modal para detalles del bien -->
    <div class="modal fade" id="modalDetalleBien" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalles del Bien</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="detalleBienContent">
                    <!-- Contenido cargado por JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#tblBienesDepartamento').DataTable({
            "language": {
                "sProcessing": "Procesando...",
                "sLengthMenu": "Mostrar _MENU_ registros",
                "sZeroRecords": "No se encontraron resultados",
                "sEmptyTable": "Ningún dato disponible en esta tabla",
                "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                "sInfoPostFix": "",
                "sSearch": "Buscar:",
                "sUrl": "",
                "sInfoThousands": ",",
                "sLoadingRecords": "Cargando...",
                "oPaginate": {
                    "sFirst": "Primero",
                    "sLast": "Último",
                    "sNext": "Siguiente",
                    "sPrevious": "Anterior"
                },
                "oAria": {
                    "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                }
            },
            "order": [[0, "asc"]],
            "responsive": true, // Habilitar la funcionalidad responsive
            "pageLength": 10,
            "columnDefs": [ // Definir prioridad de las columnas
                { "responsivePriority": 1, "targets": 4 }, // Acciones
                { "responsivePriority": 2, "targets": 0 }, // Código
                { "responsivePriority": 3, "targets": 1 }  // Descripción
            ]
        })
    })
     // Función para ver detalles del bien
    function verDetalleBien(idBien) {
        // Buscar el bien en los datos ya cargados
        const bienes = <?= json_encode($bienes) ?>;
        const bien = bienes.find(b => b.id_bien == idBien);
        
        if (bien) {
            let contenido = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información Principal</h6>
                        <table class="table table-sm">
                            <tr><th>Código:</th><td>${bien.id_bien}</td></tr>
                            <tr><th>Descripción:</th><td>${bien.descripcion_bien || 'N/A'}</td></tr>
                            <tr><th>Departamento:</th><td>${bien.departamento_bien || 'N/A'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Información Adicional</h6>
                        <table class="table table-sm">
                            <tr><th>Grupo:</th><td>${bien.grupo || 'N/A'}</td></tr>
                            <tr><th>Subgrupo:</th><td>${bien.subgrupo || 'N/A'}</td></tr>
                            <tr><th>Estado:</th><td><span style="font-size:14px">${bien.status_bien || 'N/A'}</span></td></tr>
                            <tr><th>Fecha Adquisición:</th><td>${bien.fecha_adquisicion ? new Date(bien.fecha_adquisicion).toLocaleDateString('es-ES') : 'N/A'}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="accordion mt-3" id="accordionTecnico">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTecnico">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTecnico" aria-expanded="false" aria-controls="collapseTecnico">
                                Ver Información Técnica
                            </button>
                        </h2>
                        <div id="collapseTecnico" class="accordion-collapse collapse" aria-labelledby="headingTecnico" data-bs-parent="#accordionTecnico">
                            <div class="accordion-body">
                                <table class="table table-sm table-bordered">
                                    <tr><th class="w-50">ID Bien:</th><td>${bien.id_bien}</td></tr>
                                    <tr><th>ID Departamento:</th><td>${bien.bien_depatamento_id}</td></tr>
                                    <tr><th>ID Grupo:</th><td>${bien.grupo_id || 'N/A'}</td></tr>
                                    <tr><th>ID Subgrupo:</th><td>${bien.subgrupo_id || 'N/A'}</td></tr>
                                    <tr><th>ID Sección:</th><td>${bien.seccion_id || 'N/A'}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('detalleBienContent').innerHTML = contenido;
            new bootstrap.Modal(document.getElementById('modalDetalleBien')).show();
        }
    }
    </script>
</body>
</html>
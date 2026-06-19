<?= head($data)?>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-bus"></i> <?= $data['page_title'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active"><?= $data['page_title'] ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <button id="btnNuevo" type="button" class="btn btn-primary" onclick="openModal()">
                                <i class="fas fa-plus-circle"></i> Nueva Unidad
                            </button>
                        </div>
                        <div class="card-body">
                            <!-- Sección de Reporte de Operatividad -->
                            <div class="card card-outline card-info mb-4">
                                <div class="card-header">
                                    <h3 class="card-title">Reporte de Operatividad</h3>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-tool" data-card-widget="collapse" id="toggleReportSection">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body" id="reportSection">
                                    <div class="row">
                                        <!-- Columna de Filtros -->
                                        <div class="col-md-6">
                                            <h5>1. Seleccione Grupos</h5>
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                                </div>
                                                <input type="text" id="filtro-grupos-input" class="form-control" placeholder="Buscar grupo por modelo, combustible...">
                                            </div>
                                            <div id="reporte-filtros" class="border rounded p-2" style="max-height: 250px; overflow-y: auto;">
                                                <p class="text-muted">Cargando filtros...</p>
                                            </div>
                                        </div>
                                        <!-- Columna de Vista Previa -->
                                        <div class="col-md-6">
                                            <h5>2. Vista Previa del Reporte</h5>
                                            <div id="reporte-vista-previa" class="border rounded p-3 bg-light" style="min-height: 200px;">
                                                <div id="resumen-operatividad" class="row text-center text-white mb-3">
                                                    <div class="col"><div class="p-2 bg-success rounded">Op.<br><strong id="total-operativas">0</strong></div></div>
                                                    <div class="col"><div class="p-2 bg-warning rounded">Inop.<br><strong id="total-inoperativas">0</strong></div></div>
                                                    <div class="col"><div class="p-2 bg-danger rounded">Crít.<br><strong id="total-criticas">0</strong></div></div>
                                                    <div class="col"><div class="p-2 bg-dark rounded">Total<br><strong id="total-unidades-seleccionadas">0</strong></div></div>
                                                </div>
                                                <div style="max-height: 150px; overflow-y: auto;">
                                                    <table id="tabla-unidades-seleccionadas" class="table table-sm table-bordered d-none">
                                                        <thead class="thead-light">
                                                            <tr><th>Grupo</th><th class="text-center">Cant.</th><th class="text-center">Op.</th><th class="text-center">Inop.</th><th class="text-center">Crít.</th></tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                    <p id="placeholder-vista-previa" class="text-center text-muted pt-4">Seleccione grupos para ver el resumen aquí.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-right">
                                        <button id="btnGenerarPdfOperatividad" class="btn btn-danger" disabled>
                                            <i class="fas fa-file-pdf mr-2"></i>Generar PDF
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenedor para tabla dinámica de flota -->
                            <div id="tableFlota_wrapper">
                                <!-- La tabla se generará dinámicamente por DataTableRefactor.js -->
                            </div>
                        </div>
                    </div>
                </div>

<!-- Deshabilitar DataTables CSS -->
<style>
    .dataTables_wrapper {
        display: none;
    }
    #tableFlota_wrapper {
        display: block !important;
    }
</style>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Modal para Crear/Editar Unidad (Bootstrap) -->
<div class="modal fade" id="modalFlota" tabindex="-1" role="dialog" aria-labelledby="modalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva Unidad</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formFlota" name="formFlota">
                    <input type="hidden" id="id_flota" name="id_flota" value="">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="id_unidad">Identificador Unidad</label>
                            <input type="text" class="form-control" id="id_unidad" name="id_unidad" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="vim_unidad">VIN (Serial Carrocería)</label>
                            <input type="text" class="form-control" id="vim_unidad" name="vim_unidad" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="id_marca">Marca</label>
                            <select id="id_marca" name="id_marca" class="form-control" required></select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="id_modelo">Modelo</label>
                            <select id="id_modelo" name="id_modelo" class="form-control" required></select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="cap_pasajero">Cap. Pasajeros</label>
                            <input type="number" class="form-control" id="cap_pasajero" name="cap_pasajero">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="fecha_creacion">Año de Fabricación</label>
                            <input type="number" class="form-control" id="fecha_creacion" name="fecha_creacion" placeholder="YYYY" min="1900" max="2099">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="tipo_combustible">Tipo de Combustible</label>
                            <select id="tipo_combustible" name="tipo_combustible" class="form-control">
                                <option value="DIESEL">DIESEL</option>
                                <option value="GASOLINA">GASOLINA</option>
                                <option value="GAS">GAS</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="transmision">Transmisión</label>
                            <select id="transmision" name="transmision" class="form-control">
                                <option value="SINCRONICO">SINCRÓNICO</option>
                                <option value="AUTOMATICO">AUTOMÁTICO</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" id="btnActionForm" class="btn btn-primary" form="formFlota"><span id="btnText">Guardar</span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Detalles (Bootstrap) -->
<div class="modal fade" id="modalViewUnidad" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Contenido se cargará dinámicamente vía JS -->
        </div>
    </div>
</div>

<!-- Modal para Cambiar Status (Bootstrap) -->
<div class="modal fade" id="modalStatus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado de Unidad</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="formStatus" name="formStatus">
                    <input type="hidden" id="status_id_flota" name="id_flota" value="">
                    <p>Unidad: <strong id="statusUnidadId"></strong></p>
                    <p class="mb-3">Estado Actual: <strong id="statusActual"></strong></p>
                    <div class="form-group">
                        <label for="status">Nuevo Estado</label>
                        <select id="status" name="status" class="form-control" required></select>
                    </div>
                    <div class="form-group">
                        <label for="motivo">Motivo del Cambio</label>
                        <textarea id="motivo" name="motivo" rows="3" class="form-control" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" form="formStatus">Actualizar Estado</button>
            </div>
        </div>
    </div>
</div>

<?= footer($data)?>
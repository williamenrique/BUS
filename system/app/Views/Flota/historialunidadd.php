<?= head($data)?>
<!-- ID de la flota para ser usado por JS -->
<input type="hidden" id="id_flota" value="<?= $data['id_flota'] ?>">

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-history"></i> Historial de la Unidad</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>flota">Flota</a></li>
                        <li class="breadcrumb-item active">Historial</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Cabecera de la página -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Unidad: <span class="font-weight-bold"><?= $data['unidad']['id_unidad'] ?></span></h3>
                    <div class="card-tools">
                        <a href="<?= base_url() ?>flota" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Volver a la Flota
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Marca:</strong><p class="text-muted"><?= $data['unidad']['marca_unidad'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Modelo:</strong><p class="text-muted"><?= $data['unidad']['modelo_unidad'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>VIN:</strong><p class="text-muted"><?= $data['unidad']['vim_unidad'] ?></p>
                        </div>
                        <div class="col-md-3">
                            <strong>Kilometraje Actual:</strong>
                            <p class="text-muted">
                                <?= isset($data['unidad']['kilometraje_actual']) && is_numeric($data['unidad']['kilometraje_actual'])
                                    ? number_format($data['unidad']['kilometraje_actual'], 0, ',', '.') . ' Km' 
                                    : 'No registrado' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="card">
                <div class="card-body">
                    <form id="formFiltros">
                        <div class="row align-items-end">
                            <div class="col-md-3 form-group">
                                <label for="filtroFecha">Buscar por Fecha</label>
                                <input type="date" id="filtroFecha" name="filtroFecha" class="form-control">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="filtroTipo">Tipo de Evento</label>
                                <select id="filtroTipo" name="filtroTipo" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="despacho">Orden de Despacho</option>
                                    <option value="aceite">Cambio de Aceite</option>
                                    <option value="mantenimiento">Mantenimiento</option>
                                    <option value="status">Cambio de Estado</option>
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="filtroTermino">Orden / Término</label>
                                <input type="text" id="filtroTermino" name="filtroTermino" placeholder="Ej: #123, Repuesto..." class="form-control">
                            </div>
                            <div class="col-md-3 form-group">
                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search"></i> Buscar</button>
                                <button type="button" id="btnLimpiarFiltros" class="btn btn-secondary btn-block mt-2">Limpiar</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Timeline del Historial -->
            <div id="timelineContainer">
                <!-- Los items del timeline se insertarán aquí dinámicamente -->
            </div>

            <!-- Estado de Carga y Sin Resultados -->
            <div id="timeline-loader" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Cargando...</span>
                </div>
            </div>

            <div id="timeline-empty" class="text-center py-5 d-none">
                <i class="fas fa-box-open fa-3x text-muted"></i>
                <h5 class="mt-3">No se encontraron eventos</h5>
                <p class="text-muted">Intenta con otros filtros o revisa más tarde.</p>
            </div>

            <!-- Paginación -->
            <div id="pagination-container" class="mt-4 d-flex justify-content-center"></div>
        </div>
    </section>
</div>
<?= footer($data)?>


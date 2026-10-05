<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-box-open"></i> <?= $data['page_title'] ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Productos</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Columna de Formularios -->
                <div class="col-md-4">
                    <!-- Card para Crear Producto -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Crear Nuevo Producto</h3>
                        </div>
                        <div class="card-body">
                            <form id="formNewArticulo">
                                <div class="form-group">
                                    <label for="txtArticulo">Nombre del Artículo *</label>
                                    <input type="text" id="txtArticulo" name="txtArticulo" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="listEnlace">Tipo/Enlace *</label>
                                    <select id="listEnlace" name="listEnlace" class="form-control" required></select>
                                </div>
                                <div class="form-group">
                                    <label for="listProveedor">Proveedor *</label>
                                    <select id="listProveedor" name="listProveedor" class="form-control" required></select>
                                </div>
                                <div class="form-group">
                                    <label for="listUbicacion">Ubicación *</label>
                                    <select id="listUbicacion" name="listUbicacion" class="form-control" required></select>
                                </div>
                                <div class="form-group">
                                    <label for="txtCantidad">Cantidad Inicial *</label>
                                    <input type="number" id="txtCantidad" name="txtCantidad" min="0" step="0.01" class="form-control" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Tipo de Artículo</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="optionsArticulo" value="1" id="radioConsumible" checked>
                                            <label class="form-check-label" for="radioConsumible">Consumible</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="optionsArticulo" value="2" id="radioRepuesto">
                                            <label class="form-check-label" for="radioRepuesto">Repuesto</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Presentación</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="optionsPresentacion" value="UNIDAD" id="radioUnidad" checked>
                                            <label class="form-check-label" for="radioUnidad">Unidad</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="optionsPresentacion" value="LITRO" id="radioLitro">
                                            <label class="form-check-label" for="radioLitro">Litro</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="optionsPresentacion" value="JUEGO" id="radioJuego">
                                            <label class="form-check-label" for="radioJuego">Juego</label>
                                        </div>
                                        <!-- Agrega más si es necesario -->
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Guardar Producto</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Card para Agregar Stock -->
                    <div class="card card-warning card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-cubes"></i> Agregar Stock</h3>
                        </div>
                        <div class="card-body">
                            <form id="formArticuloExistente">
                                <div class="form-group">
                                    <label for="listArticuloExistente">Seleccionar Artículo *</label>
                                    <select id="listArticuloExistente" name="listArticuloExistente" class="form-control" required></select>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="txtCantidadActual">Stock Actual</label>
                                        <input type="text" id="txtCantidadActual" name="txtCantidadActual" class="form-control" readonly>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="txtCantidadMas">Cantidad a Agregar *</label>
                                        <input type="number" id="txtCantidadMas" name="txtCantidadMas" min="0" step="0.01" class="form-control" required>
                                    </div>
                                </div>
                                <div class="card-footer text-center">
                                    <button type="submit" class="btn btn-warning btn-block"><i class="fas fa-plus"></i> Actualizar Stock</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Columna de la Tabla -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3 class="card-title">Inventario de Productos</h3>
                                <div class="card-tools">
                                    <button onclick="fntReporteProductosPDF()" class="btn btn-danger btn-sm">
                                        <i class="fas fa-file-pdf"></i> Generar PDF
                                    </button>
                                    <button onclick="reloadTable()" class="btn btn-secondary btn-sm" title="Recargar tabla">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tableProducto" class="table table-bordered table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Artículo</th>
                                            <th>Tipo</th>
                                            <th>Proveedor</th>
                                            <th>Ubicación</th>
                                            <th>Stock</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data will be loaded by DataTable -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?= footer($data)?>
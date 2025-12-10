<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-broom"></i> Mantenimiento de Almacén</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active">Mantenimiento</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <!-- Columna de Selección -->
                <div class="col-md-5">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-search"></i> Seleccionar Producto</h3>
                        </div>
                        <div class="card-body">
                            <p class="card-text text-muted">Usa el buscador para encontrar el artículo que deseas editar. Sus detalles aparecerán a la derecha.</p>
                            <div class="form-group">
                                <label for="listProductoStock">Seleccionar Artículo para Editar *</label>
                                <select id="listProductoStock" name="listProductoStock" class="form-control select2" required></select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna de Edición -->
                <div class="col-md-7">
                    <!-- Formulario de Edición (ahora visible por defecto) -->
                    <div id="formUpdateProductoContainer" class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-edit"></i> Ficha del Producto</h3>
                        </div>
                        <div class="card-body">
                            <form id="formUpdateProducto">
                                <input type="hidden" id="id_producto" name="id_producto">
                                
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label for="txtArticulo">Nombre del Artículo *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-tag"></i></span></div>
                                            <input type="text" id="txtArticulo" name="txtArticulo" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="listEnlace">Tipo / Enlace *</label>
                                        <select id="listEnlace" name="listEnlace" class="form-control select2" required></select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="listProveedor">Proveedor *</label>
                                        <select id="listProveedor" name="listProveedor" class="form-control select2" required></select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="listUbicacion">Ubicación *</label>
                                        <select id="listUbicacion" name="listUbicacion" class="form-control select2" required></select>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="txtCantidad">Cantidad en Stock *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-cubes"></i></span></div>
                                            <input type="number" id="txtCantidad" name="txtCantidad" min="0" step="0.01" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label for="optionsPresentacion">Unidad de Medida *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-ruler-combined"></i></span></div>
                                            <select id="optionsPresentacion" name="optionsPresentacion" class="form-control" required>
                                                <option value="">Seleccionar</option>
                                                <option value="UNIDAD">UNIDAD</option>
                                                <option value="LITRO">LITRO</option>
                                                <option value="METRO">METRO</option>
                                                <option value="KILO">KILO</option>
                                                <option value="JUEGO">JUEGO</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Tipo de Artículo *</label>
                                    <div class="d-flex">
                                        <div class="form-check mr-4">
                                            <input class="form-check-input" type="radio" name="optionsArticulo" id="radioConsumible" value="1" required>
                                            <label class="form-check-label" for="radioConsumible">Consumible</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="optionsArticulo" id="radioRepuesto" value="2">
                                            <label class="form-check-label" for="radioRepuesto">Repuesto</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent text-right">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                                </div>
            </div>
        </div>
    </section>
</div>

<?= footer($data)?>
<div id="dashboard-operaciones">
    <!-- Estado General de la Flota -->
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner"><h3 id="unidades-operativas">0</h3><p>Unidades Operativas</p></div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner"><h3 id="unidades-inoperativas">0</h3><p>Unidades Inoperativas</p></div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner"><h3 id="unidades-mantenimiento">0</h3><p>En Mantenimiento</p></div>
                <div class="icon"><i class="fas fa-tools"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-purple">
                <div class="inner"><h3 id="unidades-criticas">0</h3><p>Críticas</p></div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    <!-- Estado de Cambio de Aceite -->
    <div class="row">
        <div class="col-md-4"><a href="<?= base_url() ?>Flota/statusaceite?filtro=requerido" class="info-box bg-gradient-danger"><span class="info-box-icon"><i class="fas fa-oil-can"></i></span><div class="info-box-content"><span class="info-box-text">Cambio Requerido</span><span id="aceite-requerido" class="info-box-number">0</span></div></a></div>
        <div class="col-md-4"><a href="<?= base_url() ?>Flota/statusaceite?filtro=proximo" class="info-box bg-gradient-warning"><span class="info-box-icon"><i class="fas fa-history"></i></span><div class="info-box-content"><span class="info-box-text">Próximo a Cambio</span><span id="aceite-proximo" class="info-box-number">0</span></div></a></div>
        <div class="col-md-4"><a href="<?= base_url() ?>Flota/statusaceite?filtro=ok" class="info-box bg-gradient-success"><span class="info-box-icon"><i class="fas fa-thumbs-up"></i></span><div class="info-box-content"><span class="info-box-text">Mantenimiento OK</span><span id="aceite-ok" class="info-box-number">0</span></div></a></div>
    </div>

    <!-- Tabla Resumen de Flota -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Resumen de Flota por Modelo</h3></div>
                <div class="card-body table-responsive p-0" style="max-height: 300px;">
                    <table class="table table-head-fixed text-nowrap">
                        <thead>
                            <tr>
                                <th>Marca / Modelo</th>
                                <th>Transmisión</th>
                                <th>Combustible</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Operativas</th>
                                <th class="text-center">Inoperativas</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-resumen-flota">
                            <tr><td colspan="6" class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
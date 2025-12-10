<div id="dashboard-compras">
    <div class="row">
        <!-- Tarjeta de Nuevas Requisiciones -->
        <div class="col-lg-6 col-md-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 id="nuevas-requisiciones">0</h3>
                    <p>Nuevas Requisiciones Pendientes</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
                <a href="<?= base_url() ?>requisicion" class="small-box-footer">Gestionar <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- Tarjeta de Artículos sin Stock -->
        <div class="col-lg-6 col-md-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 id="articulos-sin-stock">0</h3>
                    <p>Artículos Sin Stock</p>
                </div>
                <div class="icon"><i class="fas fa-box-open"></i></div>
                <a href="<?= base_url() ?>producto/inventario" class="small-box-footer">Ver Inventario <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>
</div>
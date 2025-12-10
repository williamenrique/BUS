<div id="dashboard-almacen">
    <!-- Fila de 4 tarjetas -->
    <div class="row d-flex align-items-stretch">
        <!-- Tarjeta de Órdenes por Despachar (Requisiciones Aprobadas) -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3 id="ordenes-aprobadas">0</h3>
                    <p>Órdenes por Despachar</p>
                </div>
                <div class="icon"><i class="fas fa-check-double"></i></div>
                <a href="<?= base_url() ?>orden/despachosPendientes" class="small-box-footer">Gestionar <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <!-- Tarjeta de Órdenes Despachadas (Mes Actual) -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3 id="ordenes-despachadas">0</h3>
                    <p>Órdenes Despachadas (Mes)</p>
                </div>
                <div class="icon"><i class="fas fa-truck-loading"></i></div>
                <a href="<?= base_url() ?>orden" class="small-box-footer">Ver Historial <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>

        <!-- Tarjeta de Consumo de Lubricantes (Mes Actual) -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="small-box bg-primary">
                <div class="inner"><h3 id="lubricantes-actual">0 Lts.</h3><p>Consumo Lubricantes (Mes)</p></div>
                <div class="icon"><i class="fas fa-oil-can"></i></div>
                <a href="#" class="small-box-footer" style="visibility: hidden;">&nbsp;</a>
            </div>
        </div>

        <!-- Tarjeta de Producto Más Despachado -->
        <div class="col-lg-3 col-md-6 col-sm-12">
            <div class="small-box bg-warning">
                <div class="inner"><h3 id="top-producto-mes" style="font-size: 1.5rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">N/A</h3><p>Producto Más Despachado (Mes)</p></div>
                <div class="icon"><i class="fas fa-star"></i></div>
                <a href="#" class="small-box-footer" style="visibility: hidden;">&nbsp;</a>
            </div>
        </div>
    </div>
</div>
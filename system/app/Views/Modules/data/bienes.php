<div id="dashboard-bienes">
    <div class="card card-outline card-success">
        <div class="card-header"><h3 class="card-title">Resumen de Bienes</h3></div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 col-lg-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-archive"></i></span>
                        <div class="info-box-content"><span class="info-box-text">Total de Bienes</span><span id="bienes-total" class="info-box-number">0</span></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-success elevation-1"><i class="fas fa-check-circle"></i></span>
                        <div class="info-box-content"><span class="info-box-text">Activos</span><span id="bienes-activos" class="info-box-number">0</span></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-tools"></i></span>
                        <div class="info-box-content"><span class="info-box-text">En Reparación</span><span id="bienes-reparacion" class="info-box-number">0</span></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="info-box mb-3">
                        <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-times-circle"></i></span>
                        <div class="info-box-content"><span class="info-box-text">De Baja</span><span id="bienes-baja" class="info-box-number">0</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">Últimos Bienes Registrados</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm">
                <thead>
                    <tr><th>Descripción</th><th>Dpto.</th><th>Fecha</th></tr>
                </thead>
                <tbody id="tabla-bienes-recientes"></tbody>
            </table>
        </div>
    </div>
</div>
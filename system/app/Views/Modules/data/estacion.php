<div id="dashboard-estacion">
    <!-- Resumen del Día -->
    <div class="row">
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-info">
                <div class="inner"><h3 id="totalVentasHoy">0</h3><p>Total Ventas Hoy</p></div>
                <div class="icon"><i class="fas fa-ticket-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="small-box bg-success">
                <div class="inner"><h3 id="totalLitrosHoy">0.00 L</h3><p>Total Litros Vendidos</p></div>
                <div class="icon"><i class="fas fa-gas-pump"></i></div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="small-box bg-warning">
                <div class="inner"><h3 id="user_activo">0</h3><p>Vendedores Activos</p></div>
                <div class="icon"><i class="fas fa-user-clock"></i></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Ventas del Día por Usuario</h3></div>
                <div class="card-body"><div class="chart"><canvas id="dailySalesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas></div></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Ventas Mensuales (Litros)</h3></div>
                <div class="card-body">
                    <div class="form-row align-items-end">
                        <div class="form-group col-md-5"><label for="startMonth">Desde</label><select id="startMonth" class="form-control"></select></div>
                        <div class="form-group col-md-5"><label for="endMonth">Hasta</label><select id="endMonth" class="form-control"></select></div>
                        <div class="form-group col-md-2"><button id="generateChartBtn" class="btn btn-primary btn-block">Generar</button></div>
                    </div>
                    <div class="chart"><canvas id="monthlyLitersChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas></div>
                    <div id="noLitersDataMessage" class="text-center text-muted py-5" style="display: none;">Selecciona un rango y haz clic en 'Generar'.</div>
                </div>
            </div>
        </div>
    </div>
</div>
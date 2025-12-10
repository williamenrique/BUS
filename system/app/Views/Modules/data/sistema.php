<div class="row">
    <!-- Columna para tarjetas de gestión -->
    <div class="col-12">
        <div id="active-users-card" class="card card-outline card-primary collapsed-card">
            <div class="card-header" data-card-widget="collapse" style="cursor: pointer;">
                <h3 class="card-title"><i class="fas fa-user-clock mr-2"></i>Sesiones Activas</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool"><i class="fas fa-plus"></i></button></div>
            </div>
            <div class="card-body p-0" id="active-users-content" style="display: none;">
                <ul class="list-group list-group-flush" id="active-users-list">
                    <!-- Los usuarios se cargarán aquí -->
                </ul>
                <div id="no-active-users" class="text-center text-muted py-3" style="display: none;">No hay sesiones activas.</div>
            </div>
        </div>

        <div class="card card-outline card-purple collapsed-card">
            <div class="card-header" data-card-widget="collapse" style="cursor: pointer;">
                <h3 class="card-title"><i class="fas fa-users-cog mr-2"></i>Gestión General de Usuarios</h3>
                <div class="card-tools"><button type="button" class="btn btn-tool"><i class="fas fa-plus"></i></button></div>
            </div>
            <div class="card-body" style="display: none;">
                <table id="all-users-table" class="table table-bordered table-striped display responsive w-full">
                    <thead>
                        <tr>
                            <th>ID</th><th>Nombre Completo</th><th>Usuario</th><th>Rol</th><th>Departamento</th><th>Estado</th><th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Columna para dashboards informativos -->
    <div class="col-12">
        <?php include_once('operaciones.php'); ?>
        <?php include_once('almacen.php'); ?>
        <?php include_once('compras.php'); ?>
        <?php include_once('estacion.php'); ?>
        <?php include_once('bienes.php'); ?>
    </div>
</div>
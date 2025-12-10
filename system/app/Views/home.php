<?php head($data); ?>


    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                         <!-- <?= dep( $_SESSION['userData']) ?> -->
                    <?php if (isset($data['dashboard_view']) && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $data['dashboard_view'])) : ?>
                        <?php include_once(__DIR__ . DIRECTORY_SEPARATOR . $data['dashboard_view']); ?>
                    <?php else : ?> 
                        <div class="alert alert-warning" role="alert">
                            <h4 class="alert-heading">¡Bienvenido, <?= htmlspecialchars($_SESSION['userData']['personal_nombre'], ENT_QUOTES, 'UTF-8') ?>!</h4>
                            <p>No se ha configurado un dashboard para su departamento.</p>
                            <?php if (isset($data['dashboard_view']) && !file_exists(__DIR__ . DIRECTORY_SEPARATOR . $data['dashboard_view'])) : ?>
                                <hr>
                                <p class="mb-0 text-danger small">Error de configuración: No se encuentra el archivo de la vista: <strong><?= htmlspecialchars($data['dashboard_view']) ?></strong></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <!-- /box end -->
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<!-- Modal selección de estación (mostrar solo si es departamento ESTACION y no tiene estación asignada) -->
<!-- <div class="modal fade" id="selectEstacionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar estación</h5>
            </div>
            <div class="modal-body">
                <p>Seleccione en qué estación está ingresando:</p>
                <div class="d-flex justify-content-between">
                    <button id="btnEst1" class="btn btn-primary" data-est="1">E/S Tachira</button>
                    <button id="btnEst2" class="btn btn-secondary" data-est="2">E/S Gran Parada</button>
                </div>
            </div>
        </div>
    </div>
</div> -->

<?php footer($data); ?>
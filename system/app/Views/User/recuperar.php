<?= head($data)?>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                    <!-- Default box -->
                    <!-- Cabecera de la sección -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Solicitudes de Recuperación de Cuentas</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Usuarios que han solicitado ayuda para acceder a sus cuentas.
                                </p>
                            </div>
                        </div>

                        <!-- Contenedor para las tarjetas de solicitud -->
                        <div id="recoveryRequestsContainer" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <!-- Las tarjetas de usuario se cargarán aquí dinámicamente -->
                        </div>

                        <!-- Mensaje para cuando no hay solicitudes -->
                        <div id="noRequestsMessage" class="hidden text-center py-10 px-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
                            <i class="fas fa-check-circle fa-3x text-green-500 mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200">¡Todo en orden!</h3>
                            <p class="text-gray-500 dark:text-gray-400 mt-2">No hay solicitudes de recuperación pendientes en este momento.</p>
                        </div>
                    <!-- /box end -->
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<?= footer($data)?>
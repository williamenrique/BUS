<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-warehouse"></i> Inventario de Productos</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>dashboard">Inicio</a></li>
                        <li class="breadcrumb-item active">Inventario</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title">Listado de Inventario</h3>
                            <div class="card-tools">
                                <button onclick="fntReporteInventarioPDF()" class="btn btn-danger btn-sm">
                                    <i class="fas fa-file-pdf"></i> Generar PDF
                                </button>
                                <button onclick="reloadInventarioTable()" class="btn btn-secondary btn-sm" title="Recargar tabla">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Contenedor para tabla dinámica de inventario -->
                            <div id="tableInventario_wrapper">
                                <!-- La tabla se generará dinámicamente por DataTableRefactor.js -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Deshabilitar DataTables CSS -->
<style>
    .dataTables_wrapper {
        display: none;
    }
    #tableInventario_wrapper {
        display: block !important;
    }
</style>
<?= footer($data)?>
<?= head($data)?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
<!-- Deshabilitar DataTables CSS ya que usaremos nuestro sistema -->
<style>
    .dataTables_wrapper {
        display: none;
    }
    #usuariosTable_wrapper {
        display: block !important;
    }
</style>

<!-- Estilos personalizados para Select2 -->
<style>
    /* Limita la altura del desplegable de Select2 y añade scroll */
    .select2-custom-dropdown .select2-results__options {
        max-height: 200px; /* Altura máxima deseada */
        overflow-y: auto;
    }
    /* Asegurar que el Select2 tenga la apariencia de un form-control de Bootstrap */
    .select2-container--bootstrap4 .select2-selection--single {
        height: calc(2.25rem + 2px) !important;
        border: 1px solid #ced4da;
        border-radius: .25rem;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered {
        line-height: 2.25rem;
        padding-left: .75rem;
    }
    .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow {
        height: calc(2.25rem + 2px);
    }
</style>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <!-- Formulario de Creación -->
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-user-plus"></i> Formulario de Creación de Usuario</h3>
                        </div>
                        <form id="userCreateForm">
                            <div class="card-body">
                                <input type="hidden" id="userCreateId" name="userCreateId" value="0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="selectPersonal">Buscar Personal (Cédula o Nombre) *</label>
                                            <!-- Este select será controlado por Select2 -->
                                            <select id="selectPersonal" name="id_personal_fk" class="form-control" required>
                                                <option value=""></option> <!-- Opción vacía para el placeholder de Select2 -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="txtTelefono">Teléfono</label>
                                            <input type="tel" id="txtTelefono" name="txtTelefono" class="form-control" onkeypress="return soloNumeros(event);" placeholder="Número de teléfono">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="txtNombre">Nombres *</label>
                                            <input type="text" id="txtNombre" name="txtNombre" class="form-control" required onkeypress="return soloLetras(event);" placeholder="Nombres del usuario">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="txtApellido">Apellidos *</label>
                                            <input type="text" id="txtApellido" name="txtApellido" class="form-control" required onkeypress="return soloLetras(event);" placeholder="Apellidos del usuario">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="txtEmail">Email *</label>
                                            <input type="email" id="txtEmail" name="txtEmail" class="form-control" required placeholder="correo@ejemplo.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="txtDireccion">Dirección</label>
                                            <input type="text" id="txtDireccion" name="txtDireccion" class="form-control" placeholder="Dirección de habitación">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="listRolId">Rol *</label>
                                            <select id="listRolId" name="listRolId" class="form-control" required>
                                                <option value="">Seleccionar rol</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="listDep">Departamento</label>
                                            <select id="listDep" name="listDep" class="form-control">
                                                <option value="">Seleccionar departamento</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="button" id="cancelCreateBtn" class="btn btn-secondary">
                                    <i class="fas fa-times-circle"></i> Cancelar
                                </button>
                                <button type="submit" id="saveCreateBtn" class="btn btn-primary float-right">
                                    <i class="fas fa-save"></i> Crear Usuario
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Tabla de Usuarios -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-users"></i> Listado de Usuarios Registrados</h3>
                        </div>
                        <div class="card-body">
                            <!-- Contenedor para la tabla dinámica -->
                            <div id="usuariosTable_wrapper">
                                <!-- La tabla se generará dinámicamente por DataTableRefactor.js -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal para Editar Usuario (Bootstrap) -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header card-header">
                <h5 class="modal-title" id="editUserModalLabel"><i class="fas fa-user-edit"></i> Editar Usuario</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="editUsuarioId" name="usuario_id">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioCi">Identificación</label>
                            <input type="text" id="editUsuarioCi" name="personal_cedula" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioNombres">Nombres</label>
                            <input type="text" id="editUsuarioNombres" name="personal_nombre" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioApellidos">Apellidos</label>
                            <input type="text" id="editUsuarioApellidos" name="personal_apellido" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioEmail">Email</label>
                            <input type="email" id="editUsuarioEmail" name="personal_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioTelefono">Teléfono</label>
                            <input type="tel" id="editUsuarioTelefono" name="personal_tlf" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioDireccion">Dirección</label>
                            <input type="text" id="editUsuarioDireccion" name="personal_direccion" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioRolId">Rol</label>
                            <select id="editUsuarioRolId" name="usuario_rol_id" class="form-control" required></select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioDepartamentoId">Departamento</label>
                            <select id="editUsuarioDepartamentoId" name="usuario_departamento_id" class="form-control"></select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="editUsuarioStatus">Estado</label>
                            <select id="editUsuarioStatus" name="usuario_status" class="form-control" required></select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<?= footer($data)?>
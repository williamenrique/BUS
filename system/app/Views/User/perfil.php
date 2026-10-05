<?= head($data)?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="far fa-user-circle"></i> Perfil de Usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= base_url() ?>"><i class="fas fa-home"></i> Inicio</a></li>
                        <li class="breadcrumb-item active">Perfil</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-3">
                    <!-- Profile Image -->
                    <div class="card card-primary card-outline">
                        <div class="card-body box-profile">
                            <div class="text-center">
                                <input type="file" id="profileImageInput" accept="image/*" style="display: none;">
                                <input type="hidden" name="user" id="user" value="<?= $_SESSION['userData']['usuario_id']?>">
                                <img class="profile-user-img img-fluid img-circle"
                                     src="<?= BASE_URL().'/'.$_SESSION['userData']['usuario_imagen']?>"
                                     alt="User profile picture" id="profileImageLarge" style="cursor:pointer;" title="Cambiar imagen">
                            </div>

                            <h3 class="profile-username text-center"><?= $_SESSION['userData']['personal_nombre'].' '.$_SESSION['userData']['personal_apellido']?></h3>
                            <p class="text-muted text-center"><?= $_SESSION['userData']['rol_nombre'] ?></p>

                            <div class="text-center mt-3" style="display: none;" id="imageActions">
                                <button type="button" class="btn btn-sm btn-primary" id="saveImageBtn"><i class="fas fa-save"></i> Guardar</button>
                                <button type="button" class="btn btn-sm btn-danger" id="removeImageBtn"><i class="fas fa-trash"></i></button>
                                <button type="button" class="btn btn-sm btn-secondary" id="cancelImageBtn"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#datos" data-toggle="tab">Datos Personales</a></li>
                                <li class="nav-item"><a class="nav-link" href="#password" data-toggle="tab">Cambiar Contraseña</a></li>
                            </ul>
                        </div><!-- /.card-header -->
                        <div class="card-body">
                            <div class="tab-content">
                                <!-- Tab Datos Personales -->
                                <div class="active tab-pane" id="datos">
                                    <form class="form-horizontal" id="userDataForm" data-user-id="<?= $_SESSION['userData']['usuario_id'] ?? '' ?>">
                                        <div class="form-group row">
                                            <label for="usuario_nombres" class="col-sm-2 col-form-label">Nombres</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="usuario_nombres" name="usuario_nombres" value="<?= $_SESSION['userData']['personal_nombre']?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="usuario_apellidos" class="col-sm-2 col-form-label">Apellidos</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="usuario_apellidos" name="usuario_apellidos" value="<?= $_SESSION['userData']['personal_apellido'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="usuario_email" class="col-sm-2 col-form-label">Email</label>
                                            <div class="col-sm-10">
                                                <input type="email" class="form-control" id="usuario_email" name="usuario_email" value="<?= $_SESSION['userData']['personal_email'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="usuario_telefono" class="col-sm-2 col-form-label">Teléfono</label>
                                            <div class="col-sm-10">
                                                <input type="tel" class="form-control" id="usuario_telefono" name="usuario_telefono" value="<?= $_SESSION['userData']['personal_tlf'] ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="usuario_direccion" class="col-sm-2 col-form-label">Dirección</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" id="usuario_direccion" name="usuario_direccion"><?= $_SESSION['userData']['personal_direccion'] ?? '' ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-2 col-sm-10">
                                                <button type="button" class="btn btn-secondary" id="cancelBtn"><i class="fas fa-times"></i> Cancelar</button>
                                                <button type="submit" class="btn btn-primary" id="saveBtn"><i class="fas fa-save"></i> Guardar Datos</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.tab-pane -->

                                <!-- Tab Cambiar Contraseña -->
                                <div class="tab-pane" id="password">
                                    <form class="form-horizontal">
                                        <div class="form-group row">
                                            <label for="currentPassword" class="col-sm-3 col-form-label">Contraseña Actual</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="currentPassword" placeholder="Contraseña actual">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="newPassword" class="col-sm-3 col-form-label">Nueva Contraseña</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="newPassword" placeholder="Nueva contraseña">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="confirmPassword" class="col-sm-3 col-form-label">Confirmar Contraseña</label>
                                            <div class="col-sm-9">
                                                <input type="password" class="form-control" id="confirmPassword" placeholder="Confirmar contraseña">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <div class="offset-sm-3 col-sm-9">
                                                <button type="button" class="btn btn-secondary" id="cancelPasswordBtn"><i class="fas fa-times"></i> Cancelar</button>
                                                <button type="button" class="btn btn-primary" id="savePasswordBtn"><i class="fas fa-key"></i> Cambiar Contraseña</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <!-- /.tab-pane -->
                            </div>
                            <!-- /.tab-content -->
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<?= footer($data)?>
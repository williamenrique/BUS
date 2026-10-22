<?php
// Vista: Mi Perfil
// Variables recibidas: $user (sesión), $perfil (BD), $menu
$p         = $perfil ?? [];
$nick      = $p['usuario_nick']      ?? ($user['nick']      ?? '');
$rol       = $p['rol_nombre']        ?? ($user['rol_nombre'] ?? '');
$depto     = $p['departamento_nombre'] ?? '';
$estacion  = $p['estacion_nombre']   ?? '';
$creado    = $p['usuario_creado']    ?? '';
$nombre    = $p['personal_nombre']   ?? '';
$apellido  = $p['personal_apellido'] ?? '';
$email     = $p['personal_email']    ?? '';
$cedula    = $p['personal_cedula']   ?? '';
$tlf       = $p['personal_tlf']      ?? '';

// Avatar actual
$imagen    = $p['usuario_imagen'] ?? '';
$avatarUrl = !empty($imagen)
    ? asset('uploads/storage/' . $imagen)
    : asset('img/default.png');

// Iniciales para fallback
$iniciales = strtoupper(
    mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1)
);
if ($iniciales === '') $iniciales = strtoupper(mb_substr($nick, 0, 1));
?>

<div class="page-header">
    <div>
        <h2 class="page-title">Mi Perfil</h2>
        <p class="page-subtitle">Administra tu información personal y tu imagen</p>
    </div>
</div>

<div class="perfil-layout">

    <!-- ============================================ -->
    <!-- COLUMNA IZQUIERDA · Avatar + info fija       -->
    <!-- ============================================ -->
    <aside class="perfil-side card">
        <div class="perfil-avatar-wrap">
            <div class="perfil-avatar" id="perfilAvatar">
                <?php if (!empty($imagen)): ?>
                    <img src="<?= e($avatarUrl) ?>" alt="Avatar" id="perfilAvatarImg"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="perfil-avatar-fallback" style="display:none;"><?= e($iniciales) ?></div>
                <?php else: ?>
                    <div class="perfil-avatar-fallback"><?= e($iniciales) ?></div>
                <?php endif; ?>
            </div>

            <!-- Botones de imagen -->
            <div class="perfil-avatar-actions">
                <label for="perfilFile" class="btn btn-primary btn-sm" style="cursor:pointer;">
                    <?= icon_tag('upload', 'upload', 'w-4 h-4') ?>
                    <span>Subir imagen</span>
                    <input type="file" id="perfilFile" accept="image/jpeg,image/png,image/webp" hidden>
                </label>

                <button type="button" id="btnEliminarImagen" class="btn btn-ghost btn-sm">
                    <?= icon_tag('trash-2', 'trash-2', 'w-4 h-4') ?>
                    <span>Quitar</span>
                </button>
            </div>

            <p class="perfil-avatar-help">JPG, PNG o WEBP · máx 2MB · 2000x2000 px</p>
        </div>

        <div class="perfil-info-list">
            <div class="perfil-info-row">
                <span class="perfil-info-label">Usuario</span>
                <strong><?= e($nick) ?></strong>
            </div>
            <div class="perfil-info-row">
                <span class="perfil-info-label">Rol</span>
                <span class="badge badge-info"><?= e($rol) ?></span>
            </div>
            <div class="perfil-info-row">
                <span class="perfil-info-label">Departamento</span>
                <strong><?= e($depto ?: '—') ?></strong>
            </div>
            <?php if (!empty($estacion)): ?>
            <div class="perfil-info-row">
                <span class="perfil-info-label">Estación</span>
                <strong><?= e($estacion) ?></strong>
            </div>
            <?php endif; ?>
            <?php if (!empty($cedula)): ?>
            <div class="perfil-info-row">
                <span class="perfil-info-label">Cédula</span>
                <strong><?= e($cedula) ?></strong>
            </div>
            <?php endif; ?>
            <?php if (!empty($creado)): ?>
            <div class="perfil-info-row">
                <span class="perfil-info-label">Miembro desde</span>
                <strong><?= e(date('d/m/Y', strtotime($creado))) ?></strong>
            </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- ============================================ -->
    <!-- COLUMNA DERECHA · Formularios                -->
    <!-- ============================================ -->
    <section class="perfil-main">

        <!-- Datos personales -->
        <div class="card">
            <div class="card-body">
                <h3 class="perfil-section-title">
                    <?= icon_tag('user', 'user', 'w-4 h-4') ?>
                    Datos personales
                </h3>

                <form id="formPerfilDatos" novalidate>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nombre <span class="req">*</span></label>
                            <input type="text" name="personal_nombre" id="personal_nombre"
                                   value="<?= e($nombre) ?>" maxlength="45" required>
                            <span class="error" data-error="personal_nombre"></span>
                        </div>

                        <div class="form-group">
                            <label>Apellido <span class="req">*</span></label>
                            <input type="text" name="personal_apellido" id="personal_apellido"
                                   value="<?= e($apellido) ?>" maxlength="45" required>
                            <span class="error" data-error="personal_apellido"></span>
                        </div>

                        <div class="form-group">
                            <label>Correo</label>
                            <input type="email" name="personal_email" id="personal_email"
                                   value="<?= e($email) ?>" maxlength="120">
                            <span class="error" data-error="personal_email"></span>
                        </div>

                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="personal_tlf" id="personal_tlf"
                                   value="<?= e($tlf) ?>" maxlength="20">
                            <span class="error" data-error="personal_tlf"></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" id="btnGuardarDatos" class="btn btn-primary">
                            <?= icon_tag('save', 'save', 'w-4 h-4') ?>
                            <span class="btn-text">Guardar cambios</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cambio de contraseña (versión compacta) -->
        <div class="card" style="margin-top:1rem;">
            <div class="card-body">
                <h3 class="perfil-section-title">
                    <?= icon_tag('key-round', 'key-round', 'w-4 h-4') ?>
                    Cambiar contraseña
                </h3>

                <form id="formPerfilPassword" novalidate>
                    <div class="perfil-pass-grid">
                        <div class="form-group">
                            <label>Actual <span class="req">*</span></label>
                            <input type="password" name="password_actual" id="password_actual"
                                   autocomplete="current-password" placeholder="••••••">
                            <span class="error" data-error="password_actual"></span>
                        </div>

                        <div class="form-group">
                            <label>Nueva <span class="req">*</span></label>
                            <input type="password" name="password_nueva" id="password_nueva"
                                   autocomplete="new-password" placeholder="Mín. 6 caracteres">
                            <span class="error" data-error="password_nueva"></span>
                        </div>

                        <div class="form-group">
                            <label>Confirmar <span class="req">*</span></label>
                            <input type="password" name="password_confirmar" id="password_confirmar"
                                   autocomplete="new-password" placeholder="Repite la nueva">
                            <span class="error" data-error="password_confirmar"></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" id="btnGuardarPassword" class="btn btn-primary">
                            <?= icon_tag('shield-check', 'shield-check', 'w-4 h-4') ?>
                            <span class="btn-text">Actualizar contraseña</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </section>

</div>
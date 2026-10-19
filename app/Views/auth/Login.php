<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · SITGO</title>
    <link rel="icon" href="<?= asset('img/logo.png') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
</head>
<body class="login-body-page">

    <!-- ============================================ -->
    <!-- LOGIN CONTAINER                               -->
    <!-- ============================================ -->
    <div class="login-container">
        <div class="login-header">
            <h1>Bienvenido de nuevo</h1>
            <p>Ingresa a tu cuenta para acceder al sistema</p>
        </div>
        <div class="login-body">

            <?php
                $kicked  = isset($_GET['kicked']);
                $expired = isset($_GET['expired']);
                $logout  = isset($_GET['logout']);
            ?>
            <?php if ($kicked): ?>
                <div class="login-alert login-alert-warn">
                    ⚠️ Tu sesión se cerró porque <strong>se inició sesión desde otro dispositivo</strong>.
                </div>
            <?php elseif ($expired): ?>
                <div class="login-alert login-alert-error">
                    Tu sesión expiró por inactividad. Vuelve a ingresar.
                </div>
            <?php elseif ($logout): ?>
                <div class="login-alert login-alert-success">
                    Sesión cerrada correctamente.
                </div>
            <?php endif; ?>

            <form id="formLogin" name="formLogin" autocomplete="off" novalidate>

                <div class="form-group">
                    <label class="form-label" for="nick">Usuario</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fas fa-user"></i></span>
                        <input type="text"
                               id="nick"
                               name="nick"
                               class="form-input"
                               placeholder="Ingresa tu usuario"
                               required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Contraseña</label>
                    <div class="input-with-icon">
                        <span class="input-icon"><i class="fas fa-lock"></i></span>
                        <input type="password"
                               id="password"
                               name="password"
                               class="form-input"
                               placeholder="Ingresa tu contraseña"
                               required>
                        <button type="button" id="togglePass" class="input-toggle-pass">
                            Ver
                        </button>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember">
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        <span>Recordarme</span>
                    </label>
                    <a href="#" class="forgot-link" id="btnForgot">
                        <i class="fas fa-life-ring"></i>
                        <span>Recuperar contraseña</span>
                    </a>
                </div>

                <div id="loginError" class="login-alert login-alert-error" style="display:none;"></div>

                <button type="submit" class="btn btn-primary" id="btnLogin">
                    <span class="btn-spinner" aria-hidden="true"></span>
                    <span class="btn-text">Iniciar Sesión</span>
                </button>

            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- MODAL RECUPERAR CONTRASEÑA                    -->
    <!-- ============================================ -->
    <div class="modal" id="modalForgot" style="display:none;">
        <div class="modal-content">
            <button class="modal-close" id="closeForgot" type="button">&times;</button>
            <form id="formForgot" novalidate>
                <div class="modal-header">
                    <h2>Recuperar Contraseña</h2>
                    <p>Ingresa tu correo electrónico o cédula para enviar una solicitud de recuperación.</p>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="identificador">Correo Electrónico o Cédula</label>
                        <div class="input-with-icon">
                            <span class="input-icon"><i class="fas fa-id-card"></i></span>
                            <input type="text"
                                   id="identificador"
                                   name="identificador"
                                   class="form-input"
                                   placeholder="correo@ejemplo.com o 12345678"
                                   required>
                        </div>
                    </div>
                    <div id="forgotMsg" class="login-alert" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="cancelForgot">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnForgotSubmit">
                        <span class="btn-spinner" aria-hidden="true"></span>
                        <span class="btn-text">Enviar Solicitud</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        window.SITGO_BASE = "<?= BASE_URL ?>";
    </script>
    <script src="<?= asset('js/login.js') ?>"></script>
</body>
</html>
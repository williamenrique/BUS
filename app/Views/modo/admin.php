<?php
/**
 * admin.php — Panel de control del modo con login real
 * Ubicación: app/Views/modo/admin.php
 *
 * - Solo usuarios del departamento Sistema (DEPARTAMENTO_SISTEMA) pueden entrar.
 * - La sesión de admin vive en $_SESSION['admin_panel'].
 * - Timeout de 8 horas (configurable en ADMIN_PANEL_LIFETIME).
 * - Cada cambio de modo se audita en table_alm_historial_cambio.
 */

session_name('SITGO_SESS');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ruta a la raíz del proyecto (3 niveles arriba: modo/ → Views/ → app/ → raíz)
$ROOT = dirname(__DIR__, 3);

require_once $ROOT . '/app/Config/config.php';
require_once APP_PATH . 'Helpers/helpers.php';
spl_autoload_register(function ($class) {
    $prefix  = 'App\\';
    $baseDir = APP_PATH;
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});

// Tiempo de vida de la sesión de admin (8 horas)
$ADMIN_PANEL_LIFETIME = 8 * 3600;

// ============================================================
// LOGOUT
// ============================================================
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_panel']);
    session_regenerate_id(true);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ============================================================
// VALIDAR SESIÓN EXISTENTE (con timeout)
// ============================================================
$esAdmin = false;
if (!empty($_SESSION['admin_panel']['id'])) {
    $ts = (int)($_SESSION['admin_panel']['ts'] ?? 0);
    if ((time() - $ts) > $ADMIN_PANEL_LIFETIME) {
        unset($_SESSION['admin_panel']);
    } else {
        $esAdmin = true;
    }
}

// ============================================================
// LOGIN (POST)
// ============================================================
$error = '';

if (!$esAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nick'], $_POST['password'])) {
    $nick = trim((string)$_POST['nick']);
    $pass = (string)$_POST['password'];

    if ($nick === '' || $pass === '') {
        $error = 'Ingresa usuario y contraseña.';
    } else {
        try {
            $db = \App\Core\Database::getConnection();
            $stmt = $db->prepare(
                "SELECT usuario_id, usuario_nick, usuario_password, usuario_departamento_id, usuario_status
                 FROM table_usuarios
                 WHERE LOWER(usuario_nick) = LOWER(:n)
                 LIMIT 1"
            );
            $stmt->execute(['n' => $nick]);
            $u = $stmt->fetch(\PDO::FETCH_ASSOC);

            $valido = false;

            if ($u) {
                $esSistema = ((int)$u['usuario_departamento_id'] === (int)DEPARTAMENTO_SISTEMA);
                $activo    = ((int)$u['usuario_status'] === 1);

                if ($esSistema && $activo) {
                    $check = verify_password($pass, (string)$u['usuario_password']);
                    if ($check['ok']) {
                        $valido = true;

                        if ($check['needs_rehash']) {
                            try {
                                $db->prepare("UPDATE table_usuarios SET usuario_password = :h WHERE usuario_id = :id")
                                   ->execute(['h' => hash_password($pass), 'id' => (int)$u['usuario_id']]);
                            } catch (\Throwable $e) {
                                error_log('admin.php rehash: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }

            if ($valido) {
                session_regenerate_id(true);
                $_SESSION['admin_panel'] = [
                    'id'   => (int)$u['usuario_id'],
                    'nick' => $u['usuario_nick'],
                    'ts'   => time(),
                ];
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            $error = 'Credenciales inválidas o sin permisos.';
        } catch (\Throwable $e) {
            error_log('admin.php login: ' . $e->getMessage());
            if (ENVIRONMENT === 'development') {
                $error = 'Error: ' . $e->getMessage() . ' — Línea ' . $e->getLine() . ' en ' . basename($e->getFile());
            } else {
                $error = 'Error del sistema. Intenta más tarde.';
            }
        }
    }
}

// ============================================================
// SI NO ESTÁ LOGUEADO → MOSTRAR FORMULARIO DE LOGIN
// ============================================================
if (!$esAdmin):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · SITGO</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: radial-gradient(circle at 20% 20%, #1e293b 0%, #0f172a 55%, #020617 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
        }
        .login {
            background: #fff;
            border-radius: 16px;
            padding: 2.25rem 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0,0,0,.4);
        }
        .brand {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .brand-name {
            font-size: 1.1rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: .06em;
        }
        .brand-sub {
            font-size: .68rem;
            color: #94a3b8;
            letter-spacing: .15em;
            text-transform: uppercase;
            margin-top: .2rem;
        }
        h1 {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 1.25rem;
            text-align: center;
        }
        label {
            display: block;
            font-size: .72rem;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: .35rem;
        }
        input {
            width: 100%;
            padding: .7rem .85rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: .9rem;
            margin-bottom: .9rem;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }
        button {
            width: 100%;
            padding: .75rem;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: .9rem;
            cursor: pointer;
            transition: background .15s;
        }
        button:hover { background: #1d4ed8; }
        .err {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: .65rem .85rem;
            border-radius: 8px;
            font-size: .82rem;
            margin-bottom: 1rem;
        }
        .foot {
            text-align: center;
            font-size: .72rem;
            color: #94a3b8;
            margin-top: 1.25rem;
        }
    </style>
</head>
<body>
    <form class="login" method="POST" autocomplete="off">
        <div class="brand">
            <div class="brand-name">SITGO</div>
            <div class="brand-sub">Panel de administración</div>
        </div>
        <h1>Iniciar sesión</h1>
        <?php if ($error): ?>
            <div class="err"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <label>Usuario</label>
        <input name="nick" placeholder="Tu usuario del sistema" required autofocus autocomplete="username">
        <label>Contraseña</label>
        <input name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
        <button type="submit">Entrar al panel</button>
        <div class="foot">Solo administradores del departamento Sistema</div>
    </form>
</body>
</html>
<?php
exit;
endif;

// ============================================================
// PANEL (ya logueado)
// ============================================================
$archivoModo = $ROOT . '/modo.php';
$modoConfig  = require $archivoModo;
$modoActual  = $modoConfig['modo'] ?? 'produccion';

$mensaje = '';
$tipoMsg = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modo'])) {
    $nuevo = $_POST['modo'];
    if (in_array($nuevo, ['produccion', 'mantenimiento', 'suspendido'], true)) {
        $contenido = "<?php\n// modo.php — Estado del sistema. NO editar a mano.\nreturn [\n    'modo' => '" . $nuevo . "',\n];\n";

        if (file_put_contents($archivoModo, $contenido, LOCK_EX) !== false) {
            $modoActual = $nuevo;
            $mensaje = 'Modo actualizado a: ' . strtoupper($nuevo);
            $tipoMsg = 'success';

            // Auditoría
            try {
                $db = \App\Core\Database::getConnection();
                $db->prepare(
                    "INSERT INTO table_alm_historial_cambio (obs, info, id_user)
                     VALUES (:obs, :info, :uid)"
                )->execute([
                    'obs'  => 'CAMBIO DE MODO',
                    'info' => 'Modo: ' . $nuevo,
                    'uid'  => (int)$_SESSION['admin_panel']['id'],
                ]);
            } catch (\Throwable $e) {
                error_log('admin.php auditoría: ' . $e->getMessage());
            }
        } else {
            $mensaje = 'Error: no se pudo escribir modo.php. Revisa permisos.';
            $tipoMsg = 'error';
        }
    } else {
        $mensaje = 'Modo inválido.';
        $tipoMsg = 'error';
    }
}

$modos = [
    'produccion'    => ['label' => 'Producción',    'desc' => 'Sistema funcionando normal',            'color' => '#16a34a'],
    'mantenimiento' => ['label' => 'Mantenimiento', 'desc' => 'Muestra maintenance.html a los usuarios', 'color' => '#b45309'],
    'suspendido'    => ['label' => 'Suspendido',    'desc' => 'Muestra ver.html a los usuarios',        'color' => '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de modo · SITGO</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            margin: 0;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2rem;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 4px 20px rgba(15,23,42,.06);
        }
        .top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .top-left h1 {
            margin: 0;
            font-size: 1.15rem;
        }
        .top-left small {
            color: #64748b;
            font-size: .78rem;
        }
        .logout {
            font-size: .78rem;
            color: #dc2626;
            text-decoration: none;
            font-weight: 600;
        }
        .logout:hover { text-decoration: underline; }
        .sub {
            margin: 0 0 1.25rem;
            font-size: .85rem;
            color: #64748b;
        }
        .alerta {
            padding: .75rem 1rem;
            border-radius: 8px;
            font-size: .85rem;
            margin-bottom: 1rem;
        }
        .alerta.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
        .alerta.error   { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }
        .modo-item {
            display: flex;
            align-items: center;
            gap: .85rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: .65rem;
            cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .modo-item:hover { border-color: #cbd5e1; background: #f8fafc; }
        .modo-item.activo { border-color: #2563eb; background: #eff6ff; }
        .modo-item input[type="radio"] { width: 18px; height: 18px; accent-color: #2563eb; }
        .modo-item-label { font-weight: 600; font-size: .9rem; }
        .modo-item-desc { font-size: .78rem; color: #64748b; }
        .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        button {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: .7rem 1.25rem;
            border-radius: 8px;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            margin-top: .75rem;
        }
        button:hover { background: #1d4ed8; }
        .estado-actual {
            display: inline-block;
            padding: .3rem .65rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .links { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: .8rem; }
        .links a { color: #2563eb; text-decoration: none; display: block; margin-bottom: .35rem; }
        .links a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="top">
            <div class="top-left">
                <h1>Panel de modo · SITGO</h1>
                <small>
                    Sesión: <strong><?= htmlspecialchars($_SESSION['admin_panel']['nick']) ?></strong>
                    · <?= date('d/m/Y H:i', (int)$_SESSION['admin_panel']['ts']) ?>
                </small>
            </div>
            <a href="?logout=1" class="logout">Cerrar sesión</a>
        </div>

        <p class="sub">
            Estado actual:
            <span class="estado-actual" style="background:<?= $modos[$modoActual]['color'] ?>22; color:<?= $modos[$modoActual]['color'] ?>;">
                <?= htmlspecialchars($modos[$modoActual]['label']) ?>
            </span>
        </p>

        <?php if ($mensaje): ?>
            <div class="alerta <?= $tipoMsg ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php foreach ($modos as $key => $info): ?>
                <label class="modo-item <?= $key === $modoActual ? 'activo' : '' ?>">
                    <input type="radio" name="modo" value="<?= $key ?>" <?= $key === $modoActual ? 'checked' : '' ?>>
                    <span class="dot" style="background:<?= $info['color'] ?>;"></span>
                    <span>
                        <span class="modo-item-label"><?= htmlspecialchars($info['label']) ?></span><br>
                        <span class="modo-item-desc"><?= htmlspecialchars($info['desc']) ?></span>
                    </span>
                </label>
            <?php endforeach; ?>

            <button type="submit">Aplicar cambio</button>
        </form>

        <div class="links">
            <a href="admin.php">↻ Refrescar panel</a>
            <a href="/" target="_blank">↗ Ver el sitio como usuario</a>
        </div>
    </div>
</body>
</html>
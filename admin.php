<?php
// admin.php — Panel de control del modo (solo para admin)
$esAdmin = isset($_COOKIE['sitgo_admin']) && $_COOKIE['sitgo_admin'] === '1';

if (!$esAdmin) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Acceso denegado</title>';
    echo '<style>body{font-family:system-ui;display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8fafc;margin:0}';
    echo '.box{background:#fff;padding:2rem;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08);max-width:420px;text-align:center}';
    echo 'h1{color:#dc2626;font-size:1.25rem;margin:0 0 .5rem}p{color:#64748b;font-size:.9rem;margin:0}</style></head><body>';
    echo '<div class="box"><h1>Acceso denegado</h1>';
    echo '<p>Necesitas la cookie de administrador para acceder a este panel.</p></div></body></html>';
    exit;
}

$archivoModo = __DIR__ . '/modo.php';
$modoConfig  = require $archivoModo;
$modoActual  = $modoConfig['modo'] ?? 'produccion';

// Procesar cambio de modo
$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modo'])) {
    $nuevo = $_POST['modo'];
    if (in_array($nuevo, ['produccion', 'mantenimiento', 'suspendido'], true)) {
        $contenido = "<?php\n// modo.php — Estado del sistema. NO editar a mano.\nreturn [\n    'modo' => '" . $nuevo . "',\n];\n";
        if (file_put_contents($archivoModo, $contenido, LOCK_EX) !== false) {
            $modoActual = $nuevo;
            $mensaje = 'Modo actualizado a: ' . strtoupper($nuevo);
        } else {
            $mensaje = 'Error: no se pudo escribir modo.php. Revisa permisos.';
        }
    }
}

$modos = [
    'produccion'    => ['label' => 'Producción',    'desc' => 'Sistema funcionando normal',          'color' => '#16a34a'],
    'mantenimiento' => ['label' => 'Mantenimiento', 'desc' => 'Muestra maintenance.html a los usuarios', 'color' => '#b45309'],
    'suspendido'    => ['label' => 'Suspendido',    'desc' => 'Muestra ver.html a los usuarios',     'color' => '#dc2626'],
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
        h1 { margin: 0 0 .25rem; font-size: 1.25rem; }
        .sub { margin: 0 0 1.5rem; font-size: .85rem; color: #64748b; }
        .alerta {
            background: #dcfce7; border: 1px solid #bbf7d0; color: #166534;
            padding: .75rem 1rem; border-radius: 8px; font-size: .85rem;
            margin-bottom: 1rem;
        }
        .modo-item {
            display: flex; align-items: center; gap: .85rem;
            padding: 1rem; border: 1px solid #e2e8f0; border-radius: 10px;
            margin-bottom: .65rem; cursor: pointer;
            transition: border-color .15s, background .15s;
        }
        .modo-item:hover { border-color: #cbd5e1; background: #f8fafc; }
        .modo-item.activo { border-color: #2563eb; background: #eff6ff; }
        .modo-item input[type="radio"] { width: 18px; height: 18px; accent-color: #2563eb; }
        .modo-item-label { font-weight: 600; font-size: .9rem; }
        .modo-item-desc { font-size: .78rem; color: #64748b; }
        .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
        button {
            background: #2563eb; color: #fff; border: none;
            padding: .7rem 1.25rem; border-radius: 8px;
            font-size: .9rem; font-weight: 600; cursor: pointer;
            width: 100%; margin-top: .75rem;
        }
        button:hover { background: #1d4ed8; }
        .estado-actual {
            display: inline-block; padding: .3rem .65rem; border-radius: 999px;
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em;
        }
        .links { margin-top: 1.25rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; font-size: .8rem; }
        .links a { color: #2563eb; text-decoration: none; display: block; margin-bottom: .35rem; }
        .links a:hover { text-decoration: underline; }
        .links code { background: #f1f5f9; padding: .1rem .35rem; border-radius: 4px; font-size: .75rem; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Panel de modo · SITGO</h1>
        <p class="sub">
            Estado actual:
            <span class="estado-actual" style="background:<?= $modos[$modoActual]['color'] ?>22; color:<?= $modos[$modoActual]['color'] ?>;">
                <?= htmlspecialchars($modos[$modoActual]['label']) ?>
            </span>
        </p>

        <?php if ($mensaje): ?>
            <div class="alerta"><?= htmlspecialchars($mensaje) ?></div>
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
            <a href="/admin.php">↻ Refrescar panel</a>
            <a href="/" target="_blank">↗ Ver el sitio como usuario</a>
        </div>
    </div>
</body>
</html>
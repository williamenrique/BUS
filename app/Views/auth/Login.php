<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingresar · SITGO</title>
    <link rel="icon" href="<?= asset('img/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <div class="text-center mb-6">
        <img src="<?= asset('img/logo.png') ?>" alt="SITGO" class="h-16 w-16 mx-auto mb-2">
        <h1 class="text-3xl font-bold text-white tracking-wide">SITGO</h1>
        <p class="text-blue-100 text-sm mt-1">Sistema Integral de Transporte</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-slate-800 mb-1">Bienvenido</h2>
        <p class="text-sm text-slate-500 mb-6">Ingresa tus credenciales para continuar.</p>
        <?php
            $kicked  = isset($_GET['kicked']);
            $expired = isset($_GET['expired']);
            $logout  = isset($_GET['logout']);
            ?>
            <?php if ($kicked): ?>
                <div class="mb-4 text-sm rounded-lg p-3 bg-amber-50 border border-amber-200 text-amber-800">
                    ⚠️ Tu sesión se cerró porque <strong>se inició sesión desde otro dispositivo</strong>.
                </div>
            <?php elseif ($expired): ?>
                <div class="mb-4 text-sm rounded-lg p-3 bg-red-50 border border-red-200 text-red-700">
                    Tu sesión expiró por inactividad. Vuelve a ingresar.
                </div>
            <?php elseif ($logout): ?>
                <div class="mb-4 text-sm rounded-lg p-3 bg-emerald-50 border border-emerald-200 text-emerald-700">
                    Sesión cerrada correctamente.
                </div>
        <?php endif; ?>
        <form id="formLogin" autocomplete="off" novalidate>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Usuario</label>
                <input type="text" name="nick" id="nick" required
                       class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                       placeholder="usuario">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                <div class="relative">
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                           placeholder="••••••••">
                    <button type="button" id="togglePass"
                            class="absolute right-2 top-1.5 text-xs text-slate-500 hover:text-slate-700">
                        Ver
                    </button>
                </div>
            </div>

            <div id="loginError" class="hidden mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-2"></div>

            <button type="submit" id="btnLogin"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition disabled:opacity-60">
                Iniciar sesión
            </button>

            <div class="mt-4 text-center">
                <button type="button" id="btnForgot"
                        class="text-sm text-blue-600 hover:underline">
                    ¿Olvidaste tu contraseña?
                </button>
            </div>
        </form>
    </div>

    <p class="text-center text-xs text-blue-100 mt-4">
        © <?= date('Y') ?> SITGO · Todos los derechos reservados
    </p>
</div>

<!-- ============ MODAL RECUPERAR ============ -->
<div id="modalForgot"
     class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 animate-fadeIn">
        <div class="flex items-start justify-between mb-3">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Recuperar contraseña</h3>
                <p class="text-xs text-slate-500">Se registrará tu solicitud para revisión del administrador.</p>
            </div>
            <button id="closeForgot" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
        </div>

        <form id="formForgot" novalidate>
            <label class="block text-sm font-medium text-slate-700 mb-1">Usuario o correo electrónico</label>
            <input type="text" name="identificador" id="identificador" required
                   class="w-full px-3 py-2 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none mb-3"
                   placeholder="usuario o correo@dominio.com">

            <div id="forgotMsg" class="hidden text-sm rounded-lg p-2 mb-3"></div>

            <div class="flex justify-end gap-2">
                <button type="button" id="cancelForgot"
                        class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">
                    Cancelar
                </button>
                <button type="submit" id="btnForgotSubmit"
                        class="px-4 py-2 rounded-lg text-sm bg-blue-600 hover:bg-blue-700 text-white font-semibold disabled:opacity-60">
                    Enviar solicitud
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
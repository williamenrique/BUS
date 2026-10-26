<?php $user = auth_user(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate">
    <title>Seleccionar estación · SITGO</title>
    <link rel="icon" href="<?= asset('img/logo.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/tailwind.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/styles.css') ?>">
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg">
    <div class="text-center mb-6">
        <img src="<?= asset('img/logo.png') ?>" alt="SITGO" class="h-16 w-16 mx-auto mb-2">
        <h1 class="text-3xl font-bold text-white tracking-wide">SITGO</h1>
        <p class="text-blue-100 text-sm mt-1">Estación de Servicio</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <h2 class="text-xl font-semibold text-slate-800 mb-1">
            ¡Hola, <?= e($user['nombre'] ?: $user['nick']) ?>!
        </h2>
        <p class="text-sm text-slate-500 mb-6">
            Antes de comenzar a vender, selecciona la estación donde trabajarás hoy.
        </p>

        <form id="formEstacion" novalidate>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Estación de servicio</label>
                <select name="id_estacion" id="id_estacion" required
                        class="w-full px-3 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-blue-500 outline-none">
                    <option value="">Selecciona una estación...</option>
                    <?php foreach ($estaciones as $e): ?>
                        <option value="<?= (int)$e['id_estacion'] ?>"><?= e($e['estacion']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="errBox" class="hidden mb-4 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg p-2"></div>

            <button type="submit" id="btnContinuar"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-lg transition disabled:opacity-60">
                Continuar a la estación
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-blue-100 mt-4">
        Esta selección se mantendrá hasta cerrar sesión.
    </p>
</div>

<script>
    window.SITGO_BASE = "<?= BASE_URL ?>";
</script>
<script src="<?= asset('js/estacion-seleccionar.js') ?>"></script>
</body>
</html>
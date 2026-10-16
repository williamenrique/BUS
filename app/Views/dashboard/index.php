<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    $cards = [
        ['label' => 'Usuarios activos', 'value' => $totales['usuarios'],   'color' => 'from-blue-500 to-blue-600',   'icon' => '👥'],
        ['label' => 'Unidades flota',  'value' => $totales['flota'],      'color' => 'from-emerald-500 to-emerald-600','icon' => '🚌'],
        ['label' => 'Personal',        'value' => $totales['personal'],   'color' => 'from-amber-500 to-amber-600', 'icon' => '🧑‍💼'],
        ['label' => 'Estaciones',      'value' => $totales['estaciones'], 'color' => 'from-purple-500 to-purple-600','icon' => '⛽'],
    ];
    foreach ($cards as $c): ?>
        <div class="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition">
            <div class="w-12 h-12 rounded-xl bg-gradient-to-br <?= $c['color'] ?> text-white flex items-center justify-center text-xl">
                <?= $c['icon'] ?>
            </div>
            <div>
                <p class="text-sm text-slate-500"><?= e($c['label']) ?></p>
                <p class="text-2xl font-bold text-slate-800"><?= (int)$c['value'] ?></p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-800 mb-1">
        ¡Hola, <?= e($user['nombre'] ?: $user['nick']) ?>! 👋
    </h2>
    <p class="text-slate-500 text-sm">
        Bienvenido a <strong>SITGO</strong>. Estás en el <strong><?= e($user['departamento_nombre']) ?></strong>
        con el rol <strong><?= e($user['rol_nombre']) ?></strong>.
    </p>

    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div class="bg-slate-50 rounded-xl p-4">
            <p class="text-slate-500">Usuario</p>
            <p class="font-semibold text-slate-800"><?= e($user['nick']) ?></p>
        </div>
        <div class="bg-slate-50 rounded-xl p-4">
            <p class="text-slate-500">Correo</p>
            <p class="font-semibold text-slate-800"><?= e($user['email'] ?: '—') ?></p>
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6">
    <h3 class="font-semibold text-slate-800 mb-4">Estado del sistema</h3>
    <p class="text-slate-500 text-sm">
        Módulos activos actualmente: <strong>Autenticación, Dashboard</strong>.
        Próximamente: Inventario, Flota, Mantenimiento, Despacho, Compras y Reportes.
    </p>
</div>
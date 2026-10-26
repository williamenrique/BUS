<!-- Page header -->
<div class="page-header">
    <div>
        <h2 class="page-title">Recuperación de contraseñas</h2>
        <p class="page-subtitle">Solicitudes pendientes de atención</p>
    </div>
    <button id="btnRefrescar" class="btn btn-ghost">
        <?= icon_tag('refresh-cw', 'refresh-cw', 'w-4 h-4') ?>
        <span>Refrescar</span>
    </button>
</div>

<!-- Estado vacío -->
<div id="estadoVacio" class="card" style="display:none;">
    <div style="text-align:center; padding:4rem 2rem;">
        <?= icon_tag('check-circle', 'check-circle', 'w-16 h-16 mx-auto text-emerald-400') ?>
        <h3 style="margin:1rem 0 .35rem; color:#0f172a; font-size:1.1rem; font-weight:600;">
            Todo al día
        </h3>
        <p style="color:#64748b; font-size:.9rem;">
            No hay solicitudes de recuperación pendientes.
        </p>
    </div>
</div>

<!-- Grid de tarjetas -->
<div id="gridSolicitudes" class="recuperar-grid"></div>
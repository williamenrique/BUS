document.addEventListener('DOMContentLoaded', () => {
    const API = (window.SITGO_BASE || '/') + 'backup';
    const $ = id => document.getElementById(id);

    let dt = null;

    /* ============================================
       RENDER ROW
       ============================================ */
    function renderRow(r){
        const estadoBadge = {
            'ok':          'badge-success',
            'error':       'badge-danger',
            'en_progreso': 'badge-info',
        }[r.estado] || 'badge-info';

        const tipoBadge = {
            'manual':     'badge-info',
            'automatico': 'badge-info',
            'seguridad':  'badge-danger',
            'subido':     'badge-info',
        }[r.tipo] || 'badge-info';

        const accionBadge = r.accion === 'restore' ? 'badge-danger' : 'badge-success';

        const tam = fmtBytes(Number(r.tamanio_bytes || 0));
        const dur = r.duracion_seg != null ? Number(r.duracion_seg).toFixed(2) + 's' : '—';
        const nick = r.usuario_nick || '—';
        const fecha = (r.fecha_creacion || '').replace('T', ' ');

        return `
            <tr>
                <td><code style="font-size:.72rem;">${esc(r.archivo)}</code></td>
                <td>${esc(tam)}</td>
                <td><span class="badge ${tipoBadge}">${esc(r.tipo)}</span></td>
                <td><span class="badge ${accionBadge}">${esc(r.accion)}</span></td>
                <td><span class="badge ${estadoBadge}">${esc(r.estado)}</span></td>
                <td>${esc(nick)}</td>
                <td>${esc(fecha)}</td>
                <td>${esc(dur)}</td>
                <td class="text-right">
                    <div class="actions">
                        ${r.accion === 'backup'
                            ? `<button class="action-btn download" data-action="descargar" data-archivo="${esc(r.archivo)}" title="Descargar">
                                   <i data-lucide="download" class="w-4 h-4"></i>
                               </button>`
                            : ''}
                        ${r.accion === 'backup'
                            ? `<button class="action-btn toggle" data-action="restaurar" data-archivo="${esc(r.archivo)}" title="Restaurar">
                                   <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                               </button>`
                            : ''}
                        <button class="action-btn delete" data-action="eliminar-reg" data-id="${r.id_backup}" data-archivo="${esc(r.archivo)}" title="Eliminar">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </td>
            </tr>`;
    }

    /* ============================================
       INIT
       ============================================ */
    (async () => {
        try {
            dt = new DataTable({
                tbodyId: 'tbodyBackups',
                endpoint: API + '/listar',
                columns: ['Archivo','Tamaño','Tipo','Acción','Estado','Usuario','Fecha','Duración','Acciones'],
                renderRow: renderRow,
                searchInputId: 'filtroBuscar',
                limitSelectorId: 'filtroLimit',
                paginationId: 'paginationBackups',
                totalId: 'totalBackups',
                startId: 'startBackups',
                endId: 'endBackups',
                noDataMessage: 'No hay registros de backup',
                getExtraParams: () => ({
                    tipo:   $('filtroTipo')?.value   || '',
                    accion: $('filtroAccion')?.value || '',
                    estado: $('filtroEstado')?.value || '',
                }),
                onDataLoaded: () => bindTableActions()
            });

            bindEvents();
            bindSubir();
        } catch (e){
            console.error(e);
            toast('Error al inicializar: ' + e.message, 'error');
        }
    })();

    /* ============================================
       EVENTOS GENERALES
       ============================================ */
    function bindEvents(){
        $('btnCrearBackup')?.addEventListener('click', crearBackup);
        $('toggleSubir')?.addEventListener('click', toggleSeccion);
        $('btnToggleSubir')?.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleSeccion();
        });
        $('btnCancelarSubir')?.addEventListener('click', () => {
            $('formSubir').reset();
            cerrarSeccion();
        });

        $('filtroTipo')?.addEventListener('change',   () => dt?.changePage(1) || dt?.reload());
        $('filtroAccion')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
        $('filtroEstado')?.addEventListener('change', () => dt?.changePage(1) || dt?.reload());
    }

    function bindTableActions(){
        const tbody = $('tbodyBackups');
        if (!tbody || tbody._bound) return;
        tbody._bound = true;

        tbody.addEventListener('click', e => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const { action, archivo, id } = btn.dataset;
            if (action === 'descargar')    descargar(archivo);
            if (action === 'restaurar')    restaurar(archivo);
            if (action === 'eliminar-reg') eliminarRegistro(id, archivo);
        });
    }

    /* ============================================
       SECCIÓN COLAPSABLE
       ============================================ */
    function toggleSeccion(){
        const s = $('seccionSubir');
        s.classList.toggle('collapsed');
        const t = $('btnToggleSubir');
        if (t) t.style.transform = s.classList.contains('collapsed') ? 'rotate(0deg)' : 'rotate(180deg)';
    }
    function abrirSeccion(){
        $('seccionSubir').classList.remove('collapsed');
        const t = $('btnToggleSubir');
        if (t) t.style.transform = 'rotate(180deg)';
    }
    function cerrarSeccion(){
        $('seccionSubir').classList.add('collapsed');
        const t = $('btnToggleSubir');
        if (t) t.style.transform = 'rotate(0deg)';
    }

    /* ============================================
       CREAR
       ============================================ */
    async function crearBackup(){
        const ok = await sitgoConfirm({
            title: 'Crear backup ahora',
            message: 'Se generará un archivo .sql con la base de datos actual. ¿Continuar?',
            okText: 'Sí, crear backup',
            cancelText: 'Cancelar',
            type: 'primary',
            icon: 'database',
        });
        if (!ok) return;

        const btn = $('btnCrearBackup');
        const txt = $('btnCrearBackupText');
        const orig = txt.textContent;
        btn.disabled = true;
        txt.textContent = 'Creando...';

        try {
            const r = await fetchJSON(API + '/crear', { method: 'POST' });
            if (!r.ok) throw new Error(r.message || 'Error al crear backup');
            toast(r.message || 'Backup creado correctamente.', 'success');
            dt?.refresh();
            refrescarStats();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        } finally {
            btn.disabled = false;
            txt.textContent = orig;
        }
    }

    /* ============================================
       DESCARGAR
       ============================================ */
    function descargar(archivo){
        window.location.href = API + '/descargar/' + encodeURIComponent(archivo);
    }

    /* ============================================
       RESTAURAR
       ============================================ */
    async function restaurar(archivo){
        const ok = await sitgoConfirm({
            title: 'Restaurar backup',
            message: `Vas a restaurar "${archivo}".\n\nSe creará un backup de seguridad automático antes de proceder. ¿Continuar?`,
            okText: 'Sí, restaurar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'rotate-ccw',
        });
        if (!ok) return;

        const confirmTxt = await sitgoPrompt({
            title: 'Confirmar restauración',
            message: `Escribe el nombre exacto del archivo para confirmar: "${archivo}"`,
            placeholder: archivo,
            okText: 'Restaurar ahora',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'alert-triangle',
            inputType: 'text',
        });
        if (confirmTxt !== archivo){
            if (confirmTxt !== null) toast('El nombre no coincide. Restauración cancelada.', 'error');
            return;
        }

        try {
            toast('Restaurando... no cierres esta pestaña.', 'info');
            const r = await fetchJSON(API + '/restaurar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ archivo })
            });
            if (!r.ok) throw new Error(r.message || 'Error al restaurar');
            toast(r.message || 'Restauración completada.', 'success');
            dt?.refresh();
            refrescarStats();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================
       ELIMINAR — ahora por ID
       ============================================ */
    async function eliminarRegistro(id, archivo){
        if (!id) {
            toast('ID inválido.', 'error');
            return;
        }

        const ok = await sitgoConfirm({
            title: 'Eliminar backup',
            message: `Se eliminará el registro de "${archivo}" y el archivo .sql del disco (si existe).\n\n¿Continuar?`,
            okText: 'Sí, eliminar',
            cancelText: 'Cancelar',
            type: 'danger',
            icon: 'trash-2',
        });
        if (!ok) return;

        try {
            const r = await fetchJSON(API + '/eliminar/' + encodeURIComponent(id), { method: 'POST' });
            if (!r.ok) throw new Error(r.message || 'No se pudo eliminar.');
            toast(r.message || 'Eliminado.', 'success');
            dt?.refresh();
            refrescarStats();
        } catch (e){
            toast('Error: ' + e.message, 'error');
        }
    }

    /* ============================================
       SUBIR
       ============================================ */
    function bindSubir(){
        $('formSubir')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const file = $('archivoSql')?.files?.[0];
            if (!file){
                toast('Selecciona un archivo .sql', 'error');
                return;
            }
            if (!/\.sql$/i.test(file.name)){
                toast('Solo se permiten archivos .sql', 'error');
                return;
            }

            const btn = $('btnGuardarSubir');
            btn.disabled = true;
            const txt = btn.querySelector('.btn-text');
            const orig = txt.textContent;
            txt.textContent = 'Subiendo...';

            try {
                const fd = new FormData();
                fd.append('archivo', file);
                const r = await fetch(API + '/subir', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).then(x => x.json());

                if (!r.ok) throw new Error(r.message || 'Error al subir');
                toast(r.message || 'Archivo subido.', 'success');
                $('formSubir').reset();
                cerrarSeccion();
                dt?.refresh();
                refrescarStats();
            } catch (err){
                toast('Error: ' + err.message, 'error');
            } finally {
                btn.disabled = false;
                txt.textContent = orig;
            }
        });
    }

    /* ============================================
       REFRESCAR TARJETAS
       ============================================ */
    async function refrescarStats(){
        try {
            const r = await fetchJSON(API + '/stats');
            if (!r.ok) return;
            const d = r.data;
            $('statArchivos').textContent = d.archivos;
            $('statTamano').textContent   = d.tamano_fmt;
            $('statRestores').textContent = d.bd.restores_ok;
            $('statErrores').textContent  = d.bd.errores;
        } catch (e){ /* silencio */ }
    }

    /* ============================================
       HELPERS
       ============================================ */
    function fetchJSON(url, opts = {}){
        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            ...opts
        }).then(r => r.json());
    }

    function esc(s){
        if (s == null) return '';
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function fmtBytes(b){
        if (b < 1024) return b + ' B';
        if (b < 1048576) return (b/1024).toFixed(1) + ' KB';
        if (b < 1073741824) return (b/1048576).toFixed(1) + ' MB';
        return (b/1073741824).toFixed(2) + ' GB';
    }

    function toast(msg, type = 'success'){
        let c = document.querySelector('.toast-container');
        if (!c){
            c = document.createElement('div');
            c.className = 'toast-container';
            document.body.appendChild(c);
        }
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 4000);
    }
});
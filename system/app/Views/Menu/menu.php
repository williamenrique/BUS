<?= head($data)?>
<style type="text/css">
:root {
    --primary: #6366f1;
    --secondary: #4f46e5;
    --success: #10b981;
    --info: #0ea5e9;
    --warning: #f59e0b;
    --danger: #ef4444;
    --dark-bg: #111827;
    --dark-secondary: #1a202c;
    --dark-tertiary: #2d3748;
    --dark-text: #f3f4f6;
    --dark-border: #374151;
    --light-bg: #f9fafb;
    --light-secondary: #ffffff;
    --light-text: #111827;
    --light-border: #e5e7eb;
}

* {
    box-sizing: border-box;
}

.card {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.card-header {
    padding: 15px 20px;
    background-color: var(--dark-bg);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h2 {
    margin: 0;
    font-size: 1.15rem;
    color: white;
}

.card-body {
    padding: 20px;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

select,
input[type="text"],
input[type="number"],
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid var(--light-border);
    border-radius: 4px;
    font-size: 15px;
}

.btn {
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.3s;
}

.btn-primary { background-color: var(--primary); color: white; }
.btn-primary:hover { background-color: var(--secondary); }
.btn-success { background-color: var(--success); color: white; }
.btn-success:hover { background-color: #0d9669; }
.btn-danger { background-color: var(--danger); color: white; }
.btn-danger:hover { background-color: #dc2626; }
.btn-secondary { background-color: #6b7280; color: white; }
.btn-secondary:hover { background-color: #4b5563; }
.btn-sm { padding: 5px 10px; font-size: 12px; }

.btn-icon {
    padding: 0.3rem;
    border: none;
    border-radius: 4px;
    background: transparent;
    cursor: pointer;
    font-size: 0.9rem;
    color: #555;
}

.btn-icon:hover { background: #f3f4f6; color: var(--primary); }

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-item input[type="checkbox"] { width: auto; }

.alert {
    padding: 15px;
    margin-bottom: 20px;
    border: 1px solid transparent;
    border-radius: 4px;
}

.alert-success { color: #155724; background-color: #d4edda; border-color: #c3e6cb; }
.alert-error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
.hidden { display: none !important; }

.menus-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.menu-card {
    background: white;
    border: 1px solid var(--light-border);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    height: fit-content;
}

.menu-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.menu-card-header {
    padding: 0.75rem 1rem;
    background: var(--light-bg);
    border-bottom: 1px solid var(--light-border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.menu-card-header i {
    font-size: 1rem;
    color: var(--primary);
    width: 16px;
}

.menu-card-header h4 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--light-text);
    flex-grow: 1;
}

.menu-badge {
    background: var(--primary);
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
}

.menu-badge.badge-scope { background: var(--info); }

.menu-card-body { padding: 1rem; }

.menu-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.info-item .label {
    font-size: 0.75rem;
    color: var(--light-text);
    opacity: 0.7;
}

.info-item .value {
    font-size: 0.85rem;
    color: var(--light-text);
    font-weight: 500;
}

.rutas-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--light-border);
}

.rutas-section h5 {
    margin: 0 0 0.5rem 0;
    font-size: 0.85rem;
    color: var(--light-text);
    font-weight: 600;
}

.rutas-list {
    display: flex;
    flex-direction: column;
    gap: 0.3rem;
}

.ruta-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.4rem 0.6rem;
    background: #f8f9fa;
    border-radius: 4px;
    border: 1px solid var(--light-border);
    font-size: 0.8rem;
}

.ruta-item .patron {
    font-family: 'Courier New', monospace;
    color: var(--primary);
    font-weight: 500;
}

.hijos-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--light-border);
}

.hijos-section h5 {
    margin: 0 0 0.5rem 0;
    font-size: 0.85rem;
    color: var(--light-text);
    font-weight: 600;
}

.hijo-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: var(--light-bg);
    border-radius: 4px;
    border: 1px solid var(--light-border);
    margin-bottom: 0.3rem;
}

.hijo-item .hijo-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-grow: 1;
}

.hijo-item .hijo-info i {
    font-size: 0.8rem;
    color: var(--primary);
}

.hijo-item .hijo-info span {
    font-size: 0.85rem;
    color: var(--light-text);
    font-weight: 500;
}

.menu-card-actions {
    padding: 0.75rem 1rem;
    background: var(--light-bg);
    border-top: 1px solid var(--light-border);
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 100%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--light-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.15rem;
    color: var(--light-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.modal-body { padding: 1.5rem; }

.close {
    font-size: 1.5rem;
    cursor: pointer;
    color: #999;
    line-height: 1;
}

.close:hover { color: var(--danger); }

.rutas-editor {
    border: 1px solid var(--light-border);
    border-radius: 6px;
    padding: 0.75rem;
    background: #fafafa;
}

.rutas-editor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.rutas-editor-header label {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.ruta-input-row {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 0.4rem;
    align-items: center;
}

.ruta-input-row input {
    flex-grow: 1;
    padding: 0.4rem 0.6rem;
    font-size: 0.85rem;
    font-family: 'Courier New', monospace;
}

.ruta-input-row .btn-remove-ruta {
    padding: 0.4rem 0.6rem;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-add-ruta {
    padding: 0.35rem 0.7rem;
    background: var(--info);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
}

.btn-add-ruta:hover { background: #0284c7; }

.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.3rem;
    font-style: italic;
}

.permission-tree {
    background: #fafafa;
    border: 1px solid var(--light-border);
    border-radius: 6px;
    padding: 1rem;
    max-height: 500px;
    overflow-y: auto;
}

.permission-node { margin-bottom: 0.4rem; }

.permission-node .node-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.35rem 0.5rem;
    border-radius: 4px;
}

.permission-node .node-header:hover { background: #f3f4f6; }

.permission-node .node-children {
    margin-left: 1.75rem;
    border-left: 2px solid #e5e7eb;
    padding-left: 0.75rem;
    margin-top: 0.3rem;
}

.permission-node input[type="checkbox"] { width: auto; cursor: pointer; }

.permission-node label {
    cursor: pointer;
    margin: 0;
    font-size: 0.9rem;
    flex-grow: 1;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.permission-node label .fa-icon { color: var(--primary); width: 16px; }

.permission-node label .scope-badge {
    font-size: 0.65rem;
    padding: 0.1rem 0.4rem;
    background: var(--info);
    color: white;
    border-radius: 8px;
    font-weight: 500;
}

.toggle-hijos {
    background: transparent;
    border: none;
    cursor: pointer;
    color: #6b7280;
    padding: 0.2rem;
    width: 20px;
    text-align: center;
}

.toggle-hijos:hover { color: var(--primary); }

@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
    .menus-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .menu-info { grid-template-columns: 1fr; }
}

/* ===== SELECTOR DE ICONOS ===== */
.icon-picker { position: relative; }

.icon-picker-trigger {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--light-border);
    border-radius: 4px;
    background: white;
    cursor: pointer;
    transition: border-color 0.2s;
}

.icon-picker-trigger:hover { border-color: var(--primary); }

.icon-picker-trigger .icon-preview {
    font-size: 1.2rem;
    color: var(--primary);
    width: 24px;
    text-align: center;
}

.icon-picker-trigger .icon-value {
    flex-grow: 1;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    color: #555;
}

.icon-picker-trigger .icon-toggle { color: #999; }

.icon-picker-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 1100;
    background: white;
    border: 1px solid var(--light-border);
    border-radius: 6px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    max-height: 420px;
    display: flex;
    flex-direction: column;
    margin-top: 4px;
}

.icon-picker-dropdown.hidden { display: none; }

.icon-picker-search {
    padding: 0.5rem;
    border-bottom: 1px solid var(--light-border);
}

.icon-picker-search input {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--light-border);
    border-radius: 4px;
    font-size: 0.9rem;
}

.icon-picker-tabs {
    display: flex;
    overflow-x: auto;
    border-bottom: 1px solid var(--light-border);
    background: #fafafa;
}

.icon-picker-tab {
    padding: 0.5rem 0.85rem;
    cursor: pointer;
    white-space: nowrap;
    font-size: 0.8rem;
    color: #555;
    border-bottom: 2px solid transparent;
}

.icon-picker-tab:hover { background: #f3f4f6; }

.icon-picker-tab.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    font-weight: 600;
    background: white;
}

.icon-picker-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(48px, 1fr));
    gap: 0.25rem;
    padding: 0.75rem;
    overflow-y: auto;
    max-height: 280px;
}

.icon-picker-item {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 1.1rem;
    color: #4b5563;
    transition: all 0.15s;
    border: 2px solid transparent;
}

.icon-picker-item:hover {
    background: #eef2ff;
    color: var(--primary);
    transform: scale(1.1);
}

.icon-picker-item.selected {
    background: var(--primary);
    color: white;
    border-color: var(--secondary);
}

.icon-picker-footer {
    padding: 0.5rem 0.75rem;
    border-top: 1px solid var(--light-border);
    background: #fafafa;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    color: #666;
}

.icon-picker-empty {
    padding: 2rem;
    text-align: center;
    color: #999;
    font-size: 0.85rem;
}
</style>

<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>GESTIÓN DE MENÚS Y PERMISOS</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL()?>">Home</a></li>
                        <li class="breadcrumb-item active">Menú</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">

                    <!-- CARD: ASIGNACIÓN DE PERMISOS -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-user-cog"></i> Asignación de Permisos</h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="select-user">Seleccionar Usuario:</label>
                                <select id="select-user" class="theme-select">
                                    <option value="">-- Seleccione un usuario --</option>
                                </select>
                            </div>

                            <div id="user-permissions" class="hidden">
                                <h3 style="font-size:1rem;margin-bottom:1rem;">
                                    Permisos de: <span id="selected-username">—</span>
                                </h3>

                                <div id="permissions-container" class="permission-tree">
                                    <!-- El árbol de permisos se carga aquí -->
                                </div>

                                <div style="margin-top: 20px;">
                                    <button class="btn btn-success" id="btn-save-permissions">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                    <button class="btn btn-secondary" id="btn-reset-permissions">
                                        <i class="fas fa-undo"></i> Restablecer
                                    </button>
                                    <button class="btn btn-primary" id="btn-expand-all">
                                        <i class="fas fa-expand"></i> Expandir Todo
                                    </button>
                                    <button class="btn btn-primary" id="btn-collapse-all">
                                        <i class="fas fa-compress"></i> Colapsar Todo
                                    </button>
                                </div>
                            </div>

                            <div id="loading" class="hidden" style="text-align:center;padding:1rem;">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </div>

                            <div id="message" class="alert hidden"></div>
                        </div>
                    </div>

                    <!-- CARD: GESTIÓN DE MENÚS -->
                    <div class="card">
                        <div class="card-header">
                            <h2><i class="fas fa-bars"></i> Gestión de Menús</h2>
                            <button class="btn btn-primary btn-sm" id="btn-crear-menu">
                                <i class="fas fa-plus"></i> Nuevo Menú
                            </button>
                        </div>

                        <div class="card-body">
                            <div id="menus-list-container" class="menus-container">
                                <!-- Los menús se cargan aquí -->
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<!-- MODAL: CREAR/EDITAR MENÚ -->
<div id="menu-modal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-bars"></i> <span id="menu-modal-title">Nuevo Menú</span></h3>
            <span class="close" id="close-menu-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="form-menu">
                <input type="hidden" id="menu_id">

                <div class="form-row">
                    <div class="form-group">
                        <label for="menu_nombre">Nombre *</label>
                        <input type="text" id="menu_nombre" required>
                    </div>
                    <div class="form-group">
                        <label for="menu_icono">Icono</label>
                        <div class="icon-picker" id="icon-picker">
                            <div class="icon-picker-trigger" id="icon-picker-trigger">
                                <i class="icon-preview fas fa-home" id="icon-preview"></i>
                                <span class="icon-value" id="icon-value">fas fa-home</span>
                                <i class="icon-toggle fas fa-chevron-down"></i>
                            </div>
                            <input type="hidden" id="menu_icono" value="fas fa-home">
                            <div class="icon-picker-dropdown hidden" id="icon-picker-dropdown">
                                <div class="icon-picker-search">
                                    <input type="text" id="icon-search" placeholder="🔍 Buscar icono...">
                                </div>
                                <div class="icon-picker-tabs" id="icon-picker-tabs"></div>
                                <div class="icon-picker-grid" id="icon-picker-grid"></div>
                                <div class="icon-picker-footer">
                                    <span id="icon-picker-count">0 iconos</span>
                                    <button type="button" class="btn btn-secondary btn-sm" id="icon-picker-clear">
                                        <i class="fas fa-times"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="menu_padre_id">Menú Padre (opcional)</label>
                        <select id="menu_padre_id">
                            <option value="">— Ninguno (es raíz) —</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="menu_scope">Contexto (scope)</label>
                        <input type="text" id="menu_scope" placeholder="general" value="general">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="menu_orden">Orden</label>
                        <input type="number" id="menu_orden" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label for="menu_ruta">Ruta (URL)</label>
                        <input type="text" id="menu_ruta" placeholder="flota/taller" class="input-mono">
                        <div class="help-text">Opcional si va a tener hijos.</div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="rutas-editor">
                        <div class="rutas-editor-header">
                            <label>Patrones de ruta que activan este menú</label>
                            <button type="button" class="btn-add-ruta" id="btn-add-ruta">
                                <i class="fas fa-plus"></i> Añadir
                            </button>
                        </div>
                        <div id="rutas-container">
                            <!-- inputs dinámicos -->
                        </div>
                        <div class="help-text">
                            Ejemplos: <code>flota</code>, <code>flota/taller</code>, <code>flota/taller/*</code>
                            (comodín). El menú se activará cuando la URL coincida con alguno de estos patrones.
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="btn-cancelar-menu">
                        Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= footer($data)?>
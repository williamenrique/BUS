<?= head($data)?>
<style type="text/css">
    /* Estilos para la gestión de permisos */
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
        --sidebar-width: 250px;
        --sidebar-collapsed-width: 70px;
        --transition-speed: 0.3s;
        --neon-green: #39ff14;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Card Styles */
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

    .card-body {
        padding: 20px;
    }

    /* Table Styles */
    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid var(--light-border);
    }

    th {
        background-color: var(--light-bg);
        font-weight: 600;
    }

    tr:hover {
        background-color: #f8f9fa;
    }

    /* Form Styles */
    .form-group {
        margin-bottom: 15px;
    }

    label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }

    select, input {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--light-border);
        border-radius: 4px;
        font-size: 16px;
    }

    /* Button Styles */
    .btn {
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: background-color 0.3s;
    }

    .btn-primary {
        background-color: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background-color: var(--secondary);
    }

    .btn-success {
        background-color: var(--success);
        color: white;
    }

    .btn-success:hover {
        background-color: #0d9669;
    }

    .btn-danger {
        background-color: var(--danger);
        color: white;
    }

    .btn-danger:hover {
        background-color: #dc2626;
    }

    .btn-sm {
        padding: 5px 10px;
        font-size: 12px;
    }

    /* Tabs */
    .tabs {
        display: flex;
        margin-bottom: 20px;
        border-bottom: 1px solid var(--light-border);
    }

    .tab {
        padding: 10px 20px;
        cursor: pointer;
        border-bottom: 3px solid transparent;
    }

    .tab.active {
        border-bottom: 3px solid var(--primary);
        color: var(--primary);
        font-weight: 500;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    /* Checkbox Styles */
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .checkbox-item input[type="checkbox"] {
        width: auto;
    }

    /* Badge Styles */
    .badge {
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
        margin-right: 5px;
    }

    .badge-success {
        background-color: var(--success);
        color: white;
    }

    .badge-warning {
        background-color: var(--warning);
        color: white;
    }

    /* Loading and feedback */
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
        margin-right: 10px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .alert-error {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .hidden {
        display: none;
    }

    /* ===== ESTILOS PARA GESTIÓN DE MENÚS ===== */

    /* Contenedor principal de la cuadrícula de menús */
    .menus-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }

    /* Formulario de menú */
    .menu-form {
        background: var(--light-secondary);
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        border: 1px solid var(--light-border);
    }

    .dark-mode .menu-form {
        background: var(--dark-secondary);
        border-color: var(--dark-border);
    }

    /* Tarjeta de menú individual */
    .menu-card {
        background: var(--light-secondary);
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

    .dark-mode .menu-card {
        background: var(--dark-secondary);
        border-color: var(--dark-border);
    }

    /* Encabezado de la tarjeta de menú */
    .menu-card-header {
        padding: 0.75rem 1rem;
        background: var(--light-bg);
        border-bottom: 1px solid var(--light-border);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dark-mode .menu-card-header {
        background: var(--dark-tertiary);
        border-color: var(--dark-border);
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

    .dark-mode .menu-card-header h4 {
        color: var(--dark-text);
    }

    /* Badge indicador de tipo de menú */
    .menu-badge {
        background: var(--primary);
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    /* Cuerpo de la tarjeta de menú */
    .menu-card-body {
        padding: 1rem;
    }

    /* Información del menú (página, orden) */
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

    .dark-mode .info-item .label {
        color: var(--dark-text);
    }

    .info-item .value {
        font-size: 0.85rem;
        color: var(--light-text);
        font-weight: 500;
    }

    .dark-mode .info-item .value {
        color: var(--dark-text);
    }

    /* Sección de submenús */
    .submenus-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid var(--light-border);
    }

    .dark-mode .submenus-section {
        border-color: var(--dark-border);
    }

    .submenus-section h5 {
        margin: 0 0 0.5rem 0;
        font-size: 0.9rem;
        color: var(--light-text);
        font-weight: 600;
    }

    .dark-mode .submenus-section h5 {
        color: var(--dark-text);
    }

    /* Lista de submenús */
    .submenus-list {
        display: flex;
        flex-direction: column;
        gap: 0.4rem;
    }

    /* Elemento individual de submenú */
    .submenu-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.5rem;
        background: var(--light-bg);
        border-radius: 4px;
        border: 1px solid var(--light-border);
    }

    .dark-mode .submenu-item {
        background: var(--dark-tertiary);
        border-color: var(--dark-border);
    }

    .submenu-info {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-grow: 1;
    }

    .submenu-info i {
        font-size: 0.8rem;
        color: var(--primary);
    }

    .submenu-info span {
        font-size: 0.85rem;
        color: var(--light-text);
        font-weight: 500;
    }

    .dark-mode .submenu-info span {
        color: var(--dark-text);
    }

    .submenu-info small {
        font-size: 0.75rem;
        color: var(--light-text);
        opacity: 0.7;
    }

    .dark-mode .submenu-info small {
        color: var(--dark-text);
    }

    /* Acciones de submenú */
    .submenu-actions {
        display: flex;
        gap: 0.25rem;
    }

    /* Acciones de la tarjeta de menú */
    .menu-card-actions {
        padding: 0.75rem 1rem;
        background: var(--light-bg);
        border-top: 1px solid var(--light-border);
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }

    .dark-mode .menu-card-actions {
        background: var(--dark-tertiary);
        border-color: var(--dark-border);
    }

    /* ===== FORMULARIO Y ELEMENTOS DE ENTRADA ===== */

    /* Filas del formulario */
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    /* Grupo de formulario */
    .form-group {
        margin-bottom: 0.75rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.25rem;
        font-weight: 500;
        font-size: 0.85rem;
        color: var(--light-text);
    }

    .dark-mode .form-group label {
        color: var(--dark-text);
    }

    /* Input compacto */
    .form-input.compact {
        padding: 0.4rem 0.6rem;
        font-size: 0.85rem;
        height: auto;
    }

    /* Checkbox compacto */
    .checkbox-item.compact {
        margin-top: 1.5rem;
        font-size: 0.85rem;
    }

    /* Acciones del formulario */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
        margin-top: 1rem;
    }

    /* ===== MODAL DE SUBMENÚS ===== */

    /* Modal overlay */
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
    }

    /* Contenido del modal */
    .modal-content.compact {
        background: var(--light-secondary);
        border-radius: 8px;
        width: 90%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
    }

    .dark-mode .modal-content.compact {
        background: var(--dark-secondary);
    }

    /* Encabezado del modal */
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid var(--light-border);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dark-mode .modal-header {
        border-color: var(--dark-border);
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.1rem;
        color: var(--light-text);
    }

    .dark-mode .modal-header h3 {
        color: var(--dark-text);
    }

    /* Botón de cierre */
    .close {
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--light-text);
        opacity: 0.7;
    }

    .dark-mode .close {
        color: var(--dark-text);
    }

    .close:hover {
        opacity: 1;
    }

    /* Cuerpo del modal */
    .modal-body {
        padding: 1rem;
    }

    /* Formulario de submenú */
    .submenu-form {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--light-border);
    }

    .dark-mode .submenu-form {
        border-color: var(--dark-border);
    }

    /* Lista de submenús en el modal */
    .submenus-list h4 {
        margin: 0 0 0.75rem 0;
        font-size: 0.95rem;
        color: var(--light-text);
        font-weight: 600;
    }

    .dark-mode .submenus-list h4 {
        color: var(--dark-text);
    }

    /* Elemento de lista de submenú */
    .submenu-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.6rem;
        background: var(--light-bg);
        border: 1px solid var(--light-border);
        border-radius: 4px;
        margin-bottom: 0.5rem;
    }

    .dark-mode .submenu-list-item {
        background: var(--dark-tertiary);
        border-color: var(--dark-border);
    }

    .submenu-list-item .submenu-info {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }

    .submenu-list-item .submenu-info strong {
        font-size: 0.85rem;
        color: var(--light-text);
    }

    .dark-mode .submenu-list-item .submenu-info strong {
        color: var(--dark-text);
    }

    .submenu-list-item .submenu-info span {
        font-size: 0.75rem;
        color: var(--light-text);
        opacity: 0.7;
    }

    .dark-mode .submenu-list-item .submenu-info span {
        color: var(--dark-text);
    }

    /* ===== COMPONENTES DE INTERFAZ ===== */

    /* Botones pequeños */
    .btn-sm {
        padding: 0.3rem 0.6rem;
        font-size: 0.8rem;
    }

    /* Botones con icono */
    .btn-icon {
        padding: 0.3rem;
        border: none;
        border-radius: 4px;
        background: transparent;
        color: var(--light-text);
        cursor: pointer;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dark-mode .btn-icon {
        color: var(--dark-text);
    }

    .btn-icon:hover {
        background: var(--light-bg);
    }

    .dark-mode .btn-icon:hover {
        background: var(--dark-tertiary);
    }

    /* ===== UTILIDADES ===== */

    /* Mensaje de datos no disponibles */
    .no-data {
        text-align: center;
        color: var(--light-text);
        opacity: 0.7;
        padding: 1.5rem;
        font-style: italic;
        font-size: 0.9rem;
    }

    .dark-mode .no-data {
        color: var(--dark-text);
    }

    /* Clase para ocultar elementos */
    .hidden {
        display: none !important;
    }

    /* ===== RESPONSIVE ===== */

    @media (max-width: 768px) {
        .menus-grid {
            grid-template-columns: 1fr;
        }
        
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .menu-info {
            grid-template-columns: 1fr;
        }
        
        .modal-content.compact {
            width: 95%;
            margin: 1rem;
        }
        
        .menu-card-actions {
            flex-wrap: wrap;
        }
    }

    @media (min-width: 1200px) {
        .menus-grid {
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        }
    }


    /** estulos de la modal de diar menu */
    /* Estilos para modales */
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
    background: var(--light-secondary);
    border-radius: 8px;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.dark-mode .modal-content {
    background: var(--dark-secondary);
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--light-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dark-mode .modal-header {
    border-color: var(--dark-border);
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: var(--light-text);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.dark-mode .modal-header h3 {
    color: var(--dark-text);
}

.modal-body {
    padding: 1.5rem;
}

/* Utilidades para modales */
.modal.compact .modal-content {
    max-width: 400px;
}

.hidden {
    display: none !important;
}
</style>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                <h1>GESTION DE MENU</h1>
                </div>
                <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="<?= BASE_URL()?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Menu</a></li>
                </ol>
                </div>
            </div>
            </div><!-- /.container-fluid -->
        </section>

        <!-- Main content -->
        <section class="content">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                    <!-- Default box -->
                        
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-user-cog"></i> Asignación de Permisos</h2>
                            </div>
                            <div class="card-body">
                                <!-- <div class="tabs">
                                    <div class="tab active" data-tab="usuarios">Usuarios</div>
                                    <div class="tab" data-tab="roles">Roles</div>
                                </div> -->

                                <div class="tab-content active" id="usuarios-tab">
                                    <div class="form-group">
                                        <label for="select-user">Seleccionar Usuario:</label>
                                        <select id="select-user" class="theme-select">
                                            <option value="">-- Seleccione un usuario --</option>
                                        </select>
                                    </div>

                                    <div id="user-permissions" class="hidden">
                                        <h3>Permisos actuales de: <span id="selected-username">Ningún usuario seleccionado</span></h3>
                                        
                                        <div id="permissions-container">
                                            <!-- Los menús y submenús se cargarán aquí dinámicamente -->
                                        </div>

                                        <div style="margin-top: 20px;">
                                            <button class="btn btn-success" id="btn-save-permissions">
                                                <i class="fas fa-save"></i> Guardar Cambios
                                            </button>
                                            <button class="btn btn-danger" id="btn-reset-permissions">
                                                <i class="fas fa-undo"></i> Restablecer
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-content" id="roles-tab">
                                    <div class="form-group">
                                        <label for="select-role">Seleccionar Rol:</label>
                                        <select id="select-role" class="theme-select">
                                            <option value="">-- Seleccione un rol --</option>
                                            <option value="1">Administrador</option>
                                            <option value="2">Vendedor</option>
                                            <option value="3">Inventario</option>
                                        </select>
                                    </div>

                                    <div id="role-permissions">
                                        <h3>Permisos actuales del rol: <span id="selected-rolename">Ningún rol seleccionado</span></h3>
                                        <p>Seleccione un rol para ver y editar sus permisos.</p>
                                    </div>
                                </div>

                                <div id="loading" class="hidden">
                                    <p><i class="fas fa-spinner fa-spin"></i> Cargando...</p>
                                </div>

                                <div id="message" class="alert hidden"></div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <h2><i class="fas fa-bars"></i> Gestión de Menús</h2>
                                <button class="btn btn-primary" id="btn-crear-menu">
                                    <i class="fas fa-plus"></i> Nuevo Menú
                                </button>
                            </div>
                            
                            <div class="card-body">
                                <!-- Formulario de creación (oculto inicialmente) -->
                                <div id="create-menu-form" class="hidden">
                                    <form id="form-crear-menu" class="menu-form">
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="menu_nombre">Nombre *</label>
                                                <input type="text" name="menu_nombre"  id="menu_nombre" class="form-input compact" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="menu_icono">Icono (FA) *</label>
                                                <input type="text"  name="menu_icono" id="menu_icono" class="form-input compact" placeholder="fas fa-home" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="menu_orden">Orden</label>
                                                <input type="number" name="menu_orden" id="menu_orden" class="form-input compact" value="0" min="0">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="checkbox-item compact">
                                                    <input type="checkbox" id="menu_tiene_submenu">
                                                    ¿Tiene submenús?
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div id="pagina-field" class="form-group">
                                            <label for="menu_pagina">Página/Ruta</label>
                                            <input type="text" name="menu_pagina" id="menu_pagina" class="form-input compact" placeholder="home/dashboard">
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="button" class="btn btn-secondary btn-sm" id="btn-cancelar-menu">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save"></i> Guardar
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Lista de menús existentes -->
                                <div id="menus-list-container" class="menus-container">
                                    <!-- Los menús se cargarán aquí dinámicamente -->
                                </div>
                            </div>
                        </div>

                        <!-- Modal para submenús -->
                        <div id="submenu-modal" class="modal hidden">
                            <div class="modal-content compact">
                                <div class="modal-header">
                                    <h3>Gestión de Submenús: <span id="modal-menu-name"></span></h3>
                                    <span class="close">&times;</span>
                                </div>
                                <div class="modal-body">
                                    <form id="form-submenu" class="submenu-form">
                                        <input type="hidden" id="submenu_menu_id">
                                        <input type="hidden" id="submenu_id">
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="submenu_nombre">Nombre *</label>
                                                <input type="text" id="submenu_nombre" class="form-input compact" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="submenu_pagina">Página *</label>
                                                <input type="text" id="submenu_pagina" class="form-input compact" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="submenu_link">URL *</label>
                                                <input type="text" id="submenu_link" class="form-input compact" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="submenu_orden">Orden</label>
                                                <input type="number" id="submenu_orden" class="form-input compact" value="0" min="0">
                                            </div>
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="button" class="btn btn-secondary btn-sm" id="btn-cancelar-submenu">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save"></i> Guardar
                                            </button>
                                        </div>
                                    </form>
                                    
                                    <div class="submenus-list">
                                        <h4>Submenús existentes</h4>
                                        <div id="submenus-container"></div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Modal para editar menú -->
                        <div id="edit-menu-modal" class="modal hidden">
                            <div class="modal-content compact">
                                <div class="modal-header">
                                    <h3><i class="fas fa-edit"></i> Editar Menú</h3>
                                    <span class="close modal-close" data-modal="edit-menu-modal">&times;</span>
                                </div>
                                <div class="modal-body">
                                    <form id="form-editar-menu" class="menu-form">
                                        <input type="hidden" id="edit_menu_id">
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="edit_menu_nombre">Nombre *</label>
                                                <input type="text" id="edit_menu_nombre" class="form-input compact" required>
                                            </div>
                                            
                                            <div class="form-group">
                                                <label for="edit_menu_icono">Icono (FA) *</label>
                                                <input type="text" id="edit_menu_icono" class="form-input compact" placeholder="fas fa-home" required>
                                            </div>
                                        </div>
                                        
                                        <div class="form-row">
                                            <div class="form-group">
                                                <label for="edit_menu_orden">Orden</label>
                                                <input type="number" id="edit_menu_orden" class="form-input compact" value="0" min="0">
                                            </div>
                                            
                                            <div class="form-group">
                                                <label class="checkbox-item compact">
                                                    <input type="checkbox" id="edit_menu_tiene_submenu">
                                                    ¿Tiene submenús?
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <div id="edit_pagina-field" class="form-group">
                                            <label for="edit_menu_pagina">Página/Ruta</label>
                                            <input type="text" id="edit_menu_pagina" class="form-input compact" placeholder="home/dashboard">
                                        </div>
                                        
                                        <div class="form-actions">
                                            <button type="button" class="btn btn-secondary btn-sm" id="btn-cancelar-edit-menu" data-modal="edit-menu-modal">
                                                Cancelar
                                            </button>
                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-save"></i> Actualizar Menú
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <!-- /box end -->
                    </div>
                </div>
            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

<?= footer($data)?>
/**
 * DataTableRefactor.js
 * Sistema de tablas dinámicas personalizadas para reemplazar DataTables
 */

class DynamicTable {
    constructor(config) {
        // Configuración básica
        this.tableId = config.tableId;
        this.apiUrl = config.apiUrl;
        this.columns = config.columns;
        this.container = config.container;
        this.pageSize = config.pageSize || 10;
        
        // Estado de la tabla
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
        this.data = [];
        this.filteredData = [];
        this.searchTerm = '';
        
        // Elementos DOM
        this.tableElement = null;
        this.tableBody = null;
        this.paginationElement = null;
        this.searchElement = null;
        this.infoElement = null;
        
        // Inicializar tabla
        this.initTable();
        this.loadData();
    }
    
    /**
     * Inicializa la estructura de la tabla
     */
    initTable() {
        const container = this.container ? $(this.container) : $(`#${this.tableId}`).parent();
        
        // Crear contenedor principal
        const tableContainer = $(`
            <div class="dynamic-table-container">
                <div class="table-header mb-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="search-box">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control form-control-sm table-search" 
                                       placeholder="Buscar...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary btn-clear-search" type="button">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <button class="btn btn-primary btn-search" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="page-size-selector">
                            <select class="form-control form-control-sm page-size-select" style="width: auto;">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="${this.tableId}" class="table table-bordered table-striped table-hover">
                        <thead class="thead-light">
                            <tr></tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <div class="table-footer mt-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="table-info"></div>
                        <div class="table-pagination"></div>
                    </div>
                </div>
            </div>
        `);
        
        // Insertar la tabla en el contenedor
        container.empty().append(tableContainer);
        
        // Configurar elementos
        this.tableElement = $(`#${this.tableId}`);
        this.tableBody = this.tableElement.find('tbody');
        this.paginationElement = tableContainer.find('.table-pagination');
        this.searchElement = tableContainer.find('.table-search');
        this.infoElement = tableContainer.find('.table-info');
        
        // Crear cabecera de columnas
        const headerRow = this.tableElement.find('thead tr');
        this.columns.forEach(column => {
            const th = $(`<th>${column.title}</th>`);
            if (column.width) th.css('width', column.width);
            if (column.className) th.addClass(column.className);
            headerRow.append(th);
        });
        
        // Configurar eventos
        this.setupEvents();
    }
    
    /**
     * Configura los eventos de la tabla
     */
    setupEvents() {
        // Evento de búsqueda
        this.searchElement.on('keyup', (e) => {
            if (e.key === 'Enter') {
                this.search();
            }
        });
        
        this.searchElement.closest('.table-header').find('.btn-search').on('click', () => this.search());
        
        this.searchElement.closest('.table-header').find('.btn-clear-search').on('click', () => {
            this.searchElement.val('');
            this.search();
        });
        
        // Evento de cambio de tamaño de página
        this.searchElement.closest('.dynamic-table-container').find('.page-size-select').on('change', (e) => {
            this.pageSize = parseInt($(e.target).val());
            this.currentPage = 1;
            this.renderTable();
        });
        
        // Eventos de ordenamiento (opcional)
        this.tableElement.find('th').each((index, th) => {
            if (this.columns[index] && this.columns[index].sortable !== false) {
                $(th).addClass('sortable');
                $(th).on('click', () => this.sortColumn(index));
            }
        });
    }
    
    /**
     * Carga datos desde la API
     */
    async loadData() {
        try {
            console.log('Cargando datos desde:', this.apiUrl);
            
            // Mostrar loading
            this.showLoading();
            
            const response = await fetch(this.apiUrl);
            console.log('Respuesta recibida:', response.status, response.statusText);
            
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            
            const result = await response.json();
            console.log('Datos recibidos:', result);
            
            if (result.success && result.data) {
                this.data = result.data;
                this.filteredData = [...this.data];
                this.totalRecords = this.data.length;
                this.calculateTotalPages();
                this.renderTable();
                console.log('Tabla renderizada con', this.totalRecords, 'registros');
            } else {
                console.error('Respuesta sin éxito:', result);
                this.showError('No se pudieron cargar los datos');
            }
        } catch (error) {
            console.error('Error cargando datos:', error);
            this.showError('Error al cargar los datos: ' + error.message);
        }
    }
    
    /**
     * Realiza búsqueda en los datos
     */
    search() {
        this.searchTerm = this.searchElement.val().toLowerCase().trim();
        this.currentPage = 1;
        
        if (!this.searchTerm) {
            this.filteredData = [...this.data];
        } else {
            this.filteredData = this.data.filter(row => {
                return this.columns.some(column => {
                    if (column.data && row[column.data]) {
                        const value = String(row[column.data]).toLowerCase();
                        return value.includes(this.searchTerm);
                    }
                    return false;
                });
            });
        }
        
        this.totalRecords = this.filteredData.length;
        this.calculateTotalPages();
        this.renderTable();
    }
    
    /**
     * Ordena por columna
     */
    sortColumn(columnIndex) {
        const column = this.columns[columnIndex];
        if (!column || !column.data) return;
        
        // Alternar entre ascendente y descendente
        const currentSort = this.tableElement.find('th').eq(columnIndex).data('sort') || 'asc';
        const newSort = currentSort === 'asc' ? 'desc' : 'asc';
        
        // Limpiar indicadores de ordenamiento
        this.tableElement.find('th').removeClass('sort-asc sort-desc').data('sort', null);
        
        // Aplicar nuevo ordenamiento
        this.tableElement.find('th').eq(columnIndex)
            .addClass(`sort-${newSort}`)
            .data('sort', newSort);
        
        // Ordenar datos
        this.filteredData.sort((a, b) => {
            let valueA = a[column.data];
            let valueB = b[column.data];
            
            // Convertir a string para comparación
            valueA = String(valueA || '').toLowerCase();
            valueB = String(valueB || '').toLowerCase();
            
            if (newSort === 'asc') {
                return valueA.localeCompare(valueB);
            } else {
                return valueB.localeCompare(valueA);
            }
        });
        
        this.renderTable();
    }
    
    /**
     * Calcula el total de páginas
     */
    calculateTotalPages() {
        this.totalPages = Math.ceil(this.totalRecords / this.pageSize);
        if (this.totalPages === 0) this.totalPages = 1;
    }
    
    /**
     * Renderiza los datos en la tabla
     */
    renderTable() {
        // Calcular datos para la página actual
        const startIndex = (this.currentPage - 1) * this.pageSize;
        const endIndex = startIndex + this.pageSize;
        const pageData = this.filteredData.slice(startIndex, endIndex);
        
        // Limpiar tabla
        this.tableBody.empty();
        
        if (pageData.length === 0) {
            const colspan = this.columns.length;
            this.tableBody.html(`
                <tr>
                    <td colspan="${colspan}" class="text-center text-muted py-4">
                        <i class="fas fa-database fa-2x mb-2"></i>
                        <p>No se encontraron registros</p>
                    </td>
                </tr>
            `);
        } else {
            // Agregar filas
            pageData.forEach(row => {
                const tr = $('<tr></tr>');
                
                this.columns.forEach(column => {
                    const td = $('<td></td>');
                    
                    // Renderizar celda según configuración
                    if (column.render) {
                        td.html(column.render(row));
                    } else if (column.data) {
                        td.text(row[column.data] || '');
                    }
                    
                    // Aplicar clases personalizadas
                    if (column.cellClass) {
                        td.addClass(column.cellClass);
                    }
                    
                    tr.append(td);
                });
                
                this.tableBody.append(tr);
            });
        }
        
        // Actualizar información y paginación
        this.updateInfo();
        this.renderPagination();
    }
    
    /**
     * Actualiza la información de la tabla
     */
    updateInfo() {
        const start = this.totalRecords === 0 ? 0 : (this.currentPage - 1) * this.pageSize + 1;
        const end = Math.min(this.currentPage * this.pageSize, this.totalRecords);
        
        let infoText = `Mostrando ${start} a ${end} de ${this.totalRecords} registros`;
        if (this.searchTerm) {
            infoText += ` (filtrados de ${this.data.length} totales)`;
        }
        
        this.infoElement.html(infoText);
    }
    
    /**
     * Renderiza la paginación
     */
    renderPagination() {
        if (this.totalPages <= 1) {
            this.paginationElement.empty();
            return;
        }
        
        const pagination = $('<nav><ul class="pagination pagination-sm mb-0"></ul></nav>');
        const ul = pagination.find('ul');
        
        // Botón anterior
        const prevLi = $(`
            <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `);
        ul.append(prevLi);
        
        // Páginas
        const maxVisible = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(this.totalPages, startPage + maxVisible - 1);
        
        if (endPage - startPage + 1 < maxVisible) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const li = $(`
                <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
            ul.append(li);
        }
        
        // Botón siguiente
        const nextLi = $(`
            <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${this.currentPage + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `);
        ul.append(nextLi);
        
        this.paginationElement.html(pagination);
        
        // Eventos de paginación
        this.paginationElement.find('.page-link').on('click', (e) => {
            e.preventDefault();
            const page = $(e.target).data('page') || $(e.target).parent().data('page');
            if (page && page !== this.currentPage) {
                this.currentPage = page;
                this.renderTable();
            }
        });
    }
    
    /**
     * Muestra estado de carga
     */
    showLoading() {
        const colspan = this.columns.length;
        this.tableBody.html(`
            <tr>
                <td colspan="${colspan}" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2 text-muted">Cargando datos...</p>
                </td>
            </tr>
        `);
        this.infoElement.html('Cargando datos...');
        this.paginationElement.empty();
    }
    
    /**
     * Muestra error
     */
    showError(message) {
        const colspan = this.columns.length;
        this.tableBody.html(`
            <tr>
                <td colspan="${colspan}" class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <p>${message}</p>
                    <button class="btn btn-sm btn-outline-primary btn-retry mt-2">
                        <i class="fas fa-redo mr-1"></i> Reintentar
                    </button>
                </td>
            </tr>
        `);
        
        this.infoElement.html('Error al cargar datos');
        this.paginationElement.empty();
        
        // Evento de reintento
        this.tableBody.find('.btn-retry').on('click', () => this.loadData());
    }
    
    /**
     * Recarga los datos
     */
    reload() {
        this.currentPage = 1;
        this.searchTerm = '';
        this.searchElement.val('');
        this.loadData();
    }
}

/**
 * Función para inicializar tabla de usuarios
 */
function initUsuariosDynamicTable() {
    console.log('Inicializando tabla dinámica de usuarios...');
    
    // Verificar que base_url esté definida
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida. Verificar que esté definida en el header.');
        return null;
    }
    
    const apiUrl = base_url + 'User/getUsuarios/';
    console.log('URL de la API:', apiUrl);
    
    const config = {
        tableId: 'usuariosTable',
        apiUrl: apiUrl,
        container: '#usuariosTable_wrapper',
        pageSize: 10,
        columns: [
            { 
                title: 'ID',
                data: 'usuario_id',
                width: '50px',
                className: 'text-center'
            },
            { 
                title: 'Identificación',
                data: 'personal_cedula',
                width: '120px'
            },
            { 
                title: 'Nombre Completo',
                render: (data) => {
                    return `${data.personal_nombre} ${data.personal_apellido}`;
                }
            },
            { 
                title: 'Nick',
                data: 'usuario_nick',
                width: '100px'
            },
            { 
                title: 'Email',
                data: 'personal_email'
            },
            { 
                title: 'Teléfono',
                data: 'personal_tlf',
                width: '120px'
            },
            { 
                title: 'Rol',
                data: 'rol_nombre',
                width: '120px'
            },
            { 
                title: 'Departamento',
                data: 'departamento_nombre',
                width: '150px'
            },
            { 
                title: 'Estado',
                width: '100px',
                render: (data) => {
                    return data.usuario_status == 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-danger">Inactivo</span>';
                }
            },
            { 
                title: 'Acciones',
                width: '150px',
                className: 'text-center',
                render: (data) => {
                    return `
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de usuario">
                            <button type="button" class="btn btn-primary btn-edit" data-id="${data.usuario_id}" title="Editar Usuario">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn ${data.usuario_status == 1 ? 'btn-danger' : 'btn-success'} btn-status" data-id="${data.usuario_id}" data-status="${data.usuario_status}" title="${data.usuario_status == 1 ? 'Desactivar Usuario' : 'Activar Usuario'}">
                                ${data.usuario_status == 1 ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check"></i>'}
                            </button>
                        </div>
                    `;
                }
            }
        ]
    };
    
    return new DynamicTable(config);
}

/**
 * Estilos CSS para la tabla dinámica
 */
function loadDynamicTableStyles() {
    const styles = `
        <style>
            .dynamic-table-container {
                position: relative;
            }
            
            .table-header {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #dee2e6;
            }
            
            .table-footer {
                background: #f8f9fa;
                padding: 10px;
                border-radius: 4px;
                border: 1px solid #dee2e6;
            }
            
            .table-info {
                font-size: 14px;
                color: #6c757d;
            }
            
            .sortable {
                cursor: pointer;
                position: relative;
            }
            
            .sortable:hover {
                background-color: #f2f2f2;
            }
            
            .sortable.sort-asc::after {
                content: ' ↑';
                font-size: 12px;
                color: #007bff;
            }
            
            .sortable.sort-desc::after {
                content: ' ↓';
                font-size: 12px;
                color: #007bff;
            }
            
            .page-size-selector {
                width: 80px;
            }
            
            .search-box {
                flex-grow: 1;
                max-width: 300px;
                min-width: 150px;
            }
            
            .table-responsive {
                max-height: 500px;
                overflow-y: auto;
            }
            
            .table tbody tr {
                transition: background-color 0.2s;
            }
            
            .table tbody tr:hover {
                background-color: #f5f5f5;
            }
            
            .btn-retry {
                transition: all 0.2s;
            }
            
            .btn-retry:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
        </style>
    `;
    
    if (!$('#dynamic-table-styles').length) {
        $('head').append(styles);
    }
}

// Inicializar estilos al cargar
$(document).ready(function() {
    loadDynamicTableStyles();
});

/**
 * Función para inicializar tabla de departamentos
 */
function initDepartamentosDynamicTable() {
    console.log('Inicializando tabla dinámica de departamentos...');
    
    // Verificar que base_url esté definida
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida. Verificar que esté definida en el header.');
        return null;
    }
    
    const apiUrl = base_url + 'User/getDepartamentos';
    console.log('URL de la API departamentos:', apiUrl);
    
    const config = {
        tableId: 'tableDepartamentos',
        apiUrl: apiUrl,
        container: '#tableDepartamentos_wrapper',
        pageSize: 10,
        columns: [
            { 
                title: 'ID',
                data: 'departamento_id',
                width: '50px',
                className: 'text-center'
            },
            { 
                title: 'Nombre',
                data: 'departamento_nombre'
            },
            { 
                title: 'Estado',
                width: '100px',
                className: 'text-center',
                render: (data) => {
                    return data.departamento_status == 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-danger">Inactivo</span>';
                }
            },
            { 
                title: 'Acciones',
                width: '150px',
                className: 'text-center',
                render: (data) => {
                    return `
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de departamento">
                            <button type="button" class="btn btn-primary btn-edit-depto" data-id="${data.departamento_id}" title="Editar Departamento">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-delete-depto" data-id="${data.departamento_id}" title="Eliminar Departamento">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    };
    
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de roles
 */
function initRolesDynamicTable() {
    console.log('Inicializando tabla dinámica de roles...');
    
    // Verificar que base_url esté definida
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida. Verificar que esté definida en el header.');
        return null;
    }
    
    const apiUrl = base_url + 'User/getRolesForTable';
    console.log('URL de la API roles:', apiUrl);
    
    const config = {
        tableId: 'tableRoles',
        apiUrl: apiUrl,
        container: '#tableRoles_wrapper',
        pageSize: 10,
        columns: [
            { 
                title: 'ID',
                data: 'rol_id',
                width: '50px',
                className: 'text-center'
            },
            { 
                title: 'Nombre',
                data: 'rol_nombre'
            },
            { 
                title: 'Estado',
                width: '100px',
                className: 'text-center',
                render: (data) => {
                    return data.rol_status == 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-danger">Inactivo</span>';
                }
            },
            { 
                title: 'Acciones',
                width: '150px',
                className: 'text-center',
                render: (data) => {
                    return `
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de rol">
                            <button type="button" class="btn btn-primary btn-edit-rol" data-id="${data.rol_id}" title="Editar Rol">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-delete-rol" data-id="${data.rol_id}" title="Eliminar Rol">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    };
    
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de inventario de productos
 */
function initInventarioDynamicTable() {
    console.log('Inicializando tabla dinámica de inventario...');
    
    // Verificar que base_url esté definida
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida. Verificar que esté definida en el header.');
        return null;
    }
    
    const apiUrl = base_url + 'Producto/getInventario';
    console.log('URL de la API inventario:', apiUrl);
    
    const config = {
        tableId: 'tableInventario',
        apiUrl: apiUrl,
        container: '#tableInventario_wrapper',
        pageSize: 15,
        columns: [
            { 
                title: 'ID',
                data: 'id_producto',
                width: '50px',
                className: 'text-center'
            },
            { 
                title: 'Artículo',
                data: 'producto'
            },
            { 
                title: 'Tipo',
                data: 'enlace_producto'
            },
            { 
                title: 'Proveedor',
                data: 'empresa_proveedor'
            },
            { 
                title: 'Ubicación',
                data: 'ubicacion'
            },
            { 
                title: 'Stock',
                width: '120px',
                className: 'text-center',
                render: (data) => {
                    const stock = parseFloat(data.cant_producto);
                    const presentacion = data.present_producto || 'Und';
                    if (stock <= 0) {
                        return `<span class="stock-badge stock-out">Sin Stock</span>`;
                    } else if (stock < 10) {
                        return `<span class="stock-badge stock-low">${stock} ${presentacion}</span>`;
                    } else {
                        return `<span class="stock-badge stock-high">${stock} ${presentacion}</span>`;
                    }
                }
            }
        ]
    };
    
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de órdenes
 */
function initOrdenesDynamicTable() {
    console.log('Inicializando tabla dinámica de órdenes...');
    
    // Verificar que base_url esté definida
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida. Verificar que esté definida en el header.');
        return null;
    }
    
    const apiUrl = base_url + 'Orden/getOrdenes';
    console.log('URL de la API órdenes:', apiUrl);
    
    const config = {
        tableId: 'tblOrdenes',
        apiUrl: apiUrl,
        container: '#tblOrdenes_wrapper',
        pageSize: 10,
        columns: [
            { 
                title: 'ID',
                data: 'id_despacho',
                width: '50px',
                className: 'text-center'
            },
            { 
                title: 'Fecha',
                data: 'fecha_aprobacion',
                width: '120px'
            },
            { 
                title: 'Unidad',
                render: (data) => {
                    return `${data.id_unidad} - ${data.modelo_unidad}`;
                }
            },
            { 
                title: 'Estado',
                className: 'text-center',
                render: (data) => {
                    return data.estado_badge || '';
                }
            },
            { 
                title: 'Creador',
                data: 'creador_nombre'
            },
            { 
                title: 'Artículos',
                className: 'text-center',
                render: (data) => {
                    return `<span class="badge badge-info">${data.total_articulos} artículos</span>`;
                }
            },
            { 
                title: 'Acciones',
                className: 'text-center',
                render: (data) => {
                    return `<div class="btn-group">${data.acciones}</div>`;
                }
            }
        ]
    };
    
    return new DynamicTable(config);
}
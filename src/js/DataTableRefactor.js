/**
 * DataTableRefactor.js
 * Sistema de tablas dinámicas personalizadas para reemplazar DataTables
 */

// =================================================================================
// CLASE PRINCIPAL - DEFINIDA PRIMERO
// =================================================================================

class DynamicTable {
    constructor(config) {
        // Configuración básica
        this.tableId = config.tableId;
        this.apiUrl = config.apiUrl;
        this.columns = config.columns;
        this.container = config.container;
        this.pageSize = config.pageSize || 10;
        
        // Callbacks
        this.onLoad = config.onLoad;
        this.onDraw = config.onDraw;
        
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
            
            // Verificar si la respuesta tiene el formato esperado
            let dataArray = [];
            if (result.success && result.data) {
                dataArray = result.data;
            } else if (Array.isArray(result)) {
                // Si la respuesta es un array directo
                dataArray = result;
            } else {
                console.error('Formato de respuesta no reconocido:', result);
                this.showError('Formato de datos no reconocido');
                return;
            }
            
            if (dataArray.length > 0 || dataArray === []) {
                this.data = dataArray;
                this.filteredData = [...this.data];
                this.totalRecords = this.data.length;
                this.calculateTotalPages();
                this.renderTable();
                console.log('Tabla renderizada con', this.totalRecords, 'registros');
                if (typeof this.onLoad === 'function') {
                    this.onLoad(this.data);
                }
            } else {
                console.error('Datos vacíos o formato incorrecto:', result);
                this.showError('No se pudieron cargar los datos');
            }
        } catch (error) {
            console.error('Error cargando datos:', error);
            this.showError('Error al cargar los datos: ' + error.message);
        }
    }
    
    /**
     * Realiza búsqueda en los datos - VERSIÓN MEJORADA
     */
    search() {
        this.searchTerm = this.searchElement.val().toLowerCase().trim();
        this.currentPage = 1;
        
        if (!this.searchTerm) {
            this.filteredData = [...this.data];
        } else {
            this.filteredData = this.data.filter(row => {
                // Buscar en columnas con 'data'
                const columnMatch = this.columns.some(column => {
                    if (column.data && row[column.data] !== undefined && row[column.data] !== null) {
                        const value = String(row[column.data]).toLowerCase();
                        return value.includes(this.searchTerm);
                    }
                    return false;
                });
                
                if (columnMatch) return true;
                
                // BÚSQUEDA EN TODOS LOS CAMPOS DEL OBJETO
                for (const key in row) {
                    if (row[key] !== undefined && row[key] !== null) {
                        const value = String(row[key]).toLowerCase();
                        if (value.includes(this.searchTerm)) return true;
                    }
                }
                
                return false;
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
        if (typeof this.onDraw === 'function') {
            this.onDraw(pageData);
        }
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

// =================================================================================
// ESTILOS CSS
// =================================================================================

function loadDynamicTableStyles() {
    const styles = `
        <style>
            .dynamic-table-container { position: relative; }
            .table-header { background: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6; }
            .table-footer { background: #f8f9fa; padding: 10px; border-radius: 4px; border: 1px solid #dee2e6; }
            .table-info { font-size: 14px; color: #6c757d; }
            .sortable { cursor: pointer; position: relative; }
            .sortable:hover { background-color: #f2f2f2; }
            .sortable.sort-asc::after { content: ' ↑'; font-size: 12px; color: #007bff; }
            .sortable.sort-desc::after { content: ' ↓'; font-size: 12px; color: #007bff; }
            .page-size-selector { width: 80px; }
            .search-box { flex-grow: 1; max-width: 300px; min-width: 150px; }
            .table-responsive { max-height: 500px; overflow-y: auto; }
            .table tbody tr { transition: background-color 0.2s; }
            .table tbody tr:hover { background-color: #f5f5f5; }
            .btn-retry { transition: all 0.2s; }
            .btn-retry:hover { transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        </style>
    `;
    
    if (!$('#dynamic-table-styles').length) {
        $('head').append(styles);
    }
}

$(document).ready(function() {
    loadDynamicTableStyles();
});

// =================================================================================
// FUNCIONES DE INICIALIZACIÓN DE TABLAS
// =================================================================================

/**
 * Función para inicializar tabla de usuarios
 */
function initUsuariosDynamicTable() {
    console.log('Inicializando tabla dinámica de usuarios...');
    if (typeof base_url === 'undefined') { console.error('base_url no está definida.'); return null; }
    
    const config = {
        tableId: 'usuariosTable',
        apiUrl: base_url + 'User/getUsuarios/',
        container: '#usuariosTable_wrapper',
        pageSize: 10,
        columns: [
            { title: 'ID', data: 'usuario_id', width: '50px', className: 'text-center' },
            { title: 'Identificación', data: 'personal_cedula', width: '120px' },
            { title: 'Nombre Completo', render: (data) => `${data.personal_nombre} ${data.personal_apellido}` },
            { title: 'Nick', data: 'usuario_nick', width: '100px' },
            { title: 'Email', data: 'personal_email' },
            { title: 'Teléfono', data: 'personal_tlf', width: '120px' },
            { title: 'Rol', data: 'rol_nombre', width: '120px' },
            { title: 'Departamento', data: 'departamento_nombre', width: '150px' },
            { 
                title: 'Estado', width: '100px',
                render: (data) => data.usuario_status == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'
            },
            { 
                title: 'Acciones', width: '150px', className: 'text-center',
                render: (data) => `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary btn-edit" data-id="${data.usuario_id}"><i class="fas fa-edit"></i></button>
                        <button class="btn ${data.usuario_status == 1 ? 'btn-danger' : 'btn-success'} btn-status" data-id="${data.usuario_id}">
                            ${data.usuario_status == 1 ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check"></i>'}
                        </button>
                    </div>
                `
            }
        ]
    };
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de departamentos
 */
function initDepartamentosDynamicTable() {
    console.log('Inicializando tabla dinámica de departamentos...');
    if (typeof base_url === 'undefined') { console.error('base_url no está definida.'); return null; }
    
    const config = {
        tableId: 'tableDepartamentos',
        apiUrl: base_url + 'User/getDepartamentos',
        container: '#tableDepartamentos_wrapper',
        pageSize: 10,
        columns: [
            { title: 'ID', data: 'departamento_id', width: '50px', className: 'text-center' },
            { title: 'Nombre', data: 'departamento_nombre' },
            { 
                title: 'Estado', width: '100px', className: 'text-center',
                render: (data) => data.departamento_status == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'
            },
            { 
                title: 'Acciones', width: '150px', className: 'text-center',
                render: (data) => `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary btn-edit-depto" data-id="${data.departamento_id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-delete-depto" data-id="${data.departamento_id}"><i class="fas fa-trash"></i></button>
                    </div>
                `
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
    if (typeof base_url === 'undefined') { console.error('base_url no está definida.'); return null; }
    
    const config = {
        tableId: 'tableRoles',
        apiUrl: base_url + 'User/getRolesForTable',
        container: '#tableRoles_wrapper',
        pageSize: 10,
        columns: [
            { title: 'ID', data: 'rol_id', width: '50px', className: 'text-center' },
            { title: 'Nombre', data: 'rol_nombre' },
            { 
                title: 'Estado', width: '100px', className: 'text-center',
                render: (data) => data.rol_status == 1 ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'
            },
            { 
                title: 'Acciones', width: '150px', className: 'text-center',
                render: (data) => `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-primary btn-edit-rol" data-id="${data.rol_id}"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-danger btn-delete-rol" data-id="${data.rol_id}"><i class="fas fa-trash"></i></button>
                    </div>
                `
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
    if (typeof base_url === 'undefined') { console.error('base_url no está definida.'); return null; }
    
    const config = {
        tableId: 'tableInventario',
        apiUrl: base_url + 'Producto/getInventario',
        container: '#tableInventario_wrapper',
        pageSize: 15,
        columns: [
            { title: 'ID', data: 'id_producto', width: '50px', className: 'text-center' },
            { title: 'Artículo', data: 'producto' },
            { title: 'Tipo', data: 'enlace_producto' },
            { title: 'Proveedor', data: 'empresa_proveedor' },
            { title: 'Ubicación', data: 'ubicacion' },
            { 
                title: 'Stock', data: 'cant_producto', width: '120px', className: 'text-center',
                render: (data) => {
                    const stock = parseFloat(data.cant_producto);
                    const presentacion = data.present_producto || 'Und';
                    if (stock <= 0) return '<span class="stock-badge stock-out">Sin Stock</span>';
                    else if (stock < 10) return `<span class="stock-badge stock-low">${stock} ${presentacion}</span>`;
                    else return `<span class="stock-badge stock-high">${stock} ${presentacion}</span>`;
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
    if (typeof base_url === 'undefined') { console.error('base_url no está definida.'); return null; }
    
    const config = {
        tableId: 'tblOrdenes',
        apiUrl: base_url + 'Orden/getOrdenes',
        container: '#tblOrdenes_wrapper',
        pageSize: 10,
        columns: [
            { title: 'ID', data: 'id_despacho', width: '50px', className: 'text-center' },
            { title: 'Fecha', data: 'fecha_aprobacion', width: '120px' },
            { title: 'Unidad', render: (data) => `${data.id_unidad} - ${data.modelo_unidad}` },
            { title: 'Estado', className: 'text-center', render: (data) => data.estado_badge || '' },
            { title: 'Creador', data: 'creador_nombre' },
            { title: 'Artículos', className: 'text-center', render: (data) => `<span class="badge badge-info">${data.total_articulos} artículos</span>` },
            { title: 'Acciones', className: 'text-center', render: (data) => `<div class="btn-group">${data.acciones}</div>` }
        ]
    };
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de flota
 */
function initFlotaDynamicTable(options = {}) {
    console.log('Inicializando tabla dinámica de flota...');
    if (typeof base_url === 'undefined') { console.error('base_url no está definida.'); return null; }
    
    const localStatusMap = typeof statusMap !== 'undefined' ? statusMap : {
        0: { text: 'Desincorporada', color: 'badge-secondary' },
        1: { text: 'Operativa', color: 'badge-success' },
        2: { text: 'Inoperativa', color: 'badge-warning' },
        3: { text: 'Mantenimiento', color: 'badge-info' },
        4: { text: 'Por Desincorporar', color: 'badge-purple' },
        5: { text: 'Crítica', color: 'badge-danger' }
    };
    
    const config = {
        tableId: 'tableFlota',
        apiUrl: base_url + 'Flota/getFlota',
        container: '#tableFlota_wrapper',
        pageSize: 10,
        onLoad: options.onLoad,
        onDraw: options.onDraw,
        columns: [
            { 
                title: 'ID Unidad',
                data: 'id_unidad',
                render: (data) => `<a href="${base_url}flota/historialunidad/${data.id_flota}" class="font-weight-bold">${data.id_unidad}</a>`
            },
            { title: 'Marca', data: 'marca_unidad' },
            { title: 'Modelo', data: 'modelo_unidad' },
            { title: 'VIN', data: 'vim_unidad' },
            { 
                title: 'Estado', data: 'status_unidad', className: 'text-center',
                render: (data) => {
                    const status = localStatusMap[data.status_unidad] || { text: 'Desconocido', color: 'badge-light' };
                    return `<span class="badge ${status.color}">${status.text}</span>`;
                }
            },
            { 
                title: 'Acciones', className: 'text-center',
                render: (data) => `
                    <div class="btn-group">
                        <button onclick="fntViewUnidad(${data.id_flota})" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                        <button onclick="fntEditUnidad(${data.id_flota})" class="btn btn-primary btn-sm"><i class="fas fa-pencil-alt"></i></button>
                        <button onclick="fntStatusUnidad(${data.id_flota})" class="btn btn-warning btn-sm"><i class="fas fa-exchange-alt"></i></button>
                    </div>
                `
            }
        ]
    };
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de productos
 */
function initProductosDynamicTable() {
    console.log('Inicializando tabla dinámica de productos...');
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida.');
        return null;
    }
    
    const config = {
        tableId: 'tableProducto',
        apiUrl: base_url + 'Producto/getProductos',
        container: '#tableProducto_wrapper',
        pageSize: 10,
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
                data: 'cant_producto',
                width: '120px', 
                className: 'text-center',
                render: (data) => {
                    const stock = parseFloat(data.cant_producto);
                    const presentacion = data.present_producto || 'Und';
                    if (stock <= 0) {
                        return `<a href="javascript:void(0)" onclick="cargarStockForm(${data.id_producto})" class="stock-badge stock-out" style="cursor:pointer;text-decoration:none;">Sin Stock <i class="fas fa-plus-circle ml-1"></i></a>`;
                    } else if (stock < 10) {
                        return `<span class="stock-badge stock-low">${stock} ${presentacion}</span>`;
                    } else {
                        return `<span class="stock-badge stock-high">${stock} ${presentacion}</span>`;
                    }
                }
            },
            { 
                title: 'Acciones', 
                width: '100px', 
                className: 'text-center',
                render: (data) => `<button class="btn btn-danger btn-sm" onClick="fntDelProducto(${data.id_producto})"><i class="far fa-trash-alt"></i></button>`
            }
        ]
    };
    return new DynamicTable(config);
}

/**
 * Función para inicializar tabla de personal
 */
function initPersonalDynamicTable() {
    console.log('Inicializando tabla dinámica de personal...');
    
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida.');
        return null;
    }
    
    const apiUrl = base_url + 'Personal/getPersonal';
    console.log('URL de la API personal:', apiUrl);
    
    const statusMap = {
        0: { text: 'Inactivo', class: 'badge-danger' },
        1: { text: 'Activo', class: 'badge-success' },
        2: { text: 'Vacaciones', class: 'badge-info' },
        3: { text: 'Reposo', class: 'badge-warning' }
    };
    
    const config = {
        tableId: 'tablePersonal',
        apiUrl: apiUrl,
        container: '#tablePersonal_wrapper',
        pageSize: 10,
        onDraw: function(data) {
            $('.status-badge').off('click').on('click', function() {
                const idPersonal = $(this).data('id');
                if (typeof fntStatusPersonal !== 'undefined') {
                    fntStatusPersonal(idPersonal);
                }
            });
        },
        columns: [
            { 
                title: 'Cédula',
                data: 'personal_cedula',
                width: '120px'
            },
            { 
                title: 'Nombre Completo',
                data: 'personal_nombre',
                render: (data) => `${data.personal_nombre} ${data.personal_apellido}`
            },
            { 
                title: 'Cargo',
                data: 'cargo'
            },
            { 
                title: 'Teléfono',
                data: 'personal_tlf',
                render: (data) => data.personal_tlf || 'N/A'
            },
            { 
                title: 'Email',
                data: 'personal_email',
                render: (data) => data.personal_email || 'N/A'
            },
            { 
                title: 'Estado',
                data: 'personal_status',
                width: '120px',
                className: 'text-center',
                render: (data) => {
                    const status = statusMap[data.personal_status] || { text: 'Desconocido', class: 'badge-secondary' };
                    return `<span class="badge ${status.class} status-badge" data-id="${data.id_personal}" style="cursor:pointer;">${status.text}</span>`;
                }
            },
            { 
                title: 'Acciones',
                width: '150px',
                className: 'text-center',
                render: (data) => `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-info btn-view" data-id="${data.id_personal}"><i class="far fa-eye"></i></button>
                        <button class="btn btn-primary btn-edit" data-id="${data.id_personal}"><i class="fas fa-pencil-alt"></i></button>
                        <button class="btn btn-danger btn-delete" data-id="${data.id_personal}"><i class="far fa-trash-alt"></i></button>
                    </div>
                `
            }
        ]
    };
    
    const table = new DynamicTable(config);
    
    $(document).on('click', '#tablePersonal .btn-view', function() {
        const id = $(this).data('id');
        if (typeof fntViewPersonal !== 'undefined') fntViewPersonal(id);
    });
    
    $(document).on('click', '#tablePersonal .btn-edit', function() {
        const id = $(this).data('id');
        if (typeof fntEditPersonal !== 'undefined') fntEditPersonal(id);
    });
    
    $(document).on('click', '#tablePersonal .btn-delete', function() {
        const id = $(this).data('id');
        if (typeof fntDelPersonal !== 'undefined') fntDelPersonal(id);
    });
    
    return table;
}

/**
 * Función para inicializar tabla de requisiciones
 */
function initRequisicionesDynamicTable() {
    console.log('Inicializando tabla dinámica de requisiciones...');
    if (typeof base_url === 'undefined') {
        console.error('base_url no está definida.');
        return null;
    }
    
    const apiUrl = base_url + 'Requisicion/getRequisiciones';
    console.log('URL de la API requisiciones:', apiUrl);
    
    const config = {
        tableId: 'tableRequisicion',
        apiUrl: apiUrl,
        container: '#tableRequisicion_wrapper',
        pageSize: 10,
        columns: [
            { 
                title: 'ID', 
                data: 'id_despacho', 
                width: '60px', 
                className: 'text-center' 
            },
            { 
                title: 'Fecha', 
                data: 'fecha_despacho',
                width: '130px'
            },
            { 
                title: 'Unidad', 
                data: 'id_unidad',
                render: (data) => {
                    return data.id_unidad || 'N/A';
                }
            },
            { 
                title: 'Tipo', 
                data: 'tipo_orden',
                width: '100px',
                className: 'text-center'
            },
            { 
                title: 'Creador', 
                render: (data) => {
                    const nombre = data.creador_nombre || '';
                    const apellido = data.creador_apellido || '';
                    return `${nombre} ${apellido}`.trim() || 'N/A';
                }
            },
            { 
                title: 'Estado', 
                data: 'estado_orden',
                className: 'text-center'
            },
            { 
                title: 'Acciones', 
                className: 'text-center',
                render: (data) => data.acciones || ''
            }
        ]
    };
    
    return new DynamicTable(config);
}
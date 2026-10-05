document.addEventListener('DOMContentLoaded', function () {
    const idFlota = document.getElementById('id_flota').value;
    const timelineContainer = document.getElementById('timelineContainer');
    const loader = document.getElementById('timeline-loader');
    const emptyState = document.getElementById('timeline-empty');
    const paginationContainer = document.getElementById('pagination-container');
    const formFiltros = document.getElementById('formFiltros');
    const btnLimpiarFiltros = document.getElementById('btnLimpiarFiltros');

    let currentPage = 1;
    const itemsPerPage = 5; // Cantidad de eventos a mostrar por página

    // --- CARGA INICIAL Y FILTROS ---

    // Carga inicial de datos
    loadHistory(currentPage);

    // Event listener para el formulario de filtros
    formFiltros.addEventListener('submit', function (e) {
        e.preventDefault();
        currentPage = 1; // Resetear a la primera página en cada nueva búsqueda
        loadHistory(currentPage);
    });

    // Event listener para limpiar filtros
    btnLimpiarFiltros.addEventListener('click', function () {
        formFiltros.reset();
        currentPage = 1;
        loadHistory(currentPage);
    });

    // --- FUNCIONES PRINCIPALES ---

    /**
     * Carga el historial desde el servidor aplicando filtros y paginación.
     * @param {number} page - El número de página a cargar.
     */
    async function loadHistory(page) {
        const formData = new FormData(formFiltros);
        const postData = Object.fromEntries(formData.entries());
        postData.page = page;

        showLoading(true);
        timelineContainer.innerHTML = ''; // Limpiar vista anterior

        try {
            const response = await fetch(`${base_url}Flota/getHistorialUnidad/${idFlota}`, { // Corregido
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(postData)
            });

            if (!response.ok) throw new Error('Error en la respuesta del servidor.');

            const result = await response.json();

            if (result.success && result.data && result.data.items.length > 0) {
                renderTimeline(result.data.items);
                renderPagination(result.pagination.total_pages, page);
                showLoading(false);
            } else if (result.success) {
                showEmptyState(true);
            }

        } catch (error) {
            console.error('Error al cargar el historial:', error);
            notifi('Error de conexión al cargar el historial.', 'error');
            showEmptyState(true);
        }
    }

    /**
     * Renderiza los items del historial en el contenedor del timeline.
     * @param {Array} items - Array de objetos con los datos del historial.
     */
    function renderTimeline(items) {
        let timelineHTML = '<div class="timeline">';
        items.forEach(item => {
            timelineHTML += createTimelineItem(item);
        });
        timelineHTML += '</div>';
        timelineContainer.innerHTML = timelineHTML;
    }

    /**
     * Crea el HTML para un único item del timeline basado en su tipo.
     * @param {object} item - El objeto del evento del historial.
     * @returns {string} - El HTML del item.
     */
    function createTimelineItem(item) {
        const { icon, color, title, details } = getTimelineItemStyle(item);
        // Se trata la fecha del servidor como UTC para evitar que el navegador la ajuste a la zona horaria local.
        const dateOptions = { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'UTC' };
        const formattedDate = new Date(item.fecha).toLocaleDateString('es-ES', dateOptions);

        return `
            <div>
                <i class="fas ${icon} ${color.bg}"></i>
                <div class="timeline-item">
                    <span class="time"><i class="fas fa-calendar-alt mr-1"></i> ${formattedDate}</span>
                    <h3 class="timeline-header">
                        <a href="#">${title}</a>
                    </h3>
                    <div class="timeline-body">
                        ${details}
                    </div>
                    <div class="timeline-footer text-right text-sm">
                        <i class="fas fa-user mr-1"></i> Registrado por: <strong>${item.usuario || 'Sistema'}</strong>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Devuelve el estilo y contenido específico para cada tipo de evento.
     * @param {object} item - El objeto del evento.
     * @returns {object} - Un objeto con icono, color, título y detalles.
     */
    function getTimelineItemStyle(item) {
        switch (item.tipo) {
            case 'despacho': {
                const detalles = JSON.parse(item.detalles);
                let articulosHTML = '<li>No se encontraron artículos.</li>';
                if (detalles.articulos && detalles.articulos.length > 0) {
                    articulosHTML = detalles.articulos.map(art => `<li><span class="font-weight-bold">${art.cant_despacho}x</span> ${art.producto}</li>`).join('');
                }
                return {
                    icon: 'fa-dolly',
                    color: { bg: 'bg-primary' },
                    title: `Orden de Despacho #${item.id_evento}`,
                    details: `<p class="mb-2">Se despacharon los siguientes artículos:</p><ul class="list-unstyled pl-3">${articulosHTML}</ul>`
                };
            }
            case 'aceite': {
                const detalles = JSON.parse(item.detalles);
                const kmAnterior = detalles.kilometraje_anterior;
                const kmCambio = detalles.kilometraje_cambio;
                let detailsAceite;

                if (kmAnterior && kmAnterior > 0) {
                    detailsAceite = `<p>Cambio registrado de 
                                <strong class="text-muted">${new Intl.NumberFormat('es-ES').format(kmAnterior)} km</strong> 
                                a 
                                <strong class="text-success">${new Intl.NumberFormat('es-ES').format(kmCambio)} km</strong>.
                               </p>`;
                } else {
                    detailsAceite = `<p>Se registró un cambio de aceite a los <strong class="font-weight-bold">${new Intl.NumberFormat('es-ES').format(kmCambio)} Km</strong>.</p>`;
                }
                return {
                    icon: 'fa-oil-can',
                    color: { bg: 'bg-warning' },
                    title: 'Cambio de Aceite',
                    details: detailsAceite
                };
            }
            case 'mantenimiento': {
                const detalles = JSON.parse(item.detalles);
                return {
                    icon: 'fa-tools',
                    color: { bg: 'bg-success' },
                    title: `Mantenimiento #${item.id_evento}`,
                    details: `<p><strong>Tipo:</strong> ${detalles.tipo_mantenimiento === 'P' ? 'Preventivo' : 'Correctivo'}</p>
                              <p><strong>Diagnóstico:</strong> ${detalles.diagnostico || 'N/D'}</p>`
                };
            }
            case 'status': {
                const detalles = JSON.parse(item.detalles);
                return {
                    icon: 'fa-info-circle',
                    color: { bg: 'bg-info' },
                    title: 'Cambio de Estado',
                    details: `<p>La unidad cambió su estado a <span class="font-weight-bold">${detalles.status_texto}</span>.</p>
                              <p><strong>Motivo:</strong> ${detalles.motivo}</p>`
                };
            }
            default:
                return { icon: 'fa-question-circle', color: { bg: 'bg-secondary' }, title: 'Evento Desconocido', details: '' };
        }
    }

    /**
     * Renderiza los controles de paginación.
     * @param {number} totalItems - El número total de items en la BD.
     * @param {number} activePage - La página actualmente activa.
     */
    function renderPagination(totalPages, activePage) {
        paginationContainer.innerHTML = '';

        if (totalPages <= 1) return;

        let paginationHTML = '<ul class="pagination pagination-sm m-0 float-right">';

        // Botón "Anterior"
        paginationHTML += `
            <li class="page-item ${activePage === 1 ? 'disabled' : ''}">
                <a class="page-link pagination-btn" href="#" data-page="${activePage - 1}">&laquo;</a>
            </li>
        `;

        // Lógica para paginación con elipsis
        const maxVisiblePages = 5; // Máximo de botones de página visibles (ej: 1 ... 4 5 6 ... 10)
        const pagesOnEachSide = 1; // Páginas a cada lado de la página activa

        if (totalPages <= maxVisiblePages) {
            // Si hay pocas páginas, mostrarlas todas
            for (let i = 1; i <= totalPages; i++) {
                paginationHTML += createPageItem(i, activePage);
            }
        } else {
            // Mostrar la primera página
            paginationHTML += createPageItem(1, activePage);

            // Elipsis inicial si es necesario
            if (activePage > pagesOnEachSide + 2) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            // Rango de páginas alrededor de la activa
            let startPage = Math.max(2, activePage - pagesOnEachSide);
            let endPage = Math.min(totalPages - 1, activePage + pagesOnEachSide);

            if (activePage <= pagesOnEachSide + 1) {
                endPage = 1 + (pagesOnEachSide * 2);
            }
            if (activePage >= totalPages - pagesOnEachSide) {
                startPage = totalPages - (pagesOnEachSide * 2);
            }

            for (let i = startPage; i <= endPage; i++) {
                paginationHTML += createPageItem(i, activePage);
            }

            // Elipsis final si es necesario
            if (activePage < totalPages - pagesOnEachSide - 1) {
                paginationHTML += '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }

            // Mostrar la última página
            paginationHTML += createPageItem(totalPages, activePage);
        }

        // Botón "Siguiente"
        paginationHTML += `
            <li class="page-item ${activePage === totalPages ? 'disabled' : ''}">
                <a class="page-link pagination-btn" href="#" data-page="${activePage + 1}">&raquo;</a>
            </li>
        `;

        paginationHTML += '</ul>';
        paginationContainer.innerHTML = paginationHTML;

        // Agregar event listeners a los botones de paginación
        document.querySelectorAll('.pagination-btn').forEach(button => {
            button.addEventListener('click', function () {
                const page = parseInt(this.dataset.page);
                if (page && !this.parentElement.classList.contains('disabled')) {
                    currentPage = page;
                    loadHistory(currentPage);
                }
            });
        });
    }

    /**
     * Crea el HTML para un botón de página individual.
     * @param {number} pageNumber - El número de la página.
     * @param {number} activePage - La página activa actual.
     * @returns {string} - El HTML del elemento <li>.
     */
    function createPageItem(pageNumber, activePage) {
        return `
            <li class="page-item ${pageNumber === activePage ? 'active' : ''}">
                <a class="page-link pagination-btn" href="#" data-page="${pageNumber}">${pageNumber}</a>
            </li>`;
    }
    // --- FUNCIONES AUXILIARES ---

    /**
     * Muestra u oculta el indicador de carga y oculta el estado vacío.
     * @param {boolean} show - True para mostrar, false para ocultar.
     */
    function showLoading(show) {
        if (show) {
            timelineContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                    <p class="mt-2 text-muted">Cargando historial...</p>
                </div>`;
        } else {
        }
    }

    /**
     * Muestra u oculta el mensaje de "sin resultados" y oculta el loader.
     * @param {boolean} show - True para mostrar, false para ocultar.
     */
    function showEmptyState(show) {
        if (show) {
            timelineContainer.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-box-open fa-3x text-muted"></i>
                    <p class="mt-2 text-muted">No se encontraron eventos para los filtros seleccionados.</p>
                </div>`;
            paginationContainer.innerHTML = ''; // Limpiar paginación
        }
    }

    /**
     * Muestra una notificación tipo "toast".
     * @param {string} message - Mensaje a mostrar.
     * @param {string} type - Tipo de notificación (success, error, warning, info).
     */
    function notifi(message, type) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: type,
            title: message,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
});
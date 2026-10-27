/**
 * ============================================================
 * SITGO · DataTable
 * Motor unificado para tablas dinámicas con paginación server-side,
 * búsqueda, filtros, caché y control de estados.
 * ============================================================
 *
 * Uso:
 *   const dt = new DataTable({
 *       tbodyId: 'tbodyPersonal',
 *       endpoint: '/personal/listar',
 *       columns: ['#', 'Nombre', ...],     // solo para colspan
 *       renderRow: (row) => `<tr>...</tr>`,
 *       searchInputId: 'filtroBuscar',     // opcional
 *       limitSelectorId: 'filtroLimit',    // opcional
 *       paginationId: 'paginationX',       // opcional
 *       totalId: 'totalX',                 // opcional
 *       startId: 'startX',                 // opcional
 *       endId: 'endX',                     // opcional
 *       getExtraParams: () => ({...}),     // opcional
 *       onDataLoaded: (res) => {},         // opcional
 *       onError: (err) => {},              // opcional
 *       noDataMessage: 'Sin registros',    // opcional
 *       defaultLimit: 10,
 *       autoLoad: true
 *   });
 *
 *   dt.reload();          // recargar
 *   dt.reload(true);      // recargar forzando bypass de caché
 *   dt.changePage(n);     // ir a página n
 *   dt.getData();         // devuelve las filas actualmente cargadas
 * ============================================================
 */
class DataTable {
    constructor(config) {
        this.tbodyId        = config.tbodyId;
        this.endpoint       = config.endpoint;
        this.renderRow      = config.renderRow;
        this.columns        = config.columns || [];
        this.noDataMessage  = config.noDataMessage || 'No se encontraron registros';
        this.defaultLimit   = config.defaultLimit  || 10;
        this.getExtraParams = config.getExtraParams || null;
        this.onDataLoaded   = config.onDataLoaded   || null;
        this.onError        = config.onError        || null;
        this.onBeforeReq    = config.onBeforeRequest|| null;
        this.autoLoad       = config.autoLoad !== false;
        this.emptyRowClass  = config.emptyRowClass  || 'dt-empty';

        // Referencias DOM
        this.tbody         = document.getElementById(this.tbodyId);
        this.searchInput   = document.getElementById(config.searchInputId);
        this.limitSelector = document.getElementById(config.limitSelectorId);
        this.pagination    = document.getElementById(config.paginationId);
        this.totalDisplay  = document.getElementById(config.totalId);
        this.startDisplay  = document.getElementById(config.startId);
        this.endDisplay    = document.getElementById(config.endId);

        if (!this.tbody) {
            console.warn(`DataTable: no existe #${this.tbodyId}`);
            return;
        }

        // Estado interno
        this.state = {
            page: 1,
            limit: parseInt(this.limitSelector?.value) || this.defaultLimit,
            search: ''
        };

        // Cache + cancelación
        this._abort          = null;
        this._cacheKey       = '';
        this._cacheData      = null;
        this._cacheTime      = 0;
        this._cacheWindow    = 2000;
        this._searchTimer    = null;
        this._totalPages     = 1;
        this._totalFiltered  = 0;
        this._totalAll       = 0;

        // Referencia global (por si se necesita desde HTML: onclick="dt_xxx")
        window[`dt_${this.tbodyId}`] = this;

        this.init();
    }

    /* ============================================================
       INIT · listeners
       ============================================================ */
    init() {
        // Búsqueda
        this.searchInput?.addEventListener('input', (e) => {
            this.state.search = e.target.value.trim();
            this.state.page = 1;
            clearTimeout(this._searchTimer);
            this._searchTimer = setTimeout(() => this.reload(), 350);
        });

        // Límite por página
        this.limitSelector?.addEventListener('change', (e) => {
            this.state.limit = parseInt(e.target.value) || this.defaultLimit;
            this.state.page = 1;
            this.reload();
        });

        // Delegación de eventos de paginación
        this.pagination?.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-page]');
            if (!btn || btn.disabled) return;
            const p = btn.dataset.page;
            if (p === 'prev')  this.changePage(this.state.page - 1);
            else if (p === 'next')  this.changePage(this.state.page + 1);
            else if (p === 'first') this.changePage(1);
            else if (p === 'last')  this.changePage(this._totalPages);
            else this.changePage(parseInt(p));
        });

        if (this.autoLoad) this.reload();
    }

    /* ============================================================
       RELOAD · fetch + cache + abort
       ============================================================ */
    async reload(forceRefresh = false) {
        const offset = (this.state.page - 1) * this.state.limit;
        const extra  = typeof this.getExtraParams === 'function'
            ? (this.getExtraParams() || {})
            : {};

        const params = new URLSearchParams({
            q: this.state.search,
            limit: this.state.limit,
            offset: offset,
            ...extra
        });

        const key = `${this.state.page}|${this.state.limit}|${this.state.search}|${JSON.stringify(extra)}`;

        // Cache
        if (!forceRefresh
            && key === this._cacheKey
            && this._cacheData
            && (Date.now() - this._cacheTime) < this._cacheWindow) {
            this._applyData(this._cacheData);
            return;
        }

        // Cancelar request anterior
        if (this._abort) this._abort.abort();
        this._abort = new AbortController();

        this._showLoading();
        if (this.onBeforeReq) this.onBeforeReq();

        try {
            const r = await fetch(`${this.endpoint}?${params}`, {
                signal: this._abort.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await r.json();

            // Acepta {ok:true} (SITGO) o {success:true} (multiservicio)
            if (data.ok || data.success) {
                this._cacheKey  = key;
                this._cacheData = data;
                this._cacheTime = Date.now();
                this._applyData(data);
                if (this.onDataLoaded) this.onDataLoaded(data);
            } else {
                this._showError(data.message || 'Error al cargar');
                if (this.onError) this.onError(data);
            }
        } catch (err) {
            if (err?.name === 'AbortError') return;
            console.error('DataTable error:', err);
            this._showError('Error de conexión');
            if (this.onError) this.onError(err);
        }
    }

    _applyData(data) {
        const rows = data.data || [];
        this._totalFiltered = data.totalFiltrados ?? data.total ?? rows.length;
        this._totalAll      = data.total ?? rows.length;
        this._totalPages    = Math.max(1, Math.ceil(this._totalFiltered / this.state.limit));

        this.render(rows);
        this._updatePaginationUI();
    }

    /* ============================================================
       RENDER
       ============================================================ */
    render(rows) {
        if (rows.length === 0) {
            const colspan = this.columns.length || 20;
            this.tbody.innerHTML = `<tr><td colspan="${colspan}" class="dt-empty">${this.noDataMessage}</td></tr>`;
        } else {
            this.tbody.innerHTML = rows.map((row, i) => this.renderRow(row, i)).join('');
        }
        if (window.lucide) lucide.createIcons();
    }

    /* ============================================================
       PAGINACIÓN
       ============================================================ */
    _updatePaginationUI() {
        const start = this._totalFiltered === 0 ? 0 : (this.state.page - 1) * this.state.limit + 1;
        const end   = Math.min(this.state.page * this.state.limit, this._totalFiltered);

        if (this.totalDisplay) this.totalDisplay.textContent = this._totalFiltered;
        if (this.startDisplay) this.startDisplay.textContent = start;
        if (this.endDisplay)   this.endDisplay.textContent   = end;

        if (!this.pagination) return;

        const tp = this._totalPages;
        const p  = this.state.page;

        let html = '';

        html += `<button data-page="first" class="dt-page-btn" ${p === 1 ? 'disabled' : ''} title="Primera">
                    <i data-lucide="chevrons-left" class="w-4 h-4"></i>
                 </button>`;

        html += `<button data-page="prev" class="dt-page-btn" ${p === 1 ? 'disabled' : ''} title="Anterior">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                 </button>`;

        this._getPageNumbers(p, tp).forEach(num => {
            if (num === '...') {
                html += `<span class="dt-page-dots">…</span>`;
            } else {
                html += `<button data-page="${num}" class="dt-page-btn ${num === p ? 'active' : ''}">${num}</button>`;
            }
        });

        html += `<button data-page="next" class="dt-page-btn" ${p === tp ? 'disabled' : ''} title="Siguiente">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                 </button>`;

        html += `<button data-page="last" class="dt-page-btn" ${p === tp ? 'disabled' : ''} title="Última">
                    <i data-lucide="chevrons-right" class="w-4 h-4"></i>
                 </button>`;

        this.pagination.innerHTML = html;
        if (window.lucide) lucide.createIcons();
    }

    _getPageNumbers(current, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);

        if (current <= 4) {
            return [1, 2, 3, 4, 5, '...', total];
        }
        if (current >= total - 3) {
            return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        }
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    /* ============================================================
       ESTADOS
       ============================================================ */
    _showLoading() {
        const colspan = this.columns.length || 20;
        this.tbody.innerHTML = `<tr><td colspan="${colspan}" class="dt-loading">Cargando...</td></tr>`;
    }

    _showError(msg) {
        const colspan = this.columns.length || 20;
        this.tbody.innerHTML = `<tr><td colspan="${colspan}" class="dt-error">${msg}</td></tr>`;
    }

    /* ============================================================
       API PÚBLICA
       ============================================================ */
    changePage(p) {
        if (p < 1 || p > (this._totalPages || 1)) return;
        this.state.page = p;
        this.reload();
    }

    refresh()  { this.reload(true); }
    getData()  { return this._cacheData?.data || []; }
}

window.DataTable = DataTable;
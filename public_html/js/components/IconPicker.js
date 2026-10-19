/**
 * ============================================================
 * SITGO · IconPicker
 * Selector visual de iconos Lucide agrupados por categoría.
 * ============================================================
 *
 * Uso:
 *   new IconPicker({
 *       inputId: 'menu_icono',           // input que guarda el nombre del icono
 *       previewId: 'iconPreview',         // opcional: contenedor de preview
 *       onSelect: (name) => {}            // opcional
 *   });
 * ============================================================
 */
class IconPicker{

    /* ============================================================
       CATÁLOGO DE ICONOS POR CATEGORÍA
       ============================================================ */
    static CATEGORIAS = {
        'Navegación': [
            'home','compass','map','map-pin','navigation','route',
            'arrow-left','arrow-right','arrow-up','arrow-down',
            'chevron-left','chevron-right','chevron-up','chevron-down',
            'chevrons-left','chevrons-right','chevrons-up','chevrons-down',
            'move','corner-up-left','corner-up-right','refresh-cw','rotate-cw'
        ],
        'Layout / UI': [
            'layout-dashboard','layout-grid','layout-list','layout-panel-left',
            'sidebar','panel-left','panel-right','panel-top','panel-bottom',
            'menu','list','list-ordered','list-checks','grid','grid-3x3',
            'table','table-2','columns','rows','square','circle','maximize','minimize'
        ],
        'Usuarios / Personas': [
            'user','users','user-plus','user-minus','user-check','user-x',
            'user-cog','user-circle','contact','contact-round','id-card',
            'shield-user','shield','shield-check','shield-alert','crown'
        ],
        'Comercio': [
            'shopping-cart','shopping-bag','store','receipt','receipt-text',
            'credit-card','banknote','wallet','dollar-sign','euro','coins',
            'percent','tag','tags','barcode','qr-code','package','package-check',
            'package-x','package-plus','truck','container'
        ],
        'Archivos / Documentos': [
            'file','file-text','files','folder','folder-open','folder-plus',
            'folder-tree','archive','book','book-open','bookmark','clipboard',
            'clipboard-list','clipboard-check','notebook-pen','paperclip',
            'printer','download','upload','save','hard-drive'
        ],
        'Transporte': [
            'bus','car','truck','bike','plane','ship','train-front','tractor',
            'fuel','gauge','gauge-circle','wrench','settings','settings-2',
            'cog','cogs','steering-wheel','route','map-pinned','milestone'
        ],
        'Comunicación': [
            'mail','mail-open','send','phone','phone-call','message-circle',
            'message-square','bell','bell-ring','bell-off','megaphone',
            'inbox','at-sign','rss','share','share-2','link','link-2','unlink'
        ],
        'Tiempo / Fechas': [
            'calendar','calendar-days','calendar-check','calendar-x','calendar-plus',
            'clock','clock-4','timer','hourglass','alarm-clock','history','watch'
        ],
        'Datos / Analíticas': [
            'bar-chart','bar-chart-2','bar-chart-3','bar-chart-4',
            'line-chart','pie-chart','area-chart','trending-up','trending-down',
            'activity','database','server','server-cog','cloud','cloud-upload',
            'cloud-download','hard-drive-download','chart-pie','chart-line'
        ],
        'Sistema': [
            'settings','settings-2','sliders','sliders-horizontal','wrench',
            'hammer','cog','cpu','terminal','code','code-2','bug','shield-check',
            'lock','unlock','key','key-round','fingerprint','scan'
        ],
        'Edición': [
            'pencil','pencil-line','pen','pen-tool','edit','edit-2','edit-3',
            'trash','trash-2','copy','clipboard-paste','scissors','eraser',
            'plus','plus-circle','minus','minus-circle','x','x-circle','check',
            'check-circle','check-check','undo','redo'
        ],
        'Alertas / Estados': [
            'alert-circle','alert-triangle','alert-octagon','info','help-circle',
            'ban','circle-slash','eye','eye-off','search','search-x','zoom-in',
            'zoom-out','filter','filter-x','star','star-off','heart','heart-off',
            'flag','flag-triangle-right','thumbs-up','thumbs-down'
        ],
        'Media': [
            'image','image-plus','images','camera','video','film','music',
            'headphones','speaker','play','pause','stop-circle','volume-2',
            'volume-x','mic','mic-off','radio','tv','monitor'
        ],
        'Otros': [
            'building','building-2','hotel','warehouse','factory','school',
            'briefcase','award','target','rocket','zap','sun','moon','cloud-sun',
            'leaf','flame','droplet','droplets','wind','snowflake','thermometer',
            'beaker','atom','globe','globe-2','earth','lightbulb','plug','battery'
        ]
    };

    constructor(config){
        this.inputId    = config.inputId;
        this.previewId  = config.previewId || null;
        this.onSelect   = config.onSelect  || null;
        this.input      = document.getElementById(this.inputId);
        this.preview    = this.previewId ? document.getElementById(this.previewId) : null;

        if (!this.input){
            console.warn('IconPicker: input no encontrado:', this.inputId);
            return;
        }

        this._renderPreview();
        this._buildDOM();
        this._bindEvents();
        this._syncFromInput();
    }

    /* ============================================================
       PREVIEW EN EL WRAPPER
       ============================================================ */
    _renderPreview(){
        // Si ya existe un previewId externo (como en menu.php), lo usamos
        // Si no, lo creamos dentro del wrapper del input
        if (!this.preview){
            const wrapper = this.input.closest('.icon-input-wrap');
            if (wrapper){
                this.preview = wrapper.querySelector('.icon-preview');
            }
        }
    }

    /* ============================================================
       DOM DEL DROPDOWN
       ============================================================ */
    _buildDOM(){
        const wrapper = this.input.closest('.icon-input-wrap') || this.input.parentElement;
        wrapper.classList.add('icon-picker-wrap');

        // Botón dropdown
        this.trigger = document.createElement('button');
        this.trigger.type = 'button';
        this.trigger.className = 'icon-picker-trigger';
        this.trigger.title = 'Ver catálogo';
        this.trigger.innerHTML = '<i data-lucide="layout-grid" class="w-4 h-4"></i>';
        wrapper.appendChild(this.trigger);

        // Panel
        this.panel = document.createElement('div');
        this.panel.className = 'icon-picker-panel';
        this.panel.innerHTML = `
            <div class="icon-picker-head">
                <div class="icon-picker-search">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    <input type="text" placeholder="Buscar icono..." id="ipSearch_${this.inputId}">
                </div>
                <button type="button" class="icon-picker-close">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div class="icon-picker-body" id="ipBody_${this.inputId}"></div>
            <div class="icon-picker-foot">
                <span class="icon-picker-selected">
                    <span class="ip-selected-preview"></span>
                    <code class="ip-selected-name">—</code>
                </span>
                <button type="button" class="icon-picker-clear">
                    <i data-lucide="eraser" class="w-4 h-4"></i>
                    <span>Limpiar</span>
                </button>
            </div>
        `;
        wrapper.appendChild(this.panel);

        this.searchInput  = this.panel.querySelector(`#ipSearch_${this.inputId}`);
        this.body         = this.panel.querySelector(`#ipBody_${this.inputId}`);
        this.selectedPrev = this.panel.querySelector('.ip-selected-preview');
        this.selectedName = this.panel.querySelector('.ip-selected-name');

        if (window.lucide) lucide.createIcons();

        this._renderGrid();
    }

    /* ============================================================
       RENDER DEL GRID
       ============================================================ */
    _renderGrid(query = ''){
        const q = query.toLowerCase().trim();
        let html = '';
        let totalCount = 0;

        Object.entries(IconPicker.CATEGORIAS).forEach(([cat, icons]) => {
            const filtered = q
                ? icons.filter(i => i.includes(q))
                : icons;

            if (!filtered.length) return;

            totalCount += filtered.length;
            html += `
                <div class="ip-cat">
                    <div class="ip-cat-title">${cat}</div>
                    <div class="ip-grid">
                        ${filtered.map(name => `
                            <button type="button"
                                    class="ip-item ${name === this.input.value ? 'active' : ''}"
                                    data-icon="${name}"
                                    title="${name}">
                                <i data-lucide="${name}" class="w-5 h-5"></i>
                                <span class="ip-item-name">${name}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            `;
        });

        if (!totalCount){
            html = `<div class="ip-empty">
                <i data-lucide="search-x" class="w-10 h-10 mx-auto" style="color:#cbd5e1;"></i>
                <p>Sin coincidencias para "<strong>${this._esc(query)}</strong>"</p>
            </div>`;
        }

        this.body.innerHTML = html;
        if (window.lucide) lucide.createIcons();

        // Bind de clics
        this.body.querySelectorAll('.ip-item').forEach(btn => {
            btn.addEventListener('click', () => {
                const name = btn.dataset.icon;
                this._select(name);
            });
        });
    }

    /* ============================================================
       EVENTOS
       ============================================================ */
    _bindEvents(){
        // Abrir/cerrar panel
        this.trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            this._toggle();
        });

        // Cerrar con botón
        this.panel.querySelector('.icon-picker-close').addEventListener('click', () => this._close());

        // Limpiar
        this.panel.querySelector('.icon-picker-clear').addEventListener('click', () => {
            this._select('');
            this._close();
        });

        // Búsqueda
        this.searchInput.addEventListener('input', (e) => {
            this._renderGrid(e.target.value);
        });

        // Click fuera
        document.addEventListener('click', (e) => {
            if (!this.panel.parentElement.contains(e.target)) this._close();
        });

        // Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.panel.classList.contains('open')) this._close();
        });

        // Escribir manualmente en el input
        this.input.addEventListener('input', () => {
            this._syncFromInput();
        });

        this.input.addEventListener('click', () => this._open());
    }

    _toggle(){
        this.panel.classList.contains('open') ? this._close() : this._open();
    }

    _open(){
        this.panel.classList.add('open');
        this.searchInput.value = '';
        this._renderGrid();
        this._posicionarPanel();

        // Reposicionar si se hace scroll o se redimensiona mientras está abierto
        this._onScroll = () => this._posicionarPanel();
        this._onResize = () => this._posicionarPanel();
        window.addEventListener('scroll', this._onScroll, true);
        window.addEventListener('resize', this._onResize);

        setTimeout(() => this.searchInput.focus(), 50);
    }

    /**
     * Calcula posición y ancho del panel según el input/wrapper.
     * Se posiciona fixed para escapar de ancestros con overflow: hidden.
     */
    _posicionarPanel(){
        const wrapper = this.input.closest('.icon-input-wrap') || this.input.parentElement;
        if (!wrapper) return;

        const rect = wrapper.getBoundingClientRect();
        const panelWidth = Math.max(rect.width, 360);
        const viewportW = window.innerWidth;
        const viewportH = window.innerHeight;

        // Centrar horizontalmente si cabe, si no ajustar al viewport
        let left = rect.left;
        if (left + panelWidth > viewportW - 12){
            left = viewportW - panelWidth - 12;
        }
        if (left < 12) left = 12;

        // Altura estimada del panel (máx 480px, min 260px)
        const maxPanelH = Math.min(480, viewportH - 40);
        const spaceBelow = viewportH - rect.bottom - 12;
        const spaceAbove = rect.top - 12;

        // Si no cabe abajo pero sí arriba → mostrar arriba
        let top;
        if (spaceBelow >= 260){
            top = rect.bottom + 6;
        } else if (spaceAbove >= 260){
            top = rect.top - maxPanelH - 6;
            if (top < 12) top = 12;
        } else {
            top = rect.bottom + 6;
        }

        // Si aún desborda por abajo, ajustar altura máxima
        const remaining = viewportH - top - 12;
        const panelH = Math.min(maxPanelH, Math.max(200, remaining));

        this.panel.style.left    = left + 'px';
        this.panel.style.top     = top + 'px';
        this.panel.style.width   = panelWidth + 'px';
        this.panel.style.maxHeight = panelH + 'px';
    }

    _close(){
        this.panel.classList.remove('open');
        if (this._onScroll) window.removeEventListener('scroll', this._onScroll, true);
        if (this._onResize) window.removeEventListener('resize', this._onResize);
    }

    /* ============================================================
       SELECCIÓN
       ============================================================ */
    _select(name){
        this.input.value = name || '';
        this.input.dispatchEvent(new Event('input', { bubbles: true }));
        this._syncFromInput();

        if (typeof this.onSelect === 'function'){
            this.onSelect(name);
        }
    }

    _syncFromInput(){
        const name = (this.input.value || '').trim();

        // Preview
        if (this.preview){
            this.preview.innerHTML = name
                ? `<i data-lucide="${this._esc(name)}" class="w-4 h-4"></i>`
                : `<i data-lucide="circle" class="w-4 h-4"></i>`;
        }

        // Preview en el footer del panel
        if (this.selectedPrev){
            this.selectedPrev.innerHTML = name
                ? `<i data-lucide="${this._esc(name)}" class="w-4 h-4"></i>`
                : '—';
        }
        if (this.selectedName){
            this.selectedName.textContent = name || '—';
        }

        // Marcar item activo en la grid
        this.body?.querySelectorAll('.ip-item').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.icon === name);
        });

        if (window.lucide) lucide.createIcons();
    }

    _esc(s){
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ============================================================
       API PÚBLICA
       ============================================================ */
    setValue(name){ this._select(name); }
    getValue(){ return this.input.value; }
}

window.IconPicker = IconPicker;
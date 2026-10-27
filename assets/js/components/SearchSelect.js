/**
 * ============================================================
 * SITGO · SearchSelect
 * Select con buscador integrado, reutilizable en todo el sistema.
 * ============================================================
 *
 * Uso básico:
 *   const ss = new SearchSelect({
 *       wrapperId: 'wrapPersonal',       // contenedor
 *       hiddenInputId: 'usuario_id_personal', // input hidden que guarda el value
 *       placeholder: 'Seleccione un personal...',
 *       onChange: (value, item) => { ... }
 *   });
 *   ss.setOptions([
 *       { value: 1, label: 'PÉREZ JUAN (V-12345678)', search: 'juan perez v-12345678' },
 *       ...
 *   ]);
 *
 *   ss.getValue()      // value actual
 *   ss.setValue(5)     // selecciona el value 5 (si existe)
 *   ss.clear()         // limpia
 *   ss.setOptions([])  // recarga opciones
 * ============================================================
 */
class SearchSelect {
    constructor(config){
        this.wrapperId     = config.wrapperId;
        this.hiddenInputId = config.hiddenInputId;
        this.placeholder   = config.placeholder || 'Seleccione...';
        this.onChange      = config.onChange || null;
        this.noResultsText = config.noResultsText || 'Sin resultados';
        this.options       = [];
        this.filtered      = [];
        this.activeIndex   = -1;
        this.selectedValue = '';

        this.wrapper = document.getElementById(this.wrapperId);
        this.hidden  = document.getElementById(this.hiddenInputId);

        if (!this.wrapper || !this.hidden){
            console.warn('SearchSelect: faltan elementos', config);
            return;
        }

        this._buildDOM();
        this._bindEvents();
        this._renderEmptyState();
    }

    /**
     * Construye el HTML interno del componente.
     */
    _buildDOM(){
        this.wrapper.classList.add('ss');

        // Input de búsqueda (visible)
        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.className = 'ss-input';
        this.input.placeholder = this.placeholder;
        this.input.autocomplete = 'off';
        this.input.readOnly = false;

        // Ícono chevron
        this.arrow = document.createElement('span');
        this.arrow.className = 'ss-arrow';
        this.arrow.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>';

        // Dropdown
        this.dropdown = document.createElement('div');
        this.dropdown.className = 'ss-dropdown';
        this.dropdown.style.display = 'none';

        // Lista
        this.list = document.createElement('ul');
        this.list.className = 'ss-list';

        this.dropdown.appendChild(this.list);

        // Ensamblado
        this.wrapper.appendChild(this.input);
        this.wrapper.appendChild(this.arrow);
        this.wrapper.appendChild(this.dropdown);
        this.wrapper.appendChild(this.hidden);
    }

    /**
     * Listeners de apertura, búsqueda, teclado y clics.
     */
    _bindEvents(){
        // Abrir al hacer foco o clic
        this.input.addEventListener('focus', () => this._open());
        this.input.addEventListener('click', () => this._open());

        // Buscar mientras se escribe
        this.input.addEventListener('input', (e) => {
            this._filter(e.target.value);
            this._open();
        });

        // Teclado
        this.input.addEventListener('keydown', (e) => this._onKey(e));

        // Click fuera → cerrar
        document.addEventListener('click', (e) => {
            if (!this.wrapper.contains(e.target)) this._close();
        });

        // Click en opciones (delegación)
        this.list.addEventListener('click', (e) => {
            const li = e.target.closest('[data-value]');
            if (!li) return;
            this.setValue(li.dataset.value);
            this._close();
            this.input.blur();
        });
    }

    /**
     * Estado inicial (sin opciones).
     */
    _renderEmptyState(){
        this.list.innerHTML = `<li class="ss-empty">${this.placeholder}</li>`;
    }

    /**
     * Asigna las opciones del select.
     * items: [{ value, label, search? }]
     */
    setOptions(items){
        this.options = (items || []).map(it => ({
            value: String(it.value),
            label: it.label,
            search: (it.search || it.label || '').toLowerCase()
        }));
        this._filter('');

        // Si había valor seleccionado, mantener si sigue existiendo
        if (this.selectedValue){
            const found = this.options.find(o => o.value === String(this.selectedValue));
            if (found){
                this.input.value = found.label;
            } else {
                this.clear();
            }
        }
    }

    /**
     * Aplica filtro por texto (busca en label + search).
     */
    _filter(query){
        const q = (query || '').toLowerCase().trim();
        this.filtered = q
            ? this.options.filter(o => o.search.includes(q))
            : this.options.slice();
        this.activeIndex = -1;
        this._renderList();
    }

    /**
     * Dibuja la lista filtrada.
     */
    _renderList(){
        if (!this.filtered.length){
            this.list.innerHTML = `<li class="ss-empty">${this.noResultsText}</li>`;
            return;
        }
        this.list.innerHTML = this.filtered.map((o, i) => `
            <li class="ss-option ${String(o.value) === String(this.selectedValue) ? 'selected' : ''}"
                data-value="${o.value}"
                data-index="${i}">
                ${this._escape(o.label)}
            </li>
        `).join('');
    }

    /**
     * Abre el dropdown. Si ya hay valor seleccionado, limpia el input
     * para que el usuario pueda buscar de nuevo.
     */
    _open(){
        if (this.dropdown.style.display === 'block') return;
        // Si hay un valor seleccionado, guardamos su label y limpiamos input
        if (this.selectedValue){
            const cur = this.options.find(o => o.value === String(this.selectedValue));
            this._lastLabel = cur ? cur.label : '';
            this.input.value = '';
        }
        this._filter('');
        this.dropdown.style.display = 'block';
        this.wrapper.classList.add('ss-open');
    }

    /**
     * Cierra el dropdown. Restaura la etiqueta del valor seleccionado.
     */
    _close(){
        this.dropdown.style.display = 'none';
        this.wrapper.classList.remove('ss-open');
        if (this.selectedValue){
            const cur = this.options.find(o => o.value === String(this.selectedValue));
            this.input.value = cur ? cur.label : '';
        } else {
            this.input.value = '';
        }
    }

    /**
     * Manejo de teclado: ↑ ↓ Enter Escape.
     */
    _onKey(e){
        if (e.key === 'ArrowDown'){
            e.preventDefault();
            this.activeIndex = Math.min(this.activeIndex + 1, this.filtered.length - 1);
            this._highlight();
        } else if (e.key === 'ArrowUp'){
            e.preventDefault();
            this.activeIndex = Math.max(this.activeIndex - 1, 0);
            this._highlight();
        } else if (e.key === 'Enter'){
            e.preventDefault();
            if (this.activeIndex >= 0 && this.filtered[this.activeIndex]){
                this.setValue(this.filtered[this.activeIndex].value);
                this._close();
                this.input.blur();
            }
        } else if (e.key === 'Escape'){
            this._close();
            this.input.blur();
        }
    }

    /**
     * Resalta la opción activa por teclado.
     */
    _highlight(){
        this.list.querySelectorAll('.ss-option').forEach((li, i) => {
            li.classList.toggle('active', i === this.activeIndex);
            if (i === this.activeIndex) li.scrollIntoView({ block: 'nearest' });
        });
    }

    /**
     * Selecciona un valor y dispara onChange.
     */
    setValue(value){
        value = value == null ? '' : String(value);
        this.selectedValue = value;
        this.hidden.value = value;

        const cur = this.options.find(o => o.value === value);
        this.input.value = cur ? cur.label : '';

        if (this.onChange) this.onChange(value, cur || null);
    }

    /**
     * Devuelve el value actual.
     */
    getValue(){
        return this.hidden.value || '';
    }

    /**
     * Limpia la selección.
     */
    clear(){
        this.selectedValue = '';
        this.hidden.value = '';
        this.input.value = '';
        if (this.onChange) this.onChange('', null);
    }

    /**
     * Escapa HTML para evitar inyecciones en labels.
     */
    _escape(s){
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

        /**
     * Devuelve el label del valor seleccionado (o '' si no hay).
     */
    getSelectedLabel(){
        if (!this.selectedValue) return '';
        const cur = this.options.find(o => o.value === String(this.selectedValue));
        return cur ? cur.label : '';
    }
}

window.SearchSelect = SearchSelect;
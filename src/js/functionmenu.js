// ==============================================
// VARIABLES GLOBALES
// ==============================================
let currentUserId = null;
let userPermissions = [];
let allMenusTree = [];
let allMenusPlano = [];
let currentEditingMenuId = null;

// Variables para el selector de iconos
let iconCatalog = null;
let iconPickerInitialized = false;
let iconPickerCategory = null;

// ==============================================
// INICIALIZACIÓN
// ==============================================
document.addEventListener('DOMContentLoaded', function () {
    initializeApp();
});

function initializeApp() {
    loadUsers();

    document.getElementById('select-user').addEventListener('change', handleUserSelect);
    document.getElementById('btn-save-permissions').addEventListener('click', savePermissions);
    document.getElementById('btn-reset-permissions').addEventListener('click', resetPermissions);
    document.getElementById('btn-expand-all').addEventListener('click', () => toggleAllPermissionNodes(true));
    document.getElementById('btn-collapse-all').addEventListener('click', () => toggleAllPermissionNodes(false));

    document.getElementById('btn-crear-menu').addEventListener('click', () => openCreateMenuModal());
    document.getElementById('btn-cancelar-menu').addEventListener('click', closeMenuModal);
    document.getElementById('close-menu-modal').addEventListener('click', closeMenuModal);
    document.getElementById('form-menu').addEventListener('submit', handleMenuSubmit);
    document.getElementById('btn-add-ruta').addEventListener('click', () => addRutaInput(''));

    document.getElementById('menu-modal').addEventListener('click', function (e) {
        if (e.target === this) closeMenuModal();
    });

    // Inicializar selector de iconos
    initIconPicker();

    loadMenus();
}

// ==============================================
// UTILIDADES
// ==============================================
function showLoading(show) {
    const el = document.getElementById('loading');
    if (!el) return;
    if (show) el.classList.remove('hidden');
    else el.classList.add('hidden');
}

function showMessage(message, type) {
    const el = document.getElementById('message');
    if (!el) return;
    el.textContent = message;
    el.className = `alert alert-${type}`;
    el.classList.remove('hidden');
    setTimeout(() => el.classList.add('hidden'), 5000);
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ==============================================
// CARGA DE USUARIOS
// ==============================================
async function loadUsers() {
    try {
        showLoading(true);
        const response = await fetch(`${base_url}Menu/cargar_usuarios`);
        const data = await response.json();
        if (data.status) {
            populateUserDropdown(data.data);
        } else {
            showMessage('Error al cargar usuarios: ' + data.msg, 'error');
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

function populateUserDropdown(users) {
    const select = document.getElementById('select-user');
    select.innerHTML = '<option value="">-- Seleccione un usuario --</option>';
    users.forEach(user => {
        const option = document.createElement('option');
        option.value = user.usuario_id;
        option.textContent = `${user.usuario_nick} (${user.personal_nombre} ${user.personal_apellido}) - ${user.rol_nombre}`;
        select.appendChild(option);
    });
}

// ==============================================
// PERMISOS DE USUARIO
// ==============================================
async function handleUserSelect(event) {
    const userId = event.target.value;
    if (!userId) {
        document.getElementById('user-permissions').classList.add('hidden');
        return;
    }
    currentUserId = userId;

    try {
        showLoading(true);

        const userInfoRes = await fetch(`${base_url}Menu/get_user_info/${userId}`);
        const userInfoData = await userInfoRes.json();
        if (userInfoData.status) {
            document.getElementById('selected-username').textContent =
                `${userInfoData.data.personal_nombre} ${userInfoData.data.personal_apellido}`;
        }

        if (allMenusTree.length === 0) {
            const menusRes = await fetch(`${base_url}Menu/get_all_menus`);
            const menusData = await menusRes.json();
            if (menusData.status) {
                allMenusTree = menusData.data;
            } else {
                showMessage('Error al cargar menús: ' + menusData.msg, 'error');
                return;
            }
        }

        const permsRes = await fetch(`${base_url}Menu/get_user_permissions/${userId}`);
        const permsData = await permsRes.json();
        if (permsData.status) {
            userPermissions = permsData.data.map(id => parseInt(id, 10));
        } else {
            userPermissions = [];
        }

        renderPermissions();
        document.getElementById('user-permissions').classList.remove('hidden');

    } catch (error) {
        showMessage('Error al cargar información: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

function renderPermissions() {
    const container = document.getElementById('permissions-container');
    container.innerHTML = '';
    if (!allMenusTree || allMenusTree.length === 0) {
        container.innerHTML = '<div style="text-align:center;color:#888;padding:1rem;">No hay menús disponibles</div>';
        return;
    }
    container.appendChild(buildPermissionTree(allMenusTree, 0));
}

function buildPermissionTree(nodes, level) {
    const wrapper = document.createElement('div');
    wrapper.className = 'permission-node';

    nodes.forEach(node => {
        const tieneHijos = node.hijos && node.hijos.length > 0;
        const isChecked = userPermissions.includes(parseInt(node.menu_id, 10));

        const nodeDiv = document.createElement('div');

        const header = document.createElement('div');
        header.className = 'node-header';

        if (tieneHijos) {
            const toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.className = 'toggle-hijos';
            toggle.innerHTML = '<i class="fas fa-chevron-right"></i>';
            toggle.dataset.expanded = 'false';
            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                const isExpanded = this.dataset.expanded === 'true';
                this.dataset.expanded = isExpanded ? 'false' : 'true';
                this.innerHTML = isExpanded
                    ? '<i class="fas fa-chevron-right"></i>'
                    : '<i class="fas fa-chevron-down"></i>';
                const childrenDiv = nodeDiv.querySelector('.node-children');
                if (childrenDiv) {
                    childrenDiv.style.display = isExpanded ? 'none' : 'block';
                }
            });
            header.appendChild(toggle);
        } else {
            const spacer = document.createElement('span');
            spacer.style.display = 'inline-block';
            spacer.style.width = '20px';
            header.appendChild(spacer);
        }

        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.id = `perm-menu-${node.menu_id}`;
        checkbox.checked = isChecked;
        checkbox.dataset.menuId = node.menu_id;
        checkbox.addEventListener('change', function () {
            handleMenuCheckChange(node, this.checked);
        });
        header.appendChild(checkbox);

        const label = document.createElement('label');
        label.htmlFor = `perm-menu-${node.menu_id}`;
        label.innerHTML = `
            <i class="${escapeHtml(node.menu_icono || 'far fa-circle')} fa-icon"></i>
            <span>${escapeHtml(node.menu_nombre)}</span>
            ${node.menu_scope && node.menu_scope !== 'general'
                ? `<span class="scope-badge">${escapeHtml(node.menu_scope)}</span>`
                : ''}
        `;
        header.appendChild(label);

        nodeDiv.appendChild(header);

        if (tieneHijos) {
            const childrenDiv = document.createElement('div');
            childrenDiv.className = 'node-children';
            childrenDiv.style.display = 'none';
            childrenDiv.appendChild(buildPermissionTree(node.hijos, level + 1));
            nodeDiv.appendChild(childrenDiv);
        }

        wrapper.appendChild(nodeDiv);
    });

    return wrapper;
}

function handleMenuCheckChange(node, checked) {
    if (node.hijos && node.hijos.length > 0) {
        node.hijos.forEach(hijo => {
            const hijoCheckbox = document.getElementById(`perm-menu-${hijo.menu_id}`);
            if (hijoCheckbox) {
                hijoCheckbox.checked = checked;
                handleMenuCheckChange(hijo, checked);
            }
        });
    }

    if (!checked) {
        updateAncestors(node.menu_id);
    }
}

function updateAncestors(menuId) {
    const parent = findParentInTree(allMenusTree, menuId);
    if (!parent) return;

    const parentCheckbox = document.getElementById(`perm-menu-${parent.menu_id}`);
    if (!parentCheckbox) return;

    const anyChildChecked = (parent.hijos || []).some(hijo => {
        const cb = document.getElementById(`perm-menu-${hijo.menu_id}`);
        return cb && cb.checked;
    });

    parentCheckbox.checked = anyChildChecked;
    updateAncestors(parent.menu_id);
}

function findParentInTree(tree, childId) {
    for (const node of tree) {
        if (node.hijos && node.hijos.length > 0) {
            if (node.hijos.some(h => parseInt(h.menu_id, 10) === parseInt(childId, 10))) {
                return node;
            }
            const found = findParentInTree(node.hijos, childId);
            if (found) return found;
        }
    }
    return null;
}

function toggleAllPermissionNodes(expand) {
    document.querySelectorAll('#permissions-container .node-children').forEach(div => {
        div.style.display = expand ? 'block' : 'none';
    });
    document.querySelectorAll('#permissions-container .toggle-hijos').forEach(btn => {
        btn.dataset.expanded = expand ? 'true' : 'false';
        btn.innerHTML = expand
            ? '<i class="fas fa-chevron-down"></i>'
            : '<i class="fas fa-chevron-right"></i>';
    });
}

async function savePermissions() {
    if (!currentUserId) {
        showMessage('Por favor, seleccione un usuario primero', 'error');
        return;
    }

    try {
        showLoading(true);

        const selectedPermissions = [];
        document.querySelectorAll('#permissions-container input[type="checkbox"]:checked').forEach(cb => {
            selectedPermissions.push(parseInt(cb.dataset.menuId, 10));
        });

        const response = await fetch(`${base_url}Menu/update_user_permissions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: currentUserId,
                permissions: selectedPermissions
            })
        });

        const data = await response.json();
        if (data.status) {
            showMessage('Permisos actualizados correctamente', 'success');
            userPermissions = selectedPermissions;
        } else {
            showMessage('Error al actualizar permisos: ' + data.msg, 'error');
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

async function resetPermissions() {
    if (!currentUserId) return;
    if (!confirm('¿Restablecer los permisos del usuario a su estado guardado?')) return;

    try {
        showLoading(true);
        const response = await fetch(`${base_url}Menu/get_user_permissions/${currentUserId}`);
        const data = await response.json();
        if (data.status) {
            userPermissions = data.data.map(id => parseInt(id, 10));
            renderPermissions();
            showMessage('Permisos restablecidos', 'success');
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// ==============================================
// GESTIÓN DE MENÚS (CRUD)
// ==============================================
async function loadMenus() {
    try {
        showLoading(true);

        const treeRes = await fetch(`${base_url}Menu/listar_menus`);
        const treeData = await treeRes.json();
        if (treeData.status) {
            allMenusTree = treeData.data;
            renderMenusList();
        } else {
            showMessage('Error al cargar menús: ' + treeData.msg, 'error');
        }

        const planoRes = await fetch(`${base_url}Menu/listar_menus_plano`);
        const planoData = await planoRes.json();
        if (planoData.status) {
            allMenusPlano = planoData.data;
        }

    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

function renderMenusList() {
    const container = document.getElementById('menus-list-container');
    container.innerHTML = '';

    if (!allMenusTree || allMenusTree.length === 0) {
        container.innerHTML = '<div style="text-align:center;color:#888;padding:1rem;">No hay menús creados</div>';
        return;
    }

    const grid = document.createElement('div');
    grid.className = 'menus-grid';

    allMenusTree.forEach(menu => {
        grid.appendChild(buildMenuCard(menu));
    });

    container.appendChild(grid);
}

function buildMenuCard(menu) {
    const card = document.createElement('div');
    card.className = 'menu-card';

    const tieneHijos = menu.hijos && menu.hijos.length > 0;

    let html = `
        <div class="menu-card-header">
            <i class="${escapeHtml(menu.menu_icono || 'far fa-circle')}"></i>
            <h4>${escapeHtml(menu.menu_nombre)}</h4>
            <span class="menu-badge">${tieneHijos ? 'Con hijos' : 'Simple'}</span>
            ${menu.menu_scope && menu.menu_scope !== 'general'
                ? `<span class="menu-badge badge-scope">${escapeHtml(menu.menu_scope)}</span>`
                : ''}
        </div>
        <div class="menu-card-body">
            <div class="menu-info">
                <div class="info-item">
                    <span class="label">Ruta:</span>
                    <span class="value">${escapeHtml(menu.menu_ruta || 'N/A')}</span>
                </div>
                <div class="info-item">
                    <span class="label">Orden:</span>
                    <span class="value">${menu.menu_orden}</span>
                </div>
            </div>
    `;

    if (menu.rutas && menu.rutas.length > 0) {
        html += `
            <div class="rutas-section">
                <h5>Patrones de activación:</h5>
                <div class="rutas-list">
                    ${menu.rutas.map(r => `
                        <div class="ruta-item">
                            <span class="patron">${escapeHtml(r.patron)}</span>
                            <button class="btn-icon delete-ruta" data-ruta-id="${r.ruta_id}" title="Eliminar ruta">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    if (tieneHijos) {
        html += `
            <div class="hijos-section">
                <h5>Submenús (${menu.hijos.length}):</h5>
                ${menu.hijos.map(hijo => `
                    <div class="hijo-item">
                        <div class="hijo-info">
                            <i class="${escapeHtml(hijo.menu_icono || 'far fa-circle')}"></i>
                            <span>${escapeHtml(hijo.menu_nombre)}</span>
                            <small style="color:#888;margin-left:0.3rem;">${escapeHtml(hijo.menu_ruta || '')}</small>
                        </div>
                        <div>
                            <button class="btn-icon edit-menu" data-menu-id="${hijo.menu_id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-icon delete-menu" data-menu-id="${hijo.menu_id}" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }

    html += `
        </div>
        <div class="menu-card-actions">
            <button class="btn btn-primary btn-sm edit-menu" data-menu-id="${menu.menu_id}">
                <i class="fas fa-edit"></i> Editar
            </button>
            <button class="btn btn-secondary btn-sm add-child" data-menu-id="${menu.menu_id}">
                <i class="fas fa-plus"></i> Añadir hijo
            </button>
            <button class="btn btn-danger btn-sm delete-menu" data-menu-id="${menu.menu_id}">
                <i class="fas fa-trash"></i> Eliminar
            </button>
        </div>
    `;

    card.innerHTML = html;

    card.querySelectorAll('.edit-menu').forEach(btn => {
        btn.addEventListener('click', () => openEditMenuModal(btn.dataset.menuId));
    });
    card.querySelectorAll('.delete-menu').forEach(btn => {
        btn.addEventListener('click', () => deleteMenu(btn.dataset.menuId));
    });
    card.querySelectorAll('.add-child').forEach(btn => {
        btn.addEventListener('click', () => openCreateMenuModal(btn.dataset.menuId));
    });
    card.querySelectorAll('.delete-ruta').forEach(btn => {
        btn.addEventListener('click', () => deleteRuta(btn.dataset.rutaId));
    });

    return card;
}

// ==============================================
// MODAL: CREAR / EDITAR MENÚ
// ==============================================
function openCreateMenuModal(padreId = null) {
    currentEditingMenuId = null;
    document.getElementById('menu-modal-title').textContent = padreId ? 'Nuevo Submenú' : 'Nuevo Menú';
    document.getElementById('form-menu').reset();
    document.getElementById('menu_id').value = '';
    document.getElementById('menu_scope').value = 'general';
    document.getElementById('menu_orden').value = 0;
    document.getElementById('rutas-container').innerHTML = '';

    // Resetear icono a valor por defecto
    setIconValueSilent('fas fa-home');

    populatePadreSelect(padreId);
    addRutaInput('');

    document.getElementById('menu-modal').classList.remove('hidden');
}

async function openEditMenuModal(menuId) {
    try {
        showLoading(true);
        const response = await fetch(`${base_url}Menu/get_menu/${menuId}`);
        const data = await response.json();

        if (!data.status) {
            showMessage('Error al cargar menú: ' + data.msg, 'error');
            return;
        }

        const menu = data.data;
        currentEditingMenuId = menu.menu_id;

        document.getElementById('menu-modal-title').textContent = 'Editar Menú';
        document.getElementById('menu_id').value = menu.menu_id;
        document.getElementById('menu_nombre').value = menu.menu_nombre || '';
        document.getElementById('menu_orden').value = menu.menu_orden || 0;
        document.getElementById('menu_ruta').value = menu.menu_ruta || '';
        document.getElementById('menu_scope').value = menu.menu_scope || 'general';

        // Establecer icono
        setIconValueSilent(menu.menu_icono || 'fas fa-home');

        populatePadreSelect(menu.menu_padre_id, menu.menu_id);

        document.getElementById('rutas-container').innerHTML = '';
        if (menu.rutas && menu.rutas.length > 0) {
            menu.rutas.forEach(r => addRutaInput(r.patron));
        } else {
            addRutaInput('');
        }

        document.getElementById('menu-modal').classList.remove('hidden');

    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

function populatePadreSelect(selectedId = null, excludeId = null) {
    const select = document.getElementById('menu_padre_id');
    select.innerHTML = '<option value="">— Ninguno (es raíz) —</option>';

    function walk(nodes, level) {
        nodes.forEach(node => {
            if (excludeId && parseInt(node.menu_id, 10) === parseInt(excludeId, 10)) return;

            const option = document.createElement('option');
            option.value = node.menu_id;
            option.textContent = '— '.repeat(level) + node.menu_nombre;
            if (selectedId && parseInt(selectedId, 10) === parseInt(node.menu_id, 10)) {
                option.selected = true;
            }
            select.appendChild(option);

            if (node.hijos && node.hijos.length > 0) {
                walk(node.hijos, level + 1);
            }
        });
    }
    walk(allMenusTree, 0);
}

function closeMenuModal() {
    document.getElementById('menu-modal').classList.add('hidden');
    currentEditingMenuId = null;
}

function addRutaInput(valor = '') {
    const container = document.getElementById('rutas-container');
    const row = document.createElement('div');
    row.className = 'ruta-input-row';
    row.innerHTML = `
        <input type="text" class="input-ruta-patron" placeholder="ej: flota/taller/*" value="${escapeHtml(valor)}">
        <button type="button" class="btn-remove-ruta" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    `;
    row.querySelector('.btn-remove-ruta').addEventListener('click', () => row.remove());
    container.appendChild(row);
}

async function handleMenuSubmit(e) {
    e.preventDefault();

    const menuId = document.getElementById('menu_id').value;

    const rutas = [];
    document.querySelectorAll('#rutas-container .input-ruta-patron').forEach(input => {
        const val = input.value.trim();
        if (val) rutas.push(val);
    });

    const menuData = {
        menu_padre_id: document.getElementById('menu_padre_id').value || null,
        menu_nombre: document.getElementById('menu_nombre').value.trim(),
        menu_icono: document.getElementById('menu_icono').value.trim(),
        menu_ruta: document.getElementById('menu_ruta').value.trim() || null,
        menu_orden: parseInt(document.getElementById('menu_orden').value, 10) || 0,
        menu_scope: document.getElementById('menu_scope').value.trim() || 'general',
        rutas: rutas
    };

    if (!menuData.menu_nombre) {
        showMessage('El nombre es obligatorio', 'error');
        return;
    }

    let url;
    if (menuId) {
        menuData.menu_id = menuId;
        url = `${base_url}Menu/actualizar_menu`;
    } else {
        url = `${base_url}Menu/crear_menu`;
    }

    try {
        showLoading(true);
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(menuData)
        });
        const data = await response.json();

        if (data.status) {
            showMessage(data.msg, 'success');
            closeMenuModal();
            await loadMenus();
        } else {
            showMessage('Error: ' + data.msg, 'error');
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

async function deleteMenu(menuId) {
    const result = await Swal.fire({
        title: '¿Eliminar menú?',
        text: 'Esta acción desactivará el menú y todos sus hijos.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    try {
        showLoading(true);
        const response = await fetch(`${base_url}Menu/eliminar_menu`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ menu_id: menuId })
        });
        const data = await response.json();

        if (data.status) {
            showMessage(data.msg, 'success');
            await loadMenus();
        } else {
            showMessage('Error: ' + data.msg, 'error');
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

async function deleteRuta(rutaId) {
    const result = await Swal.fire({
        title: '¿Eliminar este patrón?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí',
        cancelButtonText: 'Cancelar'
    });
    if (!result.isConfirmed) return;

    try {
        showLoading(true);
        const response = await fetch(`${base_url}Menu/eliminar_ruta`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ruta_id: rutaId })
        });
        const data = await response.json();

        if (data.status) {
            showMessage(data.msg, 'success');
            await loadMenus();
        } else {
            showMessage('Error: ' + data.msg, 'error');
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error');
    } finally {
        showLoading(false);
    }
}

// ==============================================
// SELECTOR DE ICONOS FONTAWESOME
// ==============================================

async function loadIconCatalog() {
    if (iconCatalog) return iconCatalog;
    try {
        const response = await fetch(`${base_url}src/js/fontawesome-icons.json`);
        if (!response.ok) throw new Error('No se pudo cargar el catálogo de iconos');
        iconCatalog = await response.json();
        return iconCatalog;
    } catch (error) {
        console.error('Error cargando catálogo de iconos:', error);
        return null;
    }
}

async function initIconPicker() {
    if (iconPickerInitialized) return;
    iconPickerInitialized = true;

    const catalog = await loadIconCatalog();
    if (!catalog) return;

    renderIconTabs(catalog);

    const firstCategory = Object.keys(catalog.categories)[0];
    iconPickerCategory = firstCategory;
    renderIconGrid(catalog.categories[firstCategory]);

    const trigger = document.getElementById('icon-picker-trigger');
    const dropdown = document.getElementById('icon-picker-dropdown');
    const searchInput = document.getElementById('icon-search');
    const clearBtn = document.getElementById('icon-picker-clear');

    trigger.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
        if (!dropdown.classList.contains('hidden')) {
            searchInput.focus();
        }
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#icon-picker')) {
            dropdown.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
        }
    });

    searchInput.addEventListener('input', (e) => {
        const term = e.target.value.trim().toLowerCase();
        searchIcons(term);
    });

    clearBtn.addEventListener('click', () => {
        setIconValue('');
        dropdown.classList.add('hidden');
    });
}

function renderIconTabs(catalog) {
    const tabsContainer = document.getElementById('icon-picker-tabs');
    tabsContainer.innerHTML = '';

    Object.keys(catalog.categories).forEach(categoryName => {
        const tab = document.createElement('div');
        tab.className = 'icon-picker-tab';
        if (categoryName === iconPickerCategory) tab.classList.add('active');
        tab.textContent = categoryName;
        tab.dataset.category = categoryName;
        tab.addEventListener('click', () => {
            iconPickerCategory = categoryName;
            document.querySelectorAll('.icon-picker-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('icon-search').value = '';
            renderIconGrid(catalog.categories[categoryName]);
        });
        tabsContainer.appendChild(tab);
    });
}

function renderIconGrid(icons) {
    const grid = document.getElementById('icon-picker-grid');
    const countEl = document.getElementById('icon-picker-count');
    const currentValue = document.getElementById('menu_icono').value;

    grid.innerHTML = '';

    if (!icons || icons.length === 0) {
        grid.innerHTML = '<div class="icon-picker-empty">No hay iconos para mostrar</div>';
        countEl.textContent = '0 iconos';
        return;
    }

    icons.forEach(iconClass => {
        const item = document.createElement('div');
        item.className = 'icon-picker-item';
        if (iconClass === currentValue) item.classList.add('selected');
        item.title = iconClass;
        item.innerHTML = `<i class="${iconClass}"></i>`;
        item.addEventListener('click', () => {
            setIconValue(iconClass);
            document.getElementById('icon-picker-dropdown').classList.add('hidden');
        });
        grid.appendChild(item);
    });

    countEl.textContent = `${icons.length} iconos`;
}

function searchIcons(term) {
    if (!iconCatalog) return;

    if (!term) {
        renderIconGrid(iconCatalog.categories[iconPickerCategory]);
        return;
    }

    const resultados = [];
    Object.values(iconCatalog.categories).forEach(icons => {
        icons.forEach(iconClass => {
            const iconName = iconClass.replace('fas fa-', '').replace(/-/g, ' ');
            if (iconName.includes(term) || iconClass.includes(term)) {
                if (!resultados.includes(iconClass)) {
                    resultados.push(iconClass);
                }
            }
        });
    });

    document.querySelectorAll('.icon-picker-tab').forEach(t => t.classList.remove('active'));

    renderIconGrid(resultados);
    document.getElementById('icon-picker-count').textContent = `${resultados.length} resultado(s)`;
}

function setIconValue(iconClass) {
    document.getElementById('menu_icono').value = iconClass;
    document.getElementById('icon-value').textContent = iconClass || 'Sin icono';

    const preview = document.getElementById('icon-preview');
    preview.className = 'icon-preview ' + (iconClass || 'far fa-circle');

    document.querySelectorAll('.icon-picker-item').forEach(item => {
        item.classList.toggle('selected', item.title === iconClass);
    });
}

function setIconValueSilent(iconClass) {
    document.getElementById('menu_icono').value = iconClass || '';
    document.getElementById('icon-value').textContent = iconClass || 'Sin icono';
    const preview = document.getElementById('icon-preview');
    preview.className = 'icon-preview ' + (iconClass || 'far fa-circle');

    document.querySelectorAll('.icon-picker-item').forEach(item => {
        item.classList.toggle('selected', item.title === iconClass);
    });
}
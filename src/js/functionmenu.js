// Variables globales
let allMenus = []
let allSubmenus = []
let userPermissions = []
let currentUserId = null

// Variables para gestión de menús
let currentMenus = []
let currentEditingMenuId = null
let currentMenuSubmenus = []

// ==============================================
// FUNCIONES PRINCIPALES DE LA APLICACIÓN
// ==============================================

// Inicializar la aplicación cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    initializeApp()
    initializeMenuManagement()
})

// Función para inicializar la aplicación
function initializeApp() {
    // Configurar event listeners para pestañas
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function () {
            // Desactivar todas las pestañas
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'))
            document.querySelectorAll('.tab-content').forEach(tc => tc.classList.remove('active'))

            // Activar la pestaña seleccionada
            this.classList.add('active')
            document.getElementById(`${this.dataset.tab}-tab`).classList.add('active')
        })
    })

    // Cargar usuarios
    loadUsers()

    // Configurar event listeners
    document.getElementById('select-user').addEventListener('change', handleUserSelect)
    document.getElementById('btn-save-permissions').addEventListener('click', savePermissions)
    document.getElementById('btn-reset-permissions').addEventListener('click', resetPermissions)
}

// ==============================================
// FUNCIONES DE GESTIÓN DE PERMISOS
// ==============================================

// Cargar lista de usuarios
async function loadUsers() {
    try {
        showLoading(true)
        const response = await fetch(`${base_url}Menu/cargar_usuarios`)
        const data = await response.json()

        if (data.status) {
            populateUserDropdown(data.data)
        } else {
            showMessage('Error al cargar usuarios: ' + data.msg, 'error')
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Llenar el dropdown de usuarios
function populateUserDropdown(users) {
    const select = document.getElementById('select-user')
    // Guardar la clase theme-select si existe
    const hasThemeClass = select.classList.contains('theme-select')
    select.innerHTML = '<option value="">-- Seleccione un usuario --</option>'

    users.forEach(user => {
        const option = document.createElement('option')
        option.value = user.usuario_id
        option.textContent = `${user.usuario_nick} (${user.personal_nombre} ${user.personal_apellido}) - ${user.rol_nombre}`
        select.appendChild(option)
    })
    // Restaurar la clase theme-select si se perdió
    if (hasThemeClass && !select.classList.contains('theme-select')) {
        select.classList.add('theme-select')
    }
}

// Manejar la selección de usuario
async function handleUserSelect(event) {
    const userId = event.target.value
    if (!userId) {
        document.getElementById('user-permissions').classList.add('hidden')
        return
    }
    currentUserId = userId
    try {
        showLoading(true)
        // Cargar información del usuario
        const userInfoResponse = await fetch(`${base_url}Menu/get_user_info/${userId}`)
        const userInfoData = await userInfoResponse.json()
        if (userInfoData.status) {
            document.getElementById('selected-username').textContent = `${userInfoData.data.personal_nombre} ${userInfoData.data.personal_apellido}`
        }
        // Cargar menús y submenús si aún no se han cargado
        if (allMenus.length === 0) {
            const menusResponse = await fetch(`${base_url}Menu/get_all_menus`)
            const menusData = await menusResponse.json()

            if (menusData.status) {
                allMenus = menusData.data.menus
                allSubmenus = menusData.data.submenus
            }
        }

        // Cargar permisos del usuario
        const permissionsResponse = await fetch(`${base_url}Menu/get_user_permissions/${userId}`)
        const permissionsData = await permissionsResponse.json()

        if (permissionsData.status) {
            userPermissions = permissionsData.data
            renderPermissions()
        }

        document.getElementById('user-permissions').classList.remove('hidden')

    } catch (error) {
        showMessage('Error al cargar información: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Renderizar los permisos en la interfaz
function renderPermissions() {
    const container = document.getElementById('permissions-container')
    container.innerHTML = ''

    // Agrupar submenús por menú
    const menuSubmenus = {}
    allSubmenus.forEach(submenu => {
        if (!menuSubmenus[submenu.menu_id]) {
            menuSubmenus[submenu.menu_id] = []
        }
        menuSubmenus[submenu.menu_id].push(submenu)
    })

    // Crear elementos para cada menú
    allMenus.forEach(menu => {
        // Verificar si el usuario tiene acceso a este menú
        const hasMenuAccess = userPermissions.some(p =>
            p.menu_id == menu.menu_id && p.submenu_id === null
        )

        const menuGroup = document.createElement('div')
        menuGroup.className = 'checkbox-group'

        const menuItem = document.createElement('div')
        menuItem.className = 'checkbox-item'

        const menuCheckbox = document.createElement('input')
        menuCheckbox.type = 'checkbox'
        menuCheckbox.id = `menu-${menu.menu_id}`
        menuCheckbox.checked = hasMenuAccess
        menuCheckbox.dataset.menuId = menu.menu_id
        menuCheckbox.dataset.type = 'menu'
        menuCheckbox.addEventListener('change', handleMenuSelection)

        const menuLabel = document.createElement('label')
        menuLabel.htmlFor = `menu-${menu.menu_id}`
        menuLabel.innerHTML = `<i class="${menu.menu_icono}"></i> ${menu.menu_nombre}`

        menuItem.appendChild(menuCheckbox)
        menuItem.appendChild(menuLabel)
        menuGroup.appendChild(menuItem)

        // Agregar submenús si existen
        if (menuSubmenus[menu.menu_id] && menuSubmenus[menu.menu_id].length > 0) {
            menuSubmenus[menu.menu_id].forEach(submenu => {
                // Verificar si el usuario tiene acceso a este submenú
                const hasSubmenuAccess = userPermissions.some(p =>
                    p.submenu_id == submenu.submenu_id
                )

                const submenuItem = document.createElement('div')
                submenuItem.className = 'checkbox-item'
                submenuItem.style.marginLeft = '20px'

                const submenuCheckbox = document.createElement('input')
                submenuCheckbox.type = 'checkbox'
                submenuCheckbox.id = `submenu-${submenu.submenu_id}`
                submenuCheckbox.checked = hasSubmenuAccess
                submenuCheckbox.dataset.menuId = menu.menu_id
                submenuCheckbox.dataset.submenuId = submenu.submenu_id
                submenuCheckbox.dataset.type = 'submenu'

                const submenuLabel = document.createElement('label')
                submenuLabel.htmlFor = `submenu-${submenu.submenu_id}`
                submenuLabel.textContent = submenu.submenu_nombre

                submenuItem.appendChild(submenuCheckbox)
                submenuItem.appendChild(submenuLabel)
                menuGroup.appendChild(submenuItem)
            })
        }

        container.appendChild(menuGroup)
    })
}

// Manejar la selección de un menú completo
function handleMenuSelection(event) {
    const menuId = event.target.dataset.menuId
    const isChecked = event.target.checked

    // Seleccionar/deseleccionar todos los submenús de este menú
    const submenuCheckboxes = document.querySelectorAll(
        `input[data-type="submenu"][data-menu-id="${menuId}"]`
    )

    submenuCheckboxes.forEach(checkbox => {
        checkbox.checked = isChecked
    })
}

// Guardar los permisos modificados
async function savePermissions() {
    if (!currentUserId) {
        showMessage('Por favor, seleccione un usuario primero', 'error')
        return
    }

    try {
        showLoading(true)

        // Recopilar todos los permisos seleccionados
        const selectedPermissions = []

        // Obtener permisos de menú
        const menuCheckboxes = document.querySelectorAll('input[data-type="menu"]:checked')
        menuCheckboxes.forEach(checkbox => {
            selectedPermissions.push({
                menu_id: checkbox.dataset.menuId,
                submenu_id: null
            })
        })

        // Obtener permisos de submenú
        const submenuCheckboxes = document.querySelectorAll('input[data-type="submenu"]:checked')
        submenuCheckboxes.forEach(checkbox => {
            selectedPermissions.push({
                menu_id: checkbox.dataset.menuId,
                submenu_id: checkbox.dataset.submenuId
            })
        })

        // Enviar los permisos al servidor
        const response = await fetch(`${base_url}Menu/update_user_permissions`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: currentUserId,
                permissions: selectedPermissions
            })
        })

        const data = await response.json()

        if (data.status) {
            showMessage('Permisos actualizados correctamente', 'success')
            // Actualizar los permisos locales
            userPermissions = selectedPermissions
            // Recargar la tabla de usuarios
            loadUsers()
        } else {
            showMessage('Error al actualizar permisos: ' + data.msg, 'error')
        }

    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Restablecer a los permisos originales
function resetPermissions() {
    if (confirm('¿Está seguro de que desea restablecer los permisos a su estado original?')) {
        renderPermissions() // Vuelve a renderizar con los permisos originales
        showMessage('Permisos restablecidos', 'success')
    }
}

// ==============================================
// FUNCIONES DE GESTIÓN DE MENÚS
// ==============================================

// Función para manejar envío del formulario de menú
async function handleMenuSubmit(e) {
    e.preventDefault()

    const menuData = {
        menu_nombre: document.getElementById('menu_nombre').value,
        menu_icono: document.getElementById('menu_icono').value,
        menu_orden: parseInt(document.getElementById('menu_orden').value),
        menu_tiene_submenu: document.getElementById('menu_tiene_submenu').checked,
        menu_pagina: document.getElementById('menu_pagina').value
    }

    if (currentEditingMenuId) {
        menuData.menu_id = currentEditingMenuId
        await updateMenu(menuData)
    } else {
        await createMenu(menuData)
    }
}

// Función para manejar envío del formulario de edición de menú
async function handleEditMenuSubmit(e) {
    e.preventDefault()

    const menuData = {
        menu_id: document.getElementById('edit_menu_id').value,
        menu_nombre: document.getElementById('edit_menu_nombre').value,
        menu_icono: document.getElementById('edit_menu_icono').value,
        menu_orden: parseInt(document.getElementById('edit_menu_orden').value),
        menu_tiene_submenu: document.getElementById('edit_menu_tiene_submenu').checked,
        menu_pagina: document.getElementById('edit_menu_pagina').value
    }

    await updateMenu(menuData)
}

// Inicializar gestión de menús
function initializeMenuManagement() {
    // Event listeners para formulario de creación de menú
    const crearMenuForm = document.getElementById('form-crear-menu')
    if (crearMenuForm) {
        crearMenuForm.addEventListener('submit', handleMenuSubmit)
    }

    document.getElementById('btn-crear-menu').addEventListener('click', showMenuForm)
    document.getElementById('btn-cancelar-menu').addEventListener('click', hideMenuForm)
    document.getElementById('menu_tiene_submenu').addEventListener('change', togglePaginaField)

    // Event listeners para modal de edición de menú
    const editarMenuForm = document.getElementById('form-editar-menu')
    if (editarMenuForm) {
        editarMenuForm.addEventListener('submit', handleEditMenuSubmit)
    }

    document.getElementById('edit_menu_tiene_submenu').addEventListener('change', toggleEditPaginaField)

    // Configurar event listeners para cierre de modales
    const editMenuModalCloseBtn = document.querySelector('#edit-menu-modal .modal-close');
    if (editMenuModalCloseBtn) {
        editMenuModalCloseBtn.addEventListener('click', () => closeModal('edit-menu-modal'));
    }
    const btnCancelarEditMenu = document.querySelector('#btn-cancelar-edit-menu');
    if (btnCancelarEditMenu) {
        btnCancelarEditMenu.addEventListener('click', () => closeModal('edit-menu-modal'));
    }
    const submenuModalCloseBtn = document.querySelector('#submenu-modal .modal-close');
    if (submenuModalCloseBtn) {
        submenuModalCloseBtn.addEventListener('click', () => closeModal('submenu-modal'));
    }

    setupModalCloseListeners()

    // Configurar formulario de submenú
    const formSubmenu = document.getElementById('form-submenu')
    if (formSubmenu) {
        formSubmenu.addEventListener('submit', handleSubmenuSubmit)
    }

    // Botón cancelar submenú
    const btnCancelarSubmenu = document.getElementById('btn-cancelar-submenu')
    if (btnCancelarSubmenu) {
        btnCancelarSubmenu.addEventListener('click', function () {
            closeModal('submenu-modal')
        })
    }

    // Cargar menús existentes
    loadMenus()
}

// Configurar event listeners para cierre de modales
function setupModalCloseListeners() {
    // Botones de cierre con la clase 'close'
    document.querySelectorAll('.close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function () {
            const modal = this.closest('.modal')
            if (modal) {
                closeModal(modal.id)
            }
        })
    })

    // Cerrar modal al hacer click fuera del contenido
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                closeModal(this.id)
            }
        })
    })

    // Botones con data-dismiss="modal"
    document.querySelectorAll('[data-dismiss="modal"]').forEach(btn => {
        btn.addEventListener('click', function () {
            const modal = this.closest('.modal')
            if (modal) {
                closeModal(modal.id)
            }
        })
    })
}

// Función para abrir modal
function openModal(modalId) {
    const modal = document.getElementById(modalId)
    if (modal) {
        modal.classList.remove('hidden')
        modal.style.display = 'flex'
        document.body.style.overflow = 'hidden'
    }
}

// Función para cerrar modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId)
    if (modal) {
        modal.classList.add('hidden')
        modal.style.display = 'none'
        document.body.style.overflow = ''

        // Resetear formularios si es necesario
        if (modalId === 'submenu-modal') {
            resetSubmenuForm()
        }
        if (modalId === 'edit-menu-modal') {
            currentEditingMenuId = null
        }
    }
}

// Mostrar/ocultar campo de página según si tiene submenús (para formulario de creación)
function togglePaginaField() {
    const tieneSubmenu = document.getElementById('menu_tiene_submenu').checked
    const paginaField = document.getElementById('pagina-field')

    if (tieneSubmenu) {
        paginaField.classList.add('hidden')
        document.getElementById('menu_pagina').value = ''
    } else {
        paginaField.classList.remove('hidden')
    }
}

// Mostrar/ocultar campo de página según si tiene submenús (para formulario de edición)
function toggleEditPaginaField() {
    const tieneSubmenu = document.getElementById('edit_menu_tiene_submenu').checked
    const paginaField = document.getElementById('edit_pagina-field')

    if (tieneSubmenu) {
        paginaField.classList.add('hidden')
        document.getElementById('edit_menu_pagina').value = ''
    } else {
        paginaField.classList.remove('hidden')
    }
}

// Mostrar formulario de creación de menú
function showMenuForm() {
    document.getElementById('create-menu-form').classList.remove('hidden')
    document.getElementById('btn-crear-menu').classList.add('hidden')
    resetMenuForm()
}

// Ocultar formulario de creación de menú
function hideMenuForm() {
    document.getElementById('create-menu-form').classList.add('hidden')
    document.getElementById('btn-crear-menu').classList.remove('hidden')
    resetMenuForm()
}

// Resetear formulario de menú
function resetMenuForm() {
    document.getElementById('form-crear-menu').reset()
    document.getElementById('menu_orden').value = '0'
    document.getElementById('pagina-field').classList.remove('hidden')
    document.getElementById('menu_tiene_submenu').checked = false
    currentEditingMenuId = null
}

// Resetear formulario de submenú
function resetSubmenuForm() {
    document.getElementById('form-submenu').reset()
    document.getElementById('submenu_id').value = ''
    // document.getElementById('submenu_menu_id').value = ''
    document.getElementById('submenu_orden').value = '0'
}

// Crear nuevo menú
async function createMenu(menuData) {
    try {
        showLoading(true)
        const response = await fetch(`${base_url}Menu/crear_menu`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(menuData)
        })

        const data = await response.json()

        if (data.status) {
            showMessage('Menú creado correctamente', 'success')
            hideMenuForm()
            loadMenus()
        } else {
            showMessage('Error al crear menú: ' + data.msg, 'error')
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Actualizar menú existente
async function updateMenu(menuData) {
    try {
        showLoading(true)
        const response = await fetch(`${base_url}Menu/actualizar_menu`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(menuData)
        })

        const data = await response.json()

        if (data.status) {
            showMessage('Menú actualizado correctamente', 'success')
            closeModal('edit-menu-modal')
            loadMenus()
        } else {
            showMessage('Error al actualizar menú: ' + data.msg, 'error')
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Cargar lista de menús
async function loadMenus() {
    try {
        showLoading(true)
        const response = await fetch(`${base_url}Menu/listar_menus`)
        const data = await response.json()

        if (data.status) {
            currentMenus = data.data
            renderMenusList()
        } else {
            showMessage('Error al cargar menús: ' + data.msg, 'error')
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Renderizar lista de menús
// Renderizar lista de menús
function renderMenusList() {
    const container = document.getElementById('menus-list-container')
    container.innerHTML = ''

    if (currentMenus.length === 0) {
        container.innerHTML = '<div class="no-data">No hay menús creados</div>'
        return
    }

    const gridContainer = document.createElement('div')
    gridContainer.className = 'menus-grid'

    currentMenus.forEach(menu => {
        // CORRECIÓN: Usar menu_es_desplegable en lugar de menu_tiene_submenu
        const tieneSubmenus = Boolean(menu.menu_es_desplegable) || menu.menu_es_desplegable == 1;

        const menuCard = document.createElement('div')
        menuCard.className = 'menu-card'

        menuCard.innerHTML = `
            <div class="menu-card-header">
                <i class="${menu.menu_icono}"></i>
                <h4>${menu.menu_nombre}</h4>
                <span class="menu-badge">${tieneSubmenus ? 'Con submenús' : 'Menú simple'}</span>
            </div>
            <div class="menu-card-body">
                <div class="menu-info">
                    <div class="info-item">
                        <span class="label">Página:</span>
                        <span class="value">${menu.menu_link || 'N/A'}</span>
                    </div>
                    <div class="info-item">
                        <span class="label">Orden:</span>
                        <span class="value">${menu.menu_orden}</span>
                    </div>
                </div>
                
                ${tieneSubmenus && menu.submenus && menu.submenus.length > 0 ? `
                <div class="submenus-section">
                    <h5>Submenús:</h5>
                    <div class="submenus-list">
                        ${menu.submenus.map(submenu => `
                        <div class="submenu-item">
                            <div class="submenu-info">
                                <i class="fas fa-angle-right"></i>
                                <span>${submenu.submenu_nombre}</span>
                                <small>${submenu.submenu_pagina}</small>
                            </div>
                            <div class="submenu-actions">
                                <button class="btn-icon edit-submenu" data-submenu-id="${submenu.submenu_id}" data-menu-id="${menu.menu_id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-icon delete-submenu" data-submenu-id="${submenu.submenu_id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
            <div class="menu-card-actions">
                <button class="btn btn-sm btn-edit edit-menu" data-menu-id="${menu.menu_id}">
                    <i class="fas fa-edit"></i> Editar
                </button>
                ${tieneSubmenus ? `
                <button class="btn btn-sm btn-secondary add-submenu" data-menu-id="${menu.menu_id}" data-menu-name="${menu.menu_nombre}">
                    <i class="fas fa-plus"></i> Submenú
                </button>
                ` : ''}
                <button class="btn btn-sm btn-danger delete-menu" data-menu-id="${menu.menu_id}">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        `

        gridContainer.appendChild(menuCard)
    })

    container.appendChild(gridContainer)

    // Agregar event listeners a los botones
    addMenuEventListeners()
}

// Agregar event listeners a los botones de menú
function addMenuEventListeners() {
    // Botones de editar menú
    document.querySelectorAll('.edit-menu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const menuId = e.currentTarget.dataset.menuId
            editMenu(menuId)
        })
    })

    // Botones de agregar submenú
    document.querySelectorAll('.add-submenu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const menuId = e.currentTarget.dataset.menuId
            const menuName = e.currentTarget.dataset.menuName
            openSubmenuModal(menuId, menuName)
        })
    })

    // Botones de eliminar menú
    document.querySelectorAll('.delete-menu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const menuId = e.currentTarget.dataset.menuId
            deleteMenu(menuId)
        })
    })

    // Botones de editar submenú
    document.querySelectorAll('.edit-submenu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const submenuId = e.currentTarget.dataset.submenuId
            const menuId = e.currentTarget.dataset.menuId
            editSubmenu(submenuId, menuId)
        })
    })

    // Botones de eliminar submenú
    document.querySelectorAll('.delete-submenu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const submenuId = e.currentTarget.dataset.submenuId
            deleteSubmenu(submenuId)
        })
    })
}

// Editar menú existente
function editMenu(menuId) {
    const menu = currentMenus.find(m => m.menu_id == menuId)
    if (!menu) {
        showMessage('Error: No se encontró el menú', 'error')
        return
    }

    currentEditingMenuId = menuId

    // Llenar el formulario de edición
    document.getElementById('edit_menu_id').value = menuId
    document.getElementById('edit_menu_nombre').value = menu.menu_nombre || ''
    document.getElementById('edit_menu_icono').value = menu.menu_icono || ''
    document.getElementById('edit_menu_orden').value = menu.menu_orden || '0';
    const tieneSubmenus = Boolean(parseInt(menu.menu_es_desplegable));
    document.getElementById('edit_menu_tiene_submenu').checked = tieneSubmenus;
    document.getElementById('edit_menu_pagina').value = menu.menu_link || '';

    // Ajustar visibilidad del campo de página
    toggleEditPaginaField()

    // Mostrar la modal de edición
    openModal('edit-menu-modal')
}

// Eliminar menú
async function deleteMenu(menuId) {
    try {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })

        if (result.isConfirmed) {
            showLoading(true)
            const response = await fetch(`${base_url}Menu/eliminar_menu`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ menu_id: menuId })
            })

            const data = await response.json()

            if (data.status) {
                showMessage('Menú eliminado correctamente', 'success')
                loadMenus()
            } else {
                showMessage('Error al eliminar menú: ' + data.msg, 'error')
            }
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// ==============================================
// FUNCIONES DE GESTIÓN DE SUBMENÚS
// ==============================================

// Abrir modal de submenús
async function openSubmenuModal(menuId, menuName) {
    // 1. Limpia el formulario primero para evitar valores anteriores
    resetSubmenuForm();

    // 2. Ahora, asigna los nuevos valores
    document.getElementById('submenu_menu_id').value = menuId;
    document.getElementById('modal-menu-name').textContent = menuName;

    // 3. Carga los submenús y abre el modal
    await loadSubmenus(menuId);
    openModal('submenu-modal');
}

// Cargar submenús de un menú
async function loadSubmenus(menuId) {
    try {
        const response = await fetch(`${base_url}Menu/get_submenus_by_menu/${menuId}`)
        const data = await response.json()

        const container = document.getElementById('submenus-container')
        container.innerHTML = ''

        if (data.status && data.data.length > 0) {
            currentMenuSubmenus = data.data

            data.data.forEach(submenu => {
                const submenuItem = document.createElement('div')
                submenuItem.className = 'submenu-list-item'
                submenuItem.innerHTML = `
                    <div class="submenu-info">
                        <strong>${submenu.submenu_nombre}</strong>
                        <span>${submenu.submenu_pagina}</span>
                    </div>
                    <div class="submenu-actions">
                        <button class="btn-icon edit-list-submenu" data-submenu-id="${submenu.submenu_id}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-icon delete-list-submenu" data-submenu-id="${submenu.submenu_id}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `
                container.appendChild(submenuItem)
            })

            // Agregar event listeners
            document.querySelectorAll('.edit-list-submenu').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const submenuId = e.currentTarget.dataset.submenuId
                    const submenu = currentMenuSubmenus.find(s => s.submenu_id == submenuId)
                    if (submenu) {
                        editSubmenuInModal(submenu)
                    }
                })
            })

            document.querySelectorAll('.delete-list-submenu').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const submenuId = e.currentTarget.dataset.submenuId
                    deleteSubmenuFromList(submenuId)
                })
            })
        } else {
            container.innerHTML = '<div class="no-data">No hay submenús creados</div>'
        }
    } catch (error) {
        showMessage('Error al cargar submenús: ' + error.message, 'error')
    }
}

// Editar submenú en el modal
function editSubmenuInModal(submenu) {
    console.log(submenu)
    document.getElementById('submenu_id').value = submenu.submenu_id
    document.getElementById('submenu_nombre').value = submenu.submenu_nombre
    document.getElementById('submenu_pagina').value = submenu.submenu_pagina
    document.getElementById('submenu_link').value = submenu.submenu_link || ''
    document.getElementById('submenu_orden').value = submenu.submenu_orden || '0'
}
// Editar submenú desde la lista principal
// This function will handle editing a submenu directly.
// Editar submenú desde la lista principal
function editSubmenu(submenuId, menuId) {
    // Limpia el formulario primero
    resetSubmenuForm();

    const menu = currentMenus.find(m => m.menu_id == menuId);
    if (menu && menu.submenus) {
        const submenu = menu.submenus.find(s => s.submenu_id == submenuId);
        if (submenu) {
            // Asigna los valores de edición
            document.getElementById('submenu_menu_id').value = menuId;
            document.getElementById('submenu_id').value = submenu.submenu_id;
            document.getElementById('submenu_nombre').value = submenu.submenu_nombre;
            document.getElementById('submenu_pagina').value = submenu.submenu_pagina;
            document.getElementById('submenu_link').value = submenu.submenu_link || '';
            document.getElementById('submenu_orden').value = submenu.submenu_orden || '0';
            document.getElementById('modal-menu-name').textContent = menu.menu_nombre;

            // Carga y abre el modal
            loadSubmenus(menuId);
            openModal('submenu-modal');
        }
    }
}
function editSubmenuUU(submenuId, menuId) {
    const menu = currentMenus.find(m => m.menu_id == menuId)
    if (menu && menu.submenus) {
        const submenu = menu.submenus.find(s => s.submenu_id == submenuId)
        if (submenu) {
            // Establece los valores directamente sin llamar a openSubmenuModal
            document.getElementById('submenu_menu_id').value = menuId
            document.getElementById('submenu_id').value = submenu.submenu_id
            document.getElementById('submenu_nombre').value = submenu.submenu_nombre
            document.getElementById('submenu_pagina').value = submenu.submenu_pagina
            document.getElementById('submenu_link').value = submenu.submenu_link || ''
            document.getElementById('submenu_orden').value = submenu.submenu_orden || '0'
            document.getElementById('modal-menu-name').textContent = menu.menu_nombre

            // Abre el modal una vez que los campos están llenos
            loadSubmenus(menuId)
            openModal('submenu-modal')
        }
    }
}
async function editSubmenue(submenuId, menuId) {
    const menu = currentMenus.find(m => m.menu_id == menuId)
    if (!menu || !menu.submenus) {
        showMessage('Error: No se encontró el menú o los submenús.', 'error')
        return
    }
    const submenu = menu.submenus.find(s => s.submenu_id == submenuId)
    if (!submenu) {
        showMessage('Error: No se encontró el submenú.', 'error')
        return
    }

    // Set the hidden fields first, so they don't get cleared
    document.getElementById('submenu_menu_id').value = menuId
    document.getElementById('submenu_id').value = submenuId

    // Fill the rest of the form
    document.getElementById('submenu_nombre').value = submenu.submenu_nombre
    document.getElementById('submenu_pagina').value = submenu.submenu_pagina
    document.getElementById('submenu_link').value = submenu.submenu_link || ''
    document.getElementById('submenu_orden').value = submenu.submenu_orden || 0

    document.getElementById('modal-menu-name').textContent = `Editando: ${submenu.submenu_nombre}`

    await loadSubmenus(menuId)
    openModal('submenu-modal')
}
// Manejar envío del formulario de submenú
// Manejar envío del formulario de submenú
async function handleSubmenuSubmit(e) {
    e.preventDefault()

    const submenuData = {
        menu_id: document.getElementById('submenu_menu_id').value,
        submenu_id: document.getElementById('submenu_id').value,
        submenu_nombre: document.getElementById('submenu_nombre').value,
        submenu_pagina: document.getElementById('submenu_pagina').value,
        submenu_link: document.getElementById('submenu_link').value,
        submenu_orden: parseInt(document.getElementById('submenu_orden').value) || 0
    }

    // Updated validation
    if (!submenuData.submenu_nombre || !submenuData.submenu_pagina || !submenuData.submenu_link) {
        showMessage('Por favor complete todos los campos requeridos (nombre, página y URL).', 'error')
        return
    }

    await saveSubmenu(submenuData)
}
// Guardar submenú (crear o actualizar)
// Guardar submenú (crear o actualizar)
async function saveSubmenu(submenuData) {
    try {
        showLoading(true)
        const url = submenuData.submenu_id ? `${base_url}Menu/actualizar_submenu` : `${base_url}Menu/crear_submenu`

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(submenuData)
        })

        const data = await response.json()

        if (data.status) {
            showMessage(submenuData.submenu_id ? 'Submenú actualizado correctamente' : 'Submenú creado correctamente', 'success')
            resetSubmenuForm()
            await loadSubmenus(submenuData.menu_id)
            loadMenus()

            // Cerrar el modal directamente después de guardar con éxito
            closeModal('submenu-modal')
        } else {
            showMessage('Error: ' + data.msg, 'error')
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Eliminar submenú desde la lista del modal
async function deleteSubmenuFromList(submenuId) {
    try {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })

        if (result.isConfirmed) {
            showLoading(true)
            const menuId = document.getElementById('submenu_menu_id').value

            const response = await fetch(`${base_url}Menu/eliminar_submenu`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ submenu_id: submenuId })
            })

            const data = await response.json()

            if (data.status) {
                showMessage('Submenú eliminado correctamente', 'success')
                await loadSubmenus(menuId)
                loadMenus()
            } else {
                showMessage('Error al eliminar submenú: ' + data.msg, 'error')
            }
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// Eliminar submenú desde la lista principal
async function deleteSubmenu(submenuId) {
    try {
        const result = await Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        })

        if (result.isConfirmed) {
            showLoading(true)

            const response = await fetch(`${base_url}Menu/eliminar_submenu`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ submenu_id: submenuId })
            })

            const data = await response.json()

            if (data.status) {
                showMessage('Submenú eliminado correctamente', 'success')
                loadMenus()
            } else {
                showMessage('Error al eliminar submenú: ' + data.msg, 'error')
            }
        }
    } catch (error) {
        showMessage('Error de conexión: ' + error.message, 'error')
    } finally {
        showLoading(false)
    }
}

// ==============================================
// FUNCIONES DE UTILIDAD
// ==============================================

// Mostrar/ocultar loading
function showLoading(show) {
    const loadingElement = document.getElementById('loading')
    if (show) {
        loadingElement.classList.remove('hidden')
    } else {
        loadingElement.classList.add('hidden')
    }
}

// Mostrar mensajes de feedback
function showMessage(message, type) {
    const messageElement = document.getElementById('message')
    messageElement.textContent = message
    messageElement.className = `alert alert-${type}`
    messageElement.classList.remove('hidden')

    // Ocultar el mensaje después de 5 segundos
    setTimeout(() => {
        messageElement.classList.add('hidden')
    }, 5000)
}
// Validación de solo números
function soloNumeros(e) {
    const key = e.keyCode || e.which
    const tecla = String.fromCharCode(key).toLowerCase()
    const letras = "0123456789"
    const especiales = [8, 37, 39, 46] // Backspace, Left, Right, Delete

    const tecla_especial = especiales.includes(key)

    if (letras.indexOf(tecla) === -1 && !tecla_especial) {
        e.preventDefault()
        return false
    }
    return true
}

// Validación de solo letras y caracteres especiales en español
function soloLetras(e) {
    const key = e.keyCode || e.which
    const tecla = String.fromCharCode(key).toLowerCase()
    const letras = " áéíóúabcdefghijklmnñopqrstuvwxyz.,:¿?¡!"
    const especiales = [8, 9, 13, 32, 37, 39, 46] // Backspace, Tab, Enter, Space, Left, Right, Delete

    const tecla_especial = especiales.includes(key)

    // Permitir teclas de control
    if (key >= 16 && key <= 20) return true // Shift, Ctrl, Alt
    if (key >= 33 && key <= 40) return true // PageUp, PageDown, End, Home, Arrow keys

    if (letras.indexOf(tecla) === -1 && !tecla_especial) {
        e.preventDefault()
        return false
    }
    return true
}

// Validación de email
function validarEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return regex.test(email)
}

// Validación de teléfono
function validarTelefono(telefono) {
    const regex = /^[\+]?[0-9\s\-\(\)]{7,15}$/
    return regex.test(telefono)
}

// Notificación Toast mejorada
function notifi(data, icon = 'info', position = 'top-end', timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: position,
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    })

    Toast.fire({
        icon: icon,
        title: data,
        background: getComputedStyle(document.documentElement).getPropertyValue('--light-secondary'),
        color: getComputedStyle(document.documentElement).getPropertyValue('--light-text')
    })
}

// Alert personalizable
function showAlert(title, text, icon = 'info', confirmButtonText = 'OK') {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        confirmButtonText: confirmButtonText,
        background: getComputedStyle(document.documentElement).getPropertyValue('--light-secondary'),
        color: getComputedStyle(document.documentElement).getPropertyValue('--light-text'),
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--primary')
    })
}

// Confirmación con promesa
function showConfirm(title, text, icon = 'question', confirmText = 'Sí', cancelText = 'No') {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--success'),
        cancelButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--danger'),
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        background: getComputedStyle(document.documentElement).getPropertyValue('--light-secondary'),
        color: getComputedStyle(document.documentElement).getPropertyValue('--light-text')
    })
}

// Toast simplificado (alias de notifi)
function showToast(message, icon = 'info') {
    notifi(message, icon)
}

// Cargar contenido dinámico
async function loadContent(url, containerId, options = {}) {
    try {
        const { method = 'GET', data = null, headers = {} } = options
        const container = document.getElementById(containerId)

        if (!container) {
            console.error('Contenedor no encontrado:', containerId)
            return
        }

        // Mostrar loader
        container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i></div>'

        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...headers
            },
            body: data ? JSON.stringify(data) : null
        })

        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`)
        }

        const content = await response.text()
        container.innerHTML = content

        // Ejecutar scripts dentro del contenido cargado
        container.querySelectorAll('script').forEach(script => {
            const newScript = document.createElement('script')
            newScript.text = script.text
            document.head.appendChild(newScript).remove()
        })

    } catch (error) {
        console.error('Error loading content:', error)
        notifi('Error al cargar el contenido', 'error')
    }
}

// Formatear número con separadores
function formatNumber(number, decimals = 0) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number)
}

// Formatear fecha
function formatDate(date, format = 'long') {
    const options = {
        short: { day: '2-digit', month: '2-digit', year: 'numeric' },
        medium: { day: '2-digit', month: 'short', year: 'numeric' },
        long: { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }
    }

    return new Date(date).toLocaleDateString('es-ES', options[format] || options.long)
}

// Toggle sidebar mejorado
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar')
    const mainContent = document.getElementById('mainContent')
    const toggleBtn = document.getElementById('toggleSidebar')

    if (!sidebar || !mainContent || !toggleBtn) return

    sidebar.classList.toggle('collapsed')
    mainContent.classList.toggle('expanded')

    // Cambiar icono
    const icon = toggleBtn.querySelector('i')
    if (icon) {
        if (sidebar.classList.contains('collapsed')) {
            icon.classList.replace('fa-chevron-left', 'fa-chevron-right')
        } else {
            icon.classList.replace('fa-chevron-right', 'fa-chevron-left')
        }
    }

    // Guardar preferencia
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'))
}

// Toggle theme mejorado
function toggleTheme() {
    const body = document.body
    const themeToggle = document.getElementById('themeToggle')

    body.classList.toggle('dark-mode')

    if (themeToggle) {
        themeToggle.classList.toggle('light')
        themeToggle.classList.toggle('dark')
    }

    // Guardar preferencia
    localStorage.setItem('darkMode', body.classList.contains('dark-mode'))
}

// Cerrar menús dropdown al hacer clic fuera
function setupDropdowns() {
    document.addEventListener('click', (e) => {
        document.querySelectorAll('.dropdown.show').forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('show')
            }
        })
    })
}

// Global audit function
async function auditLog(actionType, module, description, referenceId = null, useSendBeacon = false) {
    if (typeof base_url === 'undefined' || !base_url) {
        console.error('base_url no está definida. No se puede registrar la auditoría.');
        return;
    }

    const data = {
        action_type: actionType,
        module: module,
        description: description,
        reference_id: referenceId
    };

    const url = base_url + 'Audit/log_action';

    if (useSendBeacon) {
        // navigator.sendBeacon es ideal para enviar datos cuando la página se está cerrando,
        // ya que la petición se envía de forma asíncrona y no bloquea el cierre.
        try {
            const blob = new Blob([JSON.stringify(data)], { type: 'application/json' });
            navigator.sendBeacon(url, blob);
        } catch (error) {
            console.error('Error al enviar auditoría con sendBeacon:', error);
        }
    } else {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            // No es necesario esperar la respuesta para auditoría, pero podemos loguear errores.
            if (!response.ok) {
                console.error('Error en la respuesta del servidor al registrar auditoría:', response.status, response.statusText);
            }
        } catch (error) {
            console.error('Error al enviar auditoría con fetch:', error);
        }
    }
}

// Helper para obtener el módulo actual del URL
function getCurrentModuleFromUrl() {
    const path = window.location.pathname;
    // Eliminar base_url si está presente
    let relativePath = path.replace(base_url, '');
    // Dividir por '/' y tomar el primer segmento como módulo
    const segments = relativePath.split('/').filter(s => s.length > 0);
    // Capitalizar el primer segmento para un nombre de módulo más legible
    return segments.length > 0 ? segments[0].charAt(0).toUpperCase() + segments[0].slice(1) : 'Home';
}

// Variable para almacenar el módulo actual y evitar logs duplicados en la misma página
let currentModule = '';

// Inicialización de la aplicación
function initApp() {
    // Inicializar tooltips de Bootstrap en toda la aplicación
    $('[data-toggle="tooltip"]').tooltip();

    // Restaurar preferencias
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        toggleSidebar()
    }

    if (localStorage.getItem('darkMode') === 'true') {
        toggleTheme()
    }

    // Configurar eventos
    setupEventListeners()

    // Registrar la vista inicial de la página
    currentModule = getCurrentModuleFromUrl();
    auditLog('PAGE_VIEW', currentModule, `Acceso a módulo: ${currentModule}.`);

    // Escuchar cambios en la URL para registrar vistas de página en SPAs o navegaciones
    window.addEventListener('popstate', () => {
        const newModule = getCurrentModuleFromUrl();
        if (newModule !== currentModule) {
            auditLog('PAGE_VIEW', newModule, `Navegación a módulo: ${newModule}.`);
            currentModule = newModule;
        }
    });
    setupDropdowns()
}

// Funciones para controlar el sidebar móvil
function toggleMobileSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mainContentOverlay');
    if (!sidebar || !overlay) return;

    const isOpen = sidebar.classList.toggle('open');
    overlay.classList.toggle('active', isOpen);
    document.body.classList.toggle('sidebar-open', isOpen);
}

function closeMobileSidebar() {
    document.getElementById('sidebar')?.classList.remove('open');
    document.getElementById('mainContentOverlay')?.classList.remove('active');
    document.body.classList.remove('sidebar-open');
}

// Actualizar fecha actual
function updateCurrentDate() {
    const currentDateElement = document.getElementById('current-date')
    if (currentDateElement) {
        const today = new Date()
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }
        currentDateElement.textContent = today.toLocaleDateString('es-ES', options)
    }
}

// Configurar event listeners
function setupEventListeners() {
    // Toggle sidebar de escritorio
    document.getElementById('toggleSidebar')?.addEventListener('click', toggleSidebar);

    // --- INICIO: Lógica de Sidebar Móvil ---
    const mobileToggleBtn = document.getElementById('mobileToggle');
    const sidebar = document.getElementById('sidebar');

    // Crear y añadir el overlay al body
    const overlay = document.createElement('div');
    overlay.id = 'mainContentOverlay';
    overlay.className = 'main-content-overlay';
    document.body.appendChild(overlay);

    // Abrir sidebar con el botón de hamburguesa
    mobileToggleBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleMobileSidebar();
    });

    // Cerrar sidebar al hacer clic en el overlay
    overlay.addEventListener('click', closeMobileSidebar);

    // Manejar clics DENTRO del sidebar en móvil
    if (sidebar) {
        sidebar.addEventListener('click', function (e) {
            // Esta lógica solo se aplica en vista móvil
            if (window.innerWidth > 992) return;

            const clickedElement = e.target;
            const menuLink = clickedElement.closest('.menu-link'); // Busca el enlace de menú más cercano

            // Si se hizo clic en un enlace principal
            if (menuLink) {
                e.stopPropagation(); // Detenemos la propagación para evitar conflictos
                const parentItem = menuLink.parentElement;

                // Si tiene submenú, solo lo abre/cierra y evita que el sidebar se cierre
                if (parentItem.classList.contains('has-submenu')) {
                    e.preventDefault(); // Evita la navegación del enlace '#'
                    parentItem.classList.toggle('open');
                } else {
                    // Si es un enlace final, cierra el sidebar para navegar a la página
                    closeMobileSidebar();
                }
            } else if (clickedElement.closest('.submenu-link')) {
                // Si se hizo clic en un enlace de submenú, también detenemos la propagación
                e.stopPropagation();
                // Si se hizo clic en un enlace de submenú, cierra el sidebar
                closeMobileSidebar(); // Cierra para navegar a la página del submenú
            }
        });
    }
    // --- FIN: Lógica de Sidebar Móvil ---

    // --- INICIO: Lógica de Menú de Usuario y Tema ---
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    const userMenuBtn = document.getElementById('userMenuBtn');
    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            document.getElementById('userMenuDropdown')?.classList.toggle('show');
        });
    }
    // --- FIN: Lógica de Menú de Usuario y Tema ---

    // Actualizar fecha actual
    updateCurrentDate();

    // --- INICIO: Lógica para desplegar submenús ---
    // Esta lógica se aplica tanto en escritorio como en móvil.
    document.querySelectorAll('.has-submenu > .menu-link').forEach(menuLink => {
        menuLink.addEventListener('click', function (e) {
            // Prevenimos la navegación si el enlace es solo para desplegar
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
            }
            // Alternamos la clase 'open' en el elemento padre 'li'
            this.parentElement.classList.toggle('open');
        });
    });
    // --- FIN: Lógica para desplegar submenús ---
}

// Debounce para optimizar eventos
function debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

// Throttle para eventos frecuentes
function throttle(func, limit) {
    let inThrottle
    return function () {
        const args = arguments
        const context = this
        if (!inThrottle) {
            func.apply(context, args)
            inThrottle = true
            setTimeout(() => inThrottle = false, limit)
        }
    }
}

// Esperar a que el DOM esté listo
document.addEventListener('DOMContentLoaded', function () {
    // Inicializar la aplicación
    initApp()

    // Configurar event listeners para elementos dinámicos
    document.addEventListener('click', function (e) {
        // Cierra el dropdown del usuario si se hace clic fuera
        if (!e.target.closest('.user-menu')) {
            document.getElementById('userMenuDropdown')?.classList.remove('show');
        }
    })

    // Manejar resize con debounce
    window.addEventListener('resize', debounce(function () {
        // Si la ventana es más ancha que el punto de quiebre móvil, cerramos el sidebar
        if (window.innerWidth > 992) closeMobileSidebar();
    }, 250))
})

// Exportar funciones para uso global (si es necesario)
window.App = {
    soloNumeros,
    soloLetras,
    validarEmail,
    validarTelefono,
    notifi,
    showAlert,
    showConfirm,
    showToast,
    loadContent,
    formatNumber,
    formatDate,
    toggleSidebar,
    toggleTheme,
    initApp
}

/** adaptacion del tema oscuro a todos los slect */
// Función para actualizar todos los selects al cambiar tema
function updateSelectThemes() {
    document.querySelectorAll('.theme-select').forEach(select => {
        // Forzar repintado para aplicar estilos CSS
        select.style.display = 'none'
        select.offsetHeight // Trigger reflow
        select.style.display = ''
    })
}

// Escuchar cambios de tema
document.addEventListener('DOMContentLoaded', function () {
    // Observar cambios en la clase dark-mode del body
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.attributeName === 'class') {
                setTimeout(updateSelectThemes, 50)
            }
        })
    })

    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    })

    // También actualizar cuando se cambie el tema manualmente
    const themeToggle = document.getElementById('themeToggle')
    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            setTimeout(updateSelectThemes, 100)
        })
    }

    // Aplicar la clase theme-select a todos los selects al cargar
    document.querySelectorAll('select').forEach(select => {
        if (!select.classList.contains('theme-select')) {
            select.classList.add('theme-select')
        }
    })
})
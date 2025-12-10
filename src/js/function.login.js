document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('formLogin');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }

    // --- INICIO DE LA CORRECCIÓN ---
    const recoveryForm = document.getElementById('formRecuperar');
    if (recoveryForm) {
        recoveryForm.addEventListener('submit', handleRecoverySubmit);
    }

    // --- INICIO: Nueva implementación del modal de configuración ---
    setupKeyListener();  // Configurar el listener para la combinación de teclas.

    const cleanForm = document.getElementById('formCleanDirectory');
    if (cleanForm) {
        cleanForm.addEventListener('submit', handleCleanDirectorySubmit);
    }

    // Listener para el nuevo formulario de eliminar archivo
    const deleteFileForm = document.getElementById('formDeleteFile');
    if (deleteFileForm) {
        deleteFileForm.addEventListener('submit', handleDeleteFileSubmit);
    }
    const renameForm = document.getElementById('formRename');
    if (renameForm) {
        renameForm.addEventListener('submit', handleRenameSubmit);
    }

    const dbExportForm = document.getElementById('formDbExport');
    if (dbExportForm) {
        dbExportForm.addEventListener('submit', handleDbExportSubmit);
    }

    // Listener para el nuevo botón de eliminar tablas
    const deleteTablesBtn = document.getElementById('btnDeleteTables');
    if (deleteTablesBtn) {
        deleteTablesBtn.addEventListener('click', handleDeleteTablesSubmit);
    }

    // --- FIN: Nueva implementación del modal de configuración ---
});

/**
 * Maneja el envío del formulario de inicio de sesión.
 * @param {Event} e - El evento de envío del formulario.
 */
async function handleLoginSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;

    // Deshabilitar botón y mostrar spinner
    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Verificando...`;

    try {
        const response = await fetch(base_url + 'Login/loginUser', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status) { // Éxito en el login
            notifi('¡Bienvenido!', 'success');
            window.location.href = base_url + 'home';
        } else {
            // --- INICIO DE LA MODIFICACIÓN ---
            // Verificar si el error es por una sesión activa
            if (result.code === 'session_active') {
                // Mostrar diálogo de SweetAlert para forzar cierre
                showForceLogoutDialog(formData);
            } else {
                // Mostrar error genérico
                notifi(result.msg, 'error');
            }
            // --- FIN DE LA MODIFICACIÓN ---
        }
    } catch (error) {
        notifi('Error de conexión con el servidor.', 'error');
        console.error('Error en handleLoginSubmit:', error);
    } finally {
        // Restaurar el botón solo si no hubo redirección
        if (!window.location.href.endsWith('home')) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
        }
    }
}

/**
 * Muestra un diálogo de confirmación para forzar el cierre de una sesión activa.
 * @param {FormData} loginFormData - Los datos del formulario de login para reintentar.
 */
function showForceLogoutDialog(loginFormData) {
    const userNick = loginFormData.get('txtUser'); // Asumimos que el campo de usuario es 'txtUser'

    Swal.fire({
        title: 'Sesión Activa Detectada',
        html: `El usuario <b>${userNick}</b> ya tiene una sesión activa en otro lugar.<br><br>¿Deseas forzar el cierre de la sesión anterior e iniciar una nueva aquí?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, cerrar e iniciar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            // El usuario confirmó, forzar cierre de sesión
            await forceLogoutAndRetry(userNick, loginFormData);
        } else {
            // El usuario canceló, restauramos el botón de login
            const submitButton = document.getElementById('formLogin').querySelector('button[type="submit"]');
            submitButton.disabled = false;
            submitButton.innerHTML = 'Iniciar Sesión';
        }
    });
}

// --- INICIO: Funciones para el nuevo modal de configuración ---

/**
 * Configura el listener de teclado para abrir el modal con una combinación de teclas.
 */
function setupKeyListener() {
    document.addEventListener('keydown', function (e) {
        // Abrir modal de configuración con Ctrl + Alt + C
        if (e.ctrlKey && e.altKey && e.key === 'c') {
            e.preventDefault(); // Evita acciones por defecto del navegador
            $('#configModal').fadeIn(200).css('display', 'flex');
        }

        // Abrir modal de exportación de BD con Ctrl + Alt + T
        if (e.ctrlKey && e.altKey && e.key === 't') {
            e.preventDefault();
            $('#dbExportModal').fadeIn(200).css('display', 'flex');
            loadDatabaseTables(); // Cargar las tablas al abrir
        }
    });
}

/**
 * Maneja el envío del formulario para eliminar un directorio.
 * @param {Event} e - El evento de envío del formulario.
 */
async function handleCleanDirectorySubmit(e) {
    e.preventDefault();
    const form = e.target;
    const pathInput = form.querySelector('#directoryPath');
    const path = pathInput.value.trim();

    if (!path) {
        notifi('La ruta del directorio no puede estar vacía.', 'warning');
        return;
    }

    // Confirmación de seguridad
    const confirmation = await Swal.fire({
        title: '¿Estás seguro?',
        html: `Estás a punto de eliminar permanentemente la carpeta y todo su contenido:<br><b>storage/${path}</b><br><br>¡Esta acción no se puede deshacer!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, ¡eliminar!',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        const formData = new FormData();
        formData.append('path', path);

        try {
            const response = await fetch(base_url + 'Login/deleteDirectory', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            notifi(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                form.reset();
                $('#configModal').fadeOut(200);
            }
        } catch (error) {
            console.error('Error al eliminar directorio:', error);
            notifi('Error de conexión con el servidor.', 'error');
        }
    }
}

/**
 * Maneja el envío del formulario para exportar tablas de la BD.
 * @param {Event} e - El evento de envío del formulario.
 */
async function handleDbExportSubmit(e) {
    e.preventDefault();
    const selectedTables = Array.from(document.querySelectorAll('#tableListContainer input[type="checkbox"]:checked'))
        .map(cb => cb.value);

    if (selectedTables.length === 0) {
        notifi('Debes seleccionar al menos una tabla para exportar.', 'warning');
        return;
    }

    const confirmation = await Swal.fire({
        title: 'Confirmar Descarga',
        text: `Se descargará un archivo .sql con la estructura y datos de ${selectedTables.length} tabla(s). ¿Continuar?`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, descargar',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('tables', JSON.stringify(selectedTables));

            const response = await fetch(base_url + 'Login/exportTables', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('La respuesta del servidor no fue exitosa.');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            const fecha = new Date().toISOString().slice(0, 10);
            a.download = `backup_busyaracuy_${fecha}.sql`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            a.remove();

            notifi('Descarga iniciada.', 'success');
            $('#dbExportModal').fadeOut(200);

        } catch (error) {
            console.error('Error al exportar las tablas:', error);
            notifi('Ocurrió un error durante la exportación.', 'error');
        }
    }
}

/**
 * Maneja el envío del formulario para eliminar un archivo.
 * @param {Event} e - El evento de envío del formulario.
 */
async function handleDeleteFileSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const pathInput = form.querySelector('#filePath');
    const filePath = pathInput.value.trim();

    if (!filePath) {
        notifi('La ruta del archivo no puede estar vacía.', 'warning');
        return;
    }

    // Confirmación de seguridad
    const confirmation = await Swal.fire({
        title: '¿Estás seguro?',
        html: `Estás a punto de eliminar permanentemente el archivo:<br><b>storage/${filePath}</b><br><br>¡Esta acción no se puede deshacer!`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, ¡eliminar!',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        const formData = new FormData();
        formData.append('path', filePath);

        try {
            const response = await fetch(base_url + 'Login/deleteFile', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            notifi(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                form.reset();
            }
        } catch (error) {
            console.error('Error al eliminar archivo:', error);
            notifi('Error de conexión con el servidor.', 'error');
        }
    }
}

/**
 * Maneja el envío del formulario para renombrar un archivo o directorio.
 * @param {Event} e - El evento de envío del formulario.
 */
async function handleRenameSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const oldPathInput = form.querySelector('#oldPath');
    const newPathInput = form.querySelector('#newPath');
    const oldPath = oldPathInput.value.trim();
    const newPath = newPathInput.value.trim();

    if (!oldPath || !newPath) {
        notifi('Ambas rutas no pueden estar vacías.', 'warning');
        return;
    }

    // Confirmación de seguridad
    const confirmation = await Swal.fire({
        title: '¿Estás seguro?',
        html: `Estás a punto de renombrar:<br><b>storage/${oldPath}</b><br>a<br><b>storage/${newPath}</b><br><br>¡Esta acción puede afectar enlaces!`,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, ¡renombrar!',
        cancelButtonText: 'Cancelar'
    });

    if (confirmation.isConfirmed) {
        const formData = new FormData();
        formData.append('oldPath', oldPath);
        formData.append('newPath', newPath);

        try {
            const response = await fetch(base_url + 'Login/renameFileOrDirectory', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            notifi(result.message, result.success ? 'success' : 'error');
            if (result.success) {
                form.reset();
            }
        } catch (error) {
            console.error('Error al renombrar:', error);
            notifi('Error de conexión con el servidor.', 'error');
        }
    }
}




/**
 * Carga la lista de tablas de la base de datos en el modal de exportación.
 */
async function loadDatabaseTables() {
    const container = document.getElementById('tableListContainer');
    container.innerHTML = '<p class="text-muted">Cargando tablas...</p>';

    try {
        const response = await fetch(base_url + 'Login/getDatabaseTables');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            container.innerHTML = ''; // Limpiar el contenedor
            result.data.forEach(table => {
                const div = document.createElement('div');
                div.innerHTML = `<input type="checkbox" id="table_${table}" value="${table}" name="tables[]"> <label for="table_${table}">${table}</label>`;
                container.appendChild(div);
            });

            // Funcionalidad para seleccionar/deseleccionar todas
            document.getElementById('selectAllTables').addEventListener('change', function (e) {
                const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(cb => cb.checked = e.target.checked);
            });

        } else {
            container.innerHTML = `<p class="text-danger">${result.message || 'No se encontraron tablas.'}</p>`;
        }
    } catch (error) {
        console.error('Error al cargar las tablas:', error);
        container.innerHTML = '<p class="text-danger">Error al conectar con el servidor para obtener las tablas.</p>';
    }
}

/**
 * Maneja el evento de clic para eliminar las tablas seleccionadas.
 * @param {Event} e - El evento de clic.
 */
async function handleDeleteTablesSubmit(e) {
    e.preventDefault();
    const selectedTables = Array.from(document.querySelectorAll('#tableListContainer input[type="checkbox"]:checked'))
        .map(cb => cb.value);

    if (selectedTables.length === 0) {
        notifi('Debes seleccionar al menos una tabla para eliminar.', 'warning');
        return;
    }

    // Confirmación de alta seguridad
    const { value: confirmationText } = await Swal.fire({
        title: '¡ACCIÓN IRREVERSIBLE!',
        html: `Estás a punto de <strong>eliminar permanentemente</strong> ${selectedTables.length} tabla(s) de la base de datos. Esta acción no se puede deshacer.<br><br>Para confirmar, escribe la palabra <strong>ELIMINAR</strong> en el campo de abajo.`,
        icon: 'error',
        input: 'text',
        inputPlaceholder: 'Escribe ELIMINAR aquí',
        showCancelButton: true,
        confirmButtonText: 'Confirmar Eliminación',
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR') {
                return 'Debes escribir "ELIMINAR" para confirmar.';
            }
        }
    });

    if (confirmationText === 'ELIMINAR') {
        try {
            const formData = new FormData();
            formData.append('tables', JSON.stringify(selectedTables));

            const response = await fetch(base_url + 'Login/deleteTables', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                notifi(result.message, 'success');
                // Recargar la lista de tablas para reflejar los cambios
                loadDatabaseTables();
            } else {
                notifi(result.message, 'error');
            }

        } catch (error) {
            console.error('Error al eliminar las tablas:', error);
            notifi('Ocurrió un error durante la eliminación.', 'error');
        }
    }
}

/**
 * Maneja el evento de clic para eliminar las tablas seleccionadas.
 * @param {Event} e - El evento de clic.
 */
async function handleDeleteTablesSubmit(e) {
    e.preventDefault();
    const selectedTables = Array.from(document.querySelectorAll('#tableListContainer input[type="checkbox"]:checked'))
        .map(cb => cb.value);

    if (selectedTables.length === 0) {
        notifi('Debes seleccionar al menos una tabla para eliminar.', 'warning');
        return;
    }

    // Confirmación de alta seguridad
    const { value: confirmationText } = await Swal.fire({
        title: '¡ACCIÓN IRREVERSIBLE!',
        html: `Estás a punto de <strong>eliminar permanentemente</strong> ${selectedTables.length} tabla(s) de la base de datos. Esta acción no se puede deshacer.<br><br>Para confirmar, escribe la palabra <strong>ELIMINAR</strong> en el campo de abajo.`,
        icon: 'error',
        input: 'text',
        inputPlaceholder: 'Escribe ELIMINAR aquí',
        showCancelButton: true,
        confirmButtonText: 'Confirmar Eliminación',
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR') {
                return 'Debes escribir "ELIMINAR" para confirmar.';
            }
        }
    });

    if (confirmationText === 'ELIMINAR') {
        try {
            const formData = new FormData();
            formData.append('tables', JSON.stringify(selectedTables));

            const response = await fetch(base_url + 'Login/deleteTables', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                notifi(result.message, 'success');
                // Recargar la lista de tablas para reflejar los cambios
                loadDatabaseTables();
            } else {
                notifi(result.message, 'error');
            }

        } catch (error) {
            console.error('Error al eliminar las tablas:', error);
            notifi('Ocurrió un error durante la eliminación.', 'error');
        }
    }
}

// --- FIN: Funciones para el nuevo modal de configuración ---

/**
 * Llama a la función para forzar el logout y, si tiene éxito, reintenta el login.
 * @param {string} userNick - El nick del usuario a desconectar.
 * @param {FormData} loginFormData - Los datos del formulario para el reintento.
 */
async function forceLogoutAndRetry(userNick, loginFormData) {
    try {
        const formData = new FormData();
        formData.append('userId', userNick);

        const response = await fetch(base_url + 'Login/forceLogout', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.status) {
            notifi('Sesión anterior cerrada. Intentando iniciar sesión de nuevo...', 'info');
            // Reintentar el login automáticamente
            document.getElementById('formLogin').requestSubmit();
        } else {
            notifi(result.msg, 'error');
        }
    } catch (error) {
        notifi('Error al intentar forzar el cierre de sesión.', 'error');
        console.error('Error en forceLogoutAndRetry:', error);
    }
}

/**
 * Maneja el envío del formulario de recuperación de cuenta.
 * @param {Event} e - El evento de envío del formulario.
 */
async function handleRecoverySubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;

    const identifier = formData.get('emailOrUser').trim();
    if (!identifier) {
        notifi('Por favor, ingrese su correo o cédula.', 'warning');
        return;
    }

    // Deshabilitar botón y mostrar spinner
    submitButton.disabled = true;
    submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...`;

    try {
        const response = await fetch(base_url + 'Login/solicitarRecuperacion', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        // Por seguridad, el backend siempre devuelve un mensaje de éxito.
        // Mostramos este mensaje al usuario y reseteamos el formulario.
        if (result.status) {
            Swal.fire({
                title: 'Solicitud Enviada',
                text: result.msg,
                icon: 'success',
                confirmButtonText: 'Entendido'
            });
            form.reset();
        } else {
            // Aunque el backend no debería llegar aquí, manejamos el caso.
            notifi(result.msg, 'error');
        }
    } catch (error) {
        notifi('Error de conexión al enviar la solicitud.', 'error');
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
}
/**
 * Muestra una notificación tipo "toast" utilizando SweetAlert2.
 * @param {string} message - El mensaje a mostrar.
 * @param {string} type - El tipo de icono ('success', 'error', 'warning', 'info').
 */
function notifi(message, type) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
}
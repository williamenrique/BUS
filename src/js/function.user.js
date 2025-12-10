$(document).ready(function () {
    let selectedFile = null

    let usuariosTable = null
    let originalData = {}
    // ===== FUNCIONES DE IMAGEN DE PERFIL =====
    // Lógica específica para la página de PERFIL
    if ($('#profileImageLarge').length > 0) {
        // Evento para abrir el selector de archivos
        $('#profileImageLarge').click(function () {
            $('#profileImageInput').click()
        })

        // Mostrar botones de acción de imagen
        $('#profileImageInput').change(function () {
            const file = this.files[0];
            if (file) {
                $('#imageActions').show();
            }
        });

        // Cancelar cambio de imagen
        $('#cancelImageBtn').click(function () {
            $('#imageActions').hide();
            // Opcional: restaurar la imagen original si se cancela
            const originalSrc = $('#profileImageLarge').data('original-src');
            if (originalSrc) {
                $('#profileImageLarge').attr('src', originalSrc);
            }
            $('#profileImageInput').val(''); // Limpiar el input de archivo
        });

        // Guardar la URL original de la imagen al cargar la página
        $('#profileImageLarge').data('original-src', $('#profileImageLarge').attr('src'));
    }

    // Lógica general de la imagen de perfil (que ya estaba)
    $('#profileImageInput').change(function () {
        const file = this.files[0]
        if (file) {
            selectedFile = file
            const reader = new FileReader()
            reader.onload = function (e) {
                $('#profileImageLarge').attr('src', e.target.result)
                $('#saveImageBtn').show()
            }
            reader.readAsDataURL(file)
        }
    })
    $('#saveImageBtn').click(async function () {
        if (!selectedFile) {
            notifi('No hay imagen seleccionada', 'error')
            return
        }
        try {
            $(this).prop('disabled', true).text('Guardando...')
            const result = await saveImgUser(selectedFile)
            $('#userImageSidebar img').attr('src', $('#profileImageLarge').attr('src'))
            $('.user-menu-btn img').attr('src', $('#profileImageLarge').attr('src'))
            $('.user-image img').attr('src', $('#profileImageLarge').attr('src'))
            $('#removeImageBtn').show()
            $('#saveImageBtn').hide()

            notifi('Imagen de perfil guardada correctamente', 'success')
        } catch (error) {
            console.error('Error al guardar imagen:', error)
            notifi('Error al guardar la imagen', 'error')
        } finally {
            $(this).prop('disabled', false).text('Guardar Imagen')
        }
    })
    $('#removeImageBtn').click(function () {
        const defaultImage = "data:image/svg+xmlbase64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiBzdHJva2U9IiM2NjYiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIj48cGF0aCBkPSJNMjAgMjF2LTJhNCA0IDAgMCAwLTQgNEg4YTQgNCAwIDAgMC00IDR2MiIvPjxjaXJjbGUgcyBjeD0iMTIiIGN5PSI3IiByPSI0Ii8+PC9zdmc+"
        $('#profileImageLarge').attr('src', defaultImage)
        $('#userImageSidebar img').attr('src', defaultImage)
        $('.user-menu-btn img').attr('src', defaultImage)
        $('#profileImageInput').val('')
        $('#removeImageBtn').hide()
        $('#saveImageBtn').hide()
    })
    // ===== FUNCIONES DE CONTRASEÑA =====
    $('#cancelPasswordBtn').click(function () {
        $('#currentPassword, #newPassword, #confirmPassword').val('')
        notifi('Cambios cancelados', 'info')
    })
    $('#savePasswordBtn').click(async function () {
        const currentPassword = $('#currentPassword').val().trim()
        const newPassword = $('#newPassword').val().trim()
        const confirmPassword = $('#confirmPassword').val().trim()

        if (!currentPassword || !newPassword || !confirmPassword) {
            notifi('Todos los campos son obligatorios', 'error')
            return
        }
        if (newPassword !== confirmPassword) {
            notifi('Las contraseñas nuevas no coinciden', 'error')
            return
        }
        if (newPassword.length < 1) {
            notifi('La nueva contraseña debe tener al menos 6 caracteres', 'error')
            return
        }
        if (currentPassword === newPassword) {
            notifi('La nueva contraseña debe ser diferente a la actual', 'error')
            return
        }

        try {
            $(this).prop('disabled', true).text('Guardando...')
            const result = await changePassword(currentPassword, newPassword)
            $('#currentPassword, #newPassword, #confirmPassword').val('')
            notifi('Contraseña cambiada exitosamente', 'success')
        } catch (error) {
            console.error('Error al cambiar contraseña:', error)
            notifi(error.message || 'Error al cambiar la contraseña', 'error')
        } finally {
            $(this).prop('disabled', false).text('Guardar Cambios')
        }
    })
    $('#currentPassword, #newPassword, #confirmPassword').keypress(function (e) {
        if (e.which === 13) {
            $('#savePasswordBtn').click()
        }
    })
    // ===== FUNCIONES DE DATOS DE USUARIO =====
    function saveOriginalData() {
        originalData = {
            usuario_nombres: $('#usuario_nombres').val(),
            usuario_apellidos: $('#usuario_apellidos').val(),
            usuario_email: $('#usuario_email').val(),
            usuario_telefono: $('#usuario_telefono').val(),
            usuario_direccion: $('#usuario_direccion').val()
        }
    }
    saveOriginalData()
    $('#cancelBtn').click(function () {
        $('#usuario_nombres').val(originalData.usuario_nombres)
        $('#usuario_apellidos').val(originalData.usuario_apellidos)
        $('#usuario_email').val(originalData.usuario_email)
        $('#usuario_telefono').val(originalData.usuario_telefono)
        $('#usuario_direccion').val(originalData.usuario_direccion)
        notifi('Cambios cancelados', 'info')
    })
    $('#userDataForm').submit(async function (e) {
        e.preventDefault()
        if (!validateForm()) return
        try {
            $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...')
            const formData = {
                usuario_nombres: $('#usuario_nombres').val().trim(),
                usuario_apellidos: $('#usuario_apellidos').val().trim(),
                usuario_email: $('#usuario_email').val().trim(),
                usuario_telefono: $('#usuario_telefono').val().trim(),
                usuario_direccion: $('#usuario_direccion').val().trim(),
                id_usuario: $('#userDataForm').data('user-id') || 0
            }

            const result = await updateUserData(formData)
            saveOriginalData()

            if (result.userData) {
                notifi('Datos actualizados correctamente', 'success')
            }
        } catch (error) {
            console.error('Error al guardar datos:', error)
            notifi(error.message || 'Error al guardar los datos', 'error')
        } finally {
            $('#saveBtn').prop('disabled', false).html('Guardar Cambios')
        }
    })
    // ===== FUNCIONES DE EDICIÓN Y ESTATUS EN LA TABLA DE USUARIOS =====
    $(document).on('click', '.btn-edit', function () {
        const userId = $(this).data('id')// Verifica si esto funciona ahora
        editUser(userId)
    })

    $(document).on('click', '.btn-status', function () {
        const userId = $(this).data('id')
        const status = $(this).data('status')// Verifica si esto funciona ahora
        changeUserStatus(userId, status)
    })
    // ===== FUNCIONES DE CREACIÓN DE USUARIO =====
    loadSelectOptions()
    $('#userCreateForm').submit(async function (e) {
        e.preventDefault()
        if (!validateCreateForm()) return

        try {
            $('#saveCreateBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...')
            const formData = {
                txtNombre: $('#txtNombre').val().trim(),
                txtApellido: $('#txtApellido').val().trim(),
                txtTelefono: $('#txtTelefono').val().trim(),
                txtDireccion: $('#txtDireccion').val().trim(),
                txtEmail: $('#txtEmail').val().trim(),
                listRolId: $('#listRolId').val(),
                listDep: $('#listDep').val() || 0, // Asegurarse de que listDep tenga un valor
                id_personal_fk: $('#selectPersonal').val() // Enviar el ID del personal desde Select2
            }
            const result = await createUser(formData)
            if (result.success) {
                $('#userCreateForm')[0].reset()
                notifi('Usuario creado correctamente', 'success')
                reloadUsuariosTable()
            } else {
                notifi(result.message, 'error')
            }
        } catch (error) {
            console.error('Error al crear usuario:', error)
            notifi(error.message || 'Error al crear el usuario', 'error')
        } finally {
            $('#saveCreateBtn').prop('disabled', false).html('Crear Usuario')
        }
    })
    $('#cancelCreateBtn').click(function () {
        $('#userCreateForm')[0].reset()
        $('#selectPersonal').val('').trigger('change'); // Resetear Select2
        notifi('Creación de usuario cancelada', 'info')
    })

    // Función para cargar el personal en el select y luego inicializar Select2
    async function loadPersonalForSelect() {
        try {
            const response = await fetch(base_url + 'Personal/getSelectPersonal');
            const optionsHTML = await response.text();
            const selectPersonal = $('#selectPersonal');
            selectPersonal.html(optionsHTML); // Cargar las opciones

            // Ahora inicializar Select2
            selectPersonal.select2({ // Inicializar Select2 después de cargar las opciones
                placeholder: 'Seleccione una persona',
                language: "es",
                dropdownCssClass: 'select2-custom-dropdown', // Clase CSS para controlar el estilo del dropdown
                theme: 'bootstrap4' // Aplicar el tema de Bootstrap 4
            }).on('select2:select', function (e) {
                const personalId = e.params.data.id;
                fillFormWithPersonalData(personalId);
            });
        } catch (error) {
            console.error('Error al cargar la lista de personal:', error);
        }
    }

    loadPersonalForSelect(); // Llamar a la función para que se ejecute

    async function fillFormWithPersonalData(personalId) {
        if (!personalId) return;
        try {
            const response = await fetch(`${base_url}Personal/getPersonalById/${personalId}`);
            const result = await response.json();
            if (result.success && result.data) {
                const personal = result.data;
                notifi('Personal encontrado. Autocompletando formulario.', 'info');
                $('#txtNombre').val(personal.personal_nombre || '');
                $('#txtApellido').val(personal.personal_apellido || '');
                $('#txtTelefono').val(personal.personal_tlf || '');
                $('#txtEmail').val(personal.personal_email || '');
                $('#txtDireccion').val(personal.personal_direccion || '');
            } else {
                notifi(result.message || 'Puede registrar los datos manualmente.', 'warning');
            }
        } catch (error) {
            console.error('Error al buscar datos del personal:', error);
            notifi('Error de conexión al buscar personal.', 'error');
        }
    }
    // --- FIN: Carga de datos para Select2 ---

    // ===== TABLA DE USUARIOS =====
    if ($('#usuariosTable').length) {
        initUsuariosTable()
    }

    // ===== PÁGINA DE RECUPERACIÓN DE CUENTAS =====
    if ($('#recoveryRequestsContainer').length) {
        loadRecoveryRequests();
        setupRequestContainerDelegation();
    }
    window.updateUsuariosTable = function () {
        if (usuariosTable) {
            reloadUsuariosTable()
        }
    }
})
// ===== FUNCIONES GLOBALES =====
async function saveImgUser(imageFile) {
    try {
        const formData = new FormData()
        formData.append('imagen', imageFile)
        formData.append('id_usuario', $('#user').val())
        formData.append('fecha', new Date().toISOString())
        const response = await fetch(base_url + "User/subirImagen/", {
            method: 'POST',
            body: formData
        })
        if (!response.ok) throw new Error(`Error del servidor: ${response.status}`)

        const objData = await response.json()
        if (!objData || !objData.success) throw new Error(objData.message || 'Error al guardar la imagen')
        return objData
    } catch (error) {
        notifi('Error: ' + error.message, 'error')
        throw error
    }
}
async function changePasswordd(currentPassword, newPassword) {
    try {
        const response = await fetch(base_url + "User/cambiarPassword/", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                currentPassword: currentPassword,
                newPassword: newPassword,
                id_usuario: $('#user').val()
            })
        })
        if (!response.ok) throw new Error(`Error del servidor: ${response.status}`)
        const objData = await response.json()
        if (!objData.success) throw new Error(objData.message || 'Error al cambiar la contraseña')
        return objData
    } catch (error) {
        throw error
    }
}
async function changePassword(currentPassword, newPassword) {
    try {
        const { isConfirmed } = await Swal.fire({
            title: '¿Estás seguro?',
            text: "¡Tu contraseña se cambiará de forma permanente!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiarla',
            cancelButtonText: 'Cancelar'
        });

        if (!isConfirmed) {
            return { success: false, message: "Cambio de contraseña cancelado." };
        }

        const response = await fetch(base_url + "User/cambiarPassword/", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                currentPassword: currentPassword,
                newPassword: newPassword,
                id_usuario: $('#user').val()
            })
        });

        if (!response.ok) {
            throw new Error('Error del servidor: ' + response.status);
        }

        const objData = await response.json();

        if (objData.success) {
            await Swal.fire({
                title: '¡Contraseña cambiada!',
                text: objData.message || 'La contraseña se ha actualizado correctamente.',
                icon: 'success'
            });
            return objData;
        } else {
            await Swal.fire({
                title: '¡Error!',
                text: objData.message || 'Error al cambiar la contraseña.',
                icon: 'error'
            });
            return objData;
        }

    } catch (error) {
        await Swal.fire({
            title: '¡Error Fatal!',
            text: 'Ha ocurrido un error inesperado. Por favor, inténtalo de nuevo más tarde.',
            icon: 'error'
        });
        throw error;
    }
}
function validateForm() {
    const nombres = $('#usuario_nombres').val().trim()
    const apellidos = $('#usuario_apellidos').val().trim()
    const email = $('#usuario_email').val().trim()
    const telefono = $('#usuario_telefono').val().trim()
    if (!nombres) {
        notifi('El nombre es obligatorio', 'error')
        $('#usuario_nombres').focus()
        return false
    }
    if (!apellidos) {
        notifi('El apellido es obligatorio', 'error')
        $('#usuario_apellidos').focus()
        return false
    }
    if (!email) {
        notifi('El correo electrónico es obligatorio', 'error')
        $('#usuario_email').focus()
        return false
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email)) {
        notifi('Por favor ingresa un correo electrónico válido', 'error')
        $('#usuario_email').focus()
        return false
    }
    if (telefono && !/^[\d\s\-\+\(\)]{10,15}$/.test(telefono)) {
        notifi('Por favor ingresa un número de teléfono válido', 'error')
        $('#usuario_telefono').focus()
        return false
    }
    return true
}
async function updateUserData(formData) {
    try {
        const response = await fetch(base_url + "User/actualizarDatos/", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        if (!response.ok) throw new Error(`Error del servidor: ${response.status}`)
        const objData = await response.json()
        if (!objData.success) throw new Error(objData.message || 'Error al actualizar los datos')
        return objData
    } catch (error) {
        throw error
    }
}
async function loadSelectOptions() {
    try {
        const rolesResponse = await fetch(base_url + "User/getRoles/")
        const rolesData = await rolesResponse.json()
        if (rolesData.success) {
            const rolSelect = $('#listRolId')
            rolSelect.empty().append('<option value="">Seleccionar rol</option>')
            rolesData.roles.forEach(rol => {
                rolSelect.append(`<option value="${rol.id}">${rol.nombre}</option>`)
            })
        }
        const depsResponse = await fetch(base_url + "User/getDepartments/")
        const depsData = await depsResponse.json()
        if (depsData.success) {
            const depSelect = $('#listDep')
            depSelect.empty().append('<option value="">Seleccionar departamento</option>')
            depsData.departments.forEach(dep => {
                depSelect.append(`<option value="${dep.id}">${dep.nombre}</option>`)
            })
        }
    } catch (error) {
        console.error('Error al cargar opciones:', error)
        notifi('Error al cargar opciones', 'error')
    }
}
function validateCreateForm() {
    const personalSeleccionado = $('#selectPersonal').val();
    const nombre = $('#txtNombre').val().trim()
    const apellido = $('#txtApellido').val().trim()
    const email = $('#txtEmail').val().trim()
    const rol = $('#listRolId').val()
    if (!personalSeleccionado) {
        notifi('Debe buscar y seleccionar una persona', 'error')
        $('#selectPersonal').select2('open');
        return false
    }
    if (!nombre) {
        notifi('El nombre es obligatorio', 'error')
        $('#txtNombre').focus()
        return false
    }
    if (!apellido) {
        notifi('El apellido es obligatorio', 'error')
        $('#txtApellido').focus()
        return false
    }
    if (!email) {
        notifi('El correo electrónico es obligatorio', 'error')
        $('#txtEmail').focus()
        return false
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    if (!emailRegex.test(email)) {
        notifi('Por favor ingresa un correo electrónico válido', 'error')
        $('#txtEmail').focus()
        return false
    }
    if (!rol) {
        notifi('Debe seleccionar un rol', 'error')
        $('#listRolId').focus()
        return false
    }
    return true
}
async function createUser(formData) {
    try {
        const response = await fetch(base_url + "User/setUser/", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        if (!response.ok) throw new Error(`Error del servidor: ${response.status}`)
        const objData = await response.json()
        return objData
    } catch (error) {
        throw error
    }
}
function initUsuariosTable() {
    // Aseguramos que la tabla tenga las clases correctas para que DataTables funcione.
    const tableElement = $('#usuariosTable');
    // 'w-full' (width: 100%) es clave para que sepa a qué ancho adaptarse.
    // 'responsive' y 'display' son las clases que DataTables busca.
    tableElement.addClass('display responsive w-full');

    usuariosTable = $('#usuariosTable').DataTable({
        ajax: {
            url: base_url + "User/getUsuarios/",
            type: "GET",
            dataSrc: "data"
        },
        columns: [
            { data: "usuario_id" },
            { data: "personal_cedula" },
            {
                data: null,
                render: function (data) {
                    return `${data.personal_nombre} ${data.personal_apellido}`
                }
            },
            { data: "usuario_nick" },
            { data: "personal_email" },
            { data: "personal_tlf" },
            { data: "rol_nombre" },
            { data: "departamento_nombre" },
            {
                data: "usuario_status",
                render: function (data) {
                    return data == 1
                        ? '<span class="badge badge-success">Activo</span>'
                        : '<span class="badge badge-danger">Inactivo</span>'
                }
            },
            {
                data: null,
                render: function (data) {
                    return `
                        <div class="btn-group btn-group-sm" role="group" aria-label="Acciones de usuario">
                            <button type="button" class="btn btn-primary btn-edit" data-id="${data.usuario_id}" title="Editar Usuario">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn ${data.usuario_status == 1 ? 'btn-danger' : 'btn-success'} btn-status" data-id="${data.usuario_id}" data-status="${data.usuario_status}" title="${data.usuario_status == 1 ? 'Desactivar Usuario' : 'Activar Usuario'}">
                                ${data.usuario_status == 1 ? '<i class="fas fa-ban"></i>' : '<i class="fas fa-check"></i>'}
                            </button>
                        </div>
                    `
                },
                orderable: false
            }
        ],
        language: { url: base_url + 'src/plugins/js/es_es.json' },
        responsive: true,
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50],
        order: [[0, "desc"]],
        dom: '<"flex justify-between items-center mb-4"<"text-xl font-bold">f>rt<"flex justify-between items-center mt-4"lip>',
    })
}
function reloadUsuariosTable() {
    if (usuariosTable) {
        usuariosTable.ajax.reload(null, false)
    }
}
async function editUser(userId) {
    try {
        const response = await fetch(base_url + "User/getUsuario/" + userId)
        const result = await response.json()
        if (result.success) {
            showEditModal(result.usuario)
        } else {
            notifi('Error al cargar datos del usuario', 'error')
        }
    } catch (error) {
        console.error('Error:', error)
        notifi('Error al cargar datos del usuario', 'error')
    }
}
function showEditModal(userData) {
    // Llenar el formulario del modal estático
    $('#editUsuarioId').val(userData.usuario_id);
    $('#editUsuarioCi').val(userData.personal_cedula);
    $('#editUsuarioNombres').val(userData.personal_nombre);
    $('#editUsuarioApellidos').val(userData.personal_apellido);
    $('#editUsuarioEmail').val(userData.personal_email);
    $('#editUsuarioTelefono').val(userData.personal_tlf || '');
    $('#editUsuarioDireccion').val(userData.personal_direccion || '');

    // Llenar y seleccionar el estado
    const statusSelect = $('#editUsuarioStatus');
    statusSelect.empty();
    statusSelect.append(`<option value="1" ${userData.usuario_status == 1 ? 'selected' : ''}>Activo</option>`);
    statusSelect.append(`<option value="0" ${userData.usuario_status == 0 ? 'selected' : ''}>Inactivo</option>`);

    // Cargar opciones de roles y departamentos
    loadEditSelectOptions(userData);

    // Configurar eventos para el modal
    setupEditModalEvents();

    // Mostrar el modal de Bootstrap
    $('#editUserModal').modal('show');
}
async function loadEditSelectOptions(userData) {
    try {
        const rolesResponse = await fetch(base_url + "User/getRoles")
        const rolesData = await rolesResponse.json()
        if (rolesData.success) {
            const rolSelect = $('#editUsuarioRolId')
            rolSelect.empty().append('<option value="">Seleccionar rol</option>')
            rolesData.roles.forEach(rol => {
                rolSelect.append(`<option value="${rol.id}" ${userData.usuario_rol_id == rol.id ? 'selected' : ''}>${rol.nombre}</option>`)
            })
        }
        const depsResponse = await fetch(base_url + "User/getDepartments")
        const depsData = await depsResponse.json()
        if (depsData.success) {
            const depSelect = $('#editUsuarioDepartamentoId')
            depSelect.empty().append('<option value="">Seleccionar departamento</option>')
            depsData.departments.forEach(dep => {
                depSelect.append(`<option value="${dep.id}" ${userData.usuario_departamento_id == dep.id ? 'selected' : ''}>${dep.nombre}</option>`)
            })
        }
    } catch (error) {
        console.error('Error al cargar opciones:', error)
    }
}
function setupEditModalEvents() {
    // Desvincular eventos anteriores para evitar múltiples llamadas
    $('#saveEditBtn').off('click');
    // Vincular el nuevo evento
    $('#saveEditBtn').on('click', saveUserChanges)
}
function closeEditModal() {
    $('#editUserModal').modal('hide');
}
async function saveUserChanges() {
    try {
        const formData = {
            usuario_id: $('#editUsuarioId').val(),
            personal_cedula: $('#editUsuarioCi').val(),
            personal_nombre: $('#editUsuarioNombres').val(),
            personal_apellido: $('#editUsuarioApellidos').val(),
            personal_email: $('#editUsuarioEmail').val(),
            personal_tlf: $('#editUsuarioTelefono').val(),
            personal_direccion: $('#editUsuarioDireccion').val(),
            usuario_rol_id: $('#editUsuarioRolId').val(),
            usuario_departamento_id: $('#editUsuarioDepartamentoId').val(),
            usuario_status: $('#editUsuarioStatus').val()
        }
        $('#saveEditBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...')
        const response = await fetch(base_url + "User/updateUsuario/", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        })
        const result = await response.json()
        if (result.success) {
            notifi('Usuario actualizado correctamente', 'success')
            closeEditModal()
            reloadUsuariosTable()
        } else {
            notifi(result.message || 'Error al actualizar usuario', 'error')
        }
    } catch (error) {
        console.error('Error:', error)
        notifi('Error al actualizar usuario', 'error')
    } finally {
        $('#saveEditBtn').prop('disabled', false).html('Guardar Cambios')
    }
}
async function changeUserStatus(userId, currentStatus) {
    try {
        const newStatus = currentStatus == 1 ? 0 : 1;
        const actionText = newStatus == 1 ? 'activar' : 'desactivar';
        const actionTitle = newStatus == 1 ? '¿Está seguro de activar este usuario?' : '¿Está seguro de desactivar este usuario?';
        const actionColor = newStatus == 1 ? '#28a745' : '#dc3545';

        const { isConfirmed } = await Swal.fire({
            title: actionTitle,
            text: `El estado del usuario cambiará a ${actionText}.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: actionColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Sí, ${actionText}lo`,
            cancelButtonText: 'Cancelar'
        });

        if (!isConfirmed) {
            return;
        }

        const response = await fetch(base_url + "User/updateStatus/", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                usuario_id: userId,
                usuario_status: newStatus
            })
        });

        const result = await response.json();

        // --- Use a toast notification for success and error messages ---
        if (result.success) {
            Swal.fire({
                toast: true,
                position: 'top-end', // Position the toast in the top right corner
                icon: 'success',
                title: result.message || `El estado del usuario se ha ${actionText} correctamente.`,
                showConfirmButton: false, // Don't show a button to close the toast
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
            reloadUsuariosTable();
        } else {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: result.message || 'Error al cambiar el estado del usuario.',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer);
                    toast.addEventListener('mouseleave', Swal.resumeTimer);
                }
            });
        }
        // -----------------------------------------------------------------

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            title: '¡Error!',
            text: 'Ha ocurrido un error inesperado al procesar la solicitud.',
            icon: 'error'
        });
    }
}

function setupRequestContainerDelegation() {
    // Delegación de eventos para el botón de resolver
    $('#recoveryRequestsContainer').on('click', '.btn-resolve-request', function () {
        const requestId = $(this).data('id');
        resolveRequest(requestId);
    });

    // Delegación de eventos para el botón de mostrar/ocultar contraseña
    $('#recoveryRequestsContainer').on('click', '.toggle-password-visibility', function () {
        const passwordSpan = $(this).siblings('.password-text');
        const isHidden = passwordSpan.data('hidden');

        if (isHidden) {
            passwordSpan.text(passwordSpan.data('password'));
            $(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordSpan.text('••••••••');
            $(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');
        }
        passwordSpan.data('hidden', !isHidden);
    });
}

async function resolveRequest(id) {
    const { isConfirmed } = await Swal.fire({
        title: '¿Confirmar Resolución?',
        text: "Esto marcará la solicitud como resuelta y la eliminará de la lista. Asegúrate de haber contactado al usuario.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, resolver',
        cancelButtonText: 'Cancelar'
    });

    if (isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('id', id);

            const response = await fetch(base_url + "User/resolverSolicitud", {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                notifi('Solicitud resuelta y eliminada.', 'success');
                loadRecoveryRequests(); // Recargar las tarjetas para actualizar la vista
                if (typeof loadAdminNotifications === 'function') {
                    loadAdminNotifications(); // Actualizar el contador de la campana
                }
            }
        } catch (error) {
            console.error('Error resolving request:', error);
            notifi('Error al procesar la solicitud.', 'error');
        }
    }
}

// ===== FUNCIONES PARA LA PÁGINA DE RECUPERACIÓN =====

async function loadRecoveryRequests() {
    const container = $('#recoveryRequestsContainer');
    const noRequestsMessage = $('#noRequestsMessage');
    container.html('<div class="col-span-full text-center py-10"><i class="fas fa-spinner fa-spin fa-3x text-blue-500"></i><p class="mt-2 text-gray-500">Cargando solicitudes...</p></div>');

    try {
        const response = await fetch(base_url + "User/getPendingRequests");
        const result = await response.json();

        container.empty();

        if (result.success && result.data.length > 0) {
            noRequestsMessage.hide(); // Usamos .hide() para asegurar que se oculte
            result.data.forEach(request => {
                const cardHtml = createRecoveryCard(request);
                container.append(cardHtml);
            });
        } else {
            noRequestsMessage.show(); // Usamos .show() para asegurar que se muestre
        }
    } catch (error) {
        console.error('Error loading recovery requests:', error);
        container.html('<div class="col-span-full text-center py-10 bg-red-100 text-red-700 rounded-lg"><i class="fas fa-exclamation-triangle fa-3x mb-4"></i><p>Error al cargar las solicitudes.</p></div>');
    }
}


function createRecoveryCard(request) {
    const requestDate = new Date(request.request_date).toLocaleString('es-VE', {
        day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit'
    });

    return `
        <div id="request-card-${request.id}" class="info-box bg-light shadow-sm">
            <span class="info-box-icon bg-warning"><i class="fas fa-user-lock"></i></span>
            <div class="info-box-content">
                <div class="flex justify-between items-start">
                    <div>
                        <span class="info-box-text font-weight-bold">${request.personal_nombre} ${request.personal_apellido}</span>
                        <span class="info-box-number text-sm text-muted">${request.rol_nombre}</span>
                    </div>
                    <span class="badge badge-warning">${requestDate}</span>
                </div>

                <div class="mt-2 text-xs">
                    <p class="mb-1"><i class="fas fa-id-card fa-fw mr-2"></i><strong>C.I:</strong> ${request.personal_cedula}</p>
                    <p class="mb-1"><i class="fas fa-user-tag fa-fw mr-2"></i><strong>Nick:</strong> ${request.usuario_nick}</p>
                    <div class="d-flex align-items-center mb-1">
                        <i class="fas fa-key fa-fw mr-2"></i><strong>Clave:</strong>
                        <span class="password-text ml-2 font-monospace" data-password="${request.usuario_password}" data-hidden="true">••••••••</span>
                        <button class="toggle-password-visibility btn btn-xs btn-link text-secondary ml-1 p-0"><i class="fas fa-eye"></i></button>
                    </div>
                    <p class="mb-0"><i class="fas fa-keyboard fa-fw mr-2"></i><strong>Solicitó con:</strong> ${request.identifier_provided}</p>
                </div>
                <button class="btn btn-xs btn-success btn-resolve-request position-absolute" style="bottom: 5px; right: 5px;" data-id="${request.id}">
                    <i class="fas fa-check-circle mr-2"></i>Marcar como Resuelto
                </button>
            </div>
        </div>
    `;
}
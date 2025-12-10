/**
 * Archivo: function.personal.js
 * Descripción: Lógica de JavaScript para la gestión de Personal.
 *              Incluye DataTable, CRUD con fetch, modales y notificaciones.
 */

let tablePersonal;

/**
 * Se ejecuta cuando el DOM está completamente cargado.
 * Punto de entrada para la inicialización de la página de Personal.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Inicializa la DataTable para mostrar el listado de personal
    tablePersonal = $('#tablePersonal').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": {
            "url": base_url + "src/plugins/js/es_es.json"
        },
        "ajax": {
            "url": base_url + "Personal/getPersonal",
            "dataSrc": ""
        },
        "columns": [
            { "data": "personal_cedula" },
            { "data": "personal_nombre" },
            { "data": "cargo" },
            { "data": "personal_tlf" },
            { "data": "personal_email" },
            { "data": "personal_status", "className": "text-center" },
            { "data": "acciones", "orderable": false, "className": "text-center" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    });

    // Carga los cargos disponibles en el select del formulario
    loadCargos();

    const formPersonal = document.querySelector("#formPersonal");
    const btnCancel = document.querySelector("#btnCancel");

    // Evento de envío del formulario para crear o actualizar personal
    formPersonal.onsubmit = async function (e) {
        e.preventDefault();

        const intIdentificacion = document.querySelector('#txtIdentificacion').value;
        const strNombre = document.querySelector('#txtNombre').value;
        const strApellido = document.querySelector('#txtApellido').value;
        const intlistRolId = document.querySelector('#listCargo').value;

        // Validación de campos obligatorios
        if (intIdentificacion.trim() === '' || strNombre.trim() === '' || strApellido.trim() === '' || intlistRolId === '0') {
            notifi("Cédula, Nombre y Cargo son obligatorios.", "warning");
            return;
        }

        try {
            const formData = new FormData(formPersonal);

            // Convertir a mayúsculas antes de enviar
            formData.set('txtNombre', formData.get('txtNombre').toUpperCase());
            formData.set('txtApellido', formData.get('txtApellido').toUpperCase());
            formData.set('txtDireccion', formData.get('txtDireccion').toUpperCase());

            const url = base_url + 'Personal/setPersonal';
            // Petición asíncrona para guardar/actualizar
            const response = await fetch(url, { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                resetForm();
                notifi(data.message, "success");
                tablePersonal.ajax.reload(); // Recargar la tabla para mostrar los cambios
            } else {
                notifi(data.message, "error");
            }
        } catch (error) {
            console.error('Error:', error);
            notifi("Ocurrió un error en la operación. Intente de nuevo.", "error");
        }
    };

    // Evento para el botón de cancelar, que resetea el formulario
    btnCancel.addEventListener('click', resetForm);
});

/**
 * Carga los cargos desde el controlador y los puebla en el select.
 * Utiliza fetch para una petición asíncrona.
 */
async function loadCargos() {
    try {
        const url = base_url + 'Personal/getSelectCargo';
        const response = await fetch(url);
        const data = await response.text();
        document.querySelector("#listCargo").innerHTML = data; // Inserta el HTML de las opciones
    } catch (error) {
        console.error("Error al cargar cargos:", error);
    }
}

/**
 * Resetea el formulario a su estado inicial para un nuevo registro.
 * Limpia campos, restaura títulos y oculta el botón de cancelar.
 */
function resetForm() {
    document.querySelector("#idPersonal").value = "";
    document.querySelector("#formTitle").innerHTML = '<i class="fas fa-user-plus"></i> Registrar Nuevo Personal';
    document.querySelector("#btnText").innerHTML = "Guardar";
    document.querySelector("#formPersonal").reset();
    document.querySelector("#btnCancel").style.display = 'none'; // Oculta el botón de cancelar
    document.querySelector('#txtIdentificacion').removeAttribute('readonly'); // Permite editar la cédula
}

/**
 * Muestra los detalles de un miembro del personal en un modal.
 * @param {number} id_personal - El ID del personal.
 */
async function fntViewPersonal(id_personal) {
    try {
        const url = `${base_url}Personal/getPersonalById/${id_personal}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const personal = result.data;
            // Mapeo de valores numéricos a texto legible
            const tagMap = { 1: 'INFORMATICA', 2: 'ALMACEN', 0: 'N/A' };
            const statusMap = { 1: 'Activo', 0: 'Inactivo', 2: 'Vacaciones', 3: 'Reposo' };

            // Llenar el modal con los datos
            document.querySelector("#viewCedula").textContent = personal.personal_cedula;
            document.querySelector("#viewNombre").textContent = `${personal.personal_nombre} ${personal.personal_apellido}`;
            document.querySelector("#viewCargo").textContent = personal.cargo;
            document.querySelector("#viewDireccion").textContent = personal.personal_direccion || 'N/A';
            document.querySelector("#viewEmail").textContent = personal.personal_email || 'N/A';
            document.querySelector("#viewTelefono").textContent = personal.personal_tlf || 'N/A';
            document.querySelector("#viewTag").textContent = tagMap[personal.personal_tag] || 'N/A';
            document.querySelector("#viewStatus").textContent = statusMap[personal.personal_status] || 'Desconocido';

            // Mostrar el modal de Bootstrap
            $('#modalViewPersonal').modal('show');
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error:', error);
        notifi("Ocurrió un error al obtener los datos.", "error");
    }
}

/**
 * Prepara el formulario para editar un miembro del personal.
 * Obtiene los datos del servidor y los carga en el formulario de registro.
 * @param {number} id_personal - El ID del personal a editar.
 */
async function fntEditPersonal(id_personal) {
    try {
        const url = `${base_url}Personal/getPersonalById/${id_personal}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const personal = result.data;
            // Cambiar textos y visibilidad de botones para modo edición
            document.querySelector("#formTitle").innerHTML = '<i class="fas fa-user-edit"></i> Actualizar Personal';
            document.querySelector("#btnText").innerHTML = "Actualizar";
            document.querySelector("#btnCancel").style.display = 'inline-block';

            // Llenar el formulario con los datos existentes
            document.querySelector("#idPersonal").value = personal.id_personal;
            document.querySelector("#txtIdentificacion").value = personal.personal_cedula;
            document.querySelector("#txtIdentificacion").setAttribute('readonly', true); // Cédula no se edita
            document.querySelector("#txtNombre").value = personal.personal_nombre;
            document.querySelector("#txtApellido").value = personal.personal_apellido;
            document.querySelector("#listCargo").value = personal.personal_cargo;
            document.querySelector("#txtTelefono").value = personal.personal_tlf;
            document.querySelector("#txtEmail").value = personal.personal_email;
            document.querySelector("#txtDireccion").value = personal.personal_direccion;
            document.querySelector("#listTagPersonal").value = personal.personal_tag;
            document.querySelector("#listStatus").value = personal.personal_status;

            window.scrollTo({ top: 0, behavior: 'smooth' }); // Mover al inicio de la página para ver el formulario
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error:', error);
        notifi("Ocurrió un error al cargar los datos para editar.", "error");
    }
}

/**
 * Elimina un miembro del personal (eliminación lógica).
 * @param {number} id_personal - El ID del personal a eliminar.
 */
function fntDelPersonal(id_personal) {
    Swal.fire({
        title: 'Eliminar Personal',
        text: "¿Realmente quiere eliminar a este miembro del personal?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('idPersonal', id_personal);

                const url = base_url + 'Personal/delPersonal';
                const response = await fetch(url, { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    notifi(data.message, "success");
                    tablePersonal.ajax.reload();
                } else {
                    notifi(data.message, "error");
                }
            } catch (error) {
                console.error('Error:', error);
                notifi("Ocurrió un error al intentar eliminar.", "error");
            }
        }
    });
}

/**
 * Cambia el estado de un miembro del personal.
 * Utiliza SweetAlert2 para un flujo de confirmación en dos pasos (seleccionar estado y dar motivo).
 * @param {number} id_personal - El ID del personal.
 */
function fntStatusPersonal(id_personal) {
    // Primer paso: seleccionar el nuevo estado
    Swal.fire({
        title: 'Cambiar Estado del Personal',
        text: 'Seleccione el nuevo estado:',
        input: 'select',
        inputOptions: {
            '1': 'Activo',
            '0': 'Inactivo',
            '2': 'Vacaciones',
            '3': 'Reposo'
        },
        showCancelButton: true,
        confirmButtonText: 'Siguiente &rarr;',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Segundo paso: pedir el motivo del cambio
            const newStatus = result.value;
            Swal.fire({
                title: 'Motivo del Cambio',
                input: 'textarea',
                inputPlaceholder: 'Escriba una breve observación sobre el cambio de estado...',
                showCancelButton: true,
                confirmButtonText: 'Confirmar Cambio',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) {
                        return '¡Necesita escribir un motivo!';
                    }
                }
            }).then(async (textResult) => {
                if (textResult.isConfirmed) {
                    // Tercer paso: enviar los datos al servidor
                    const motivo = textResult.value;
                    try {
                        const formData = new FormData();
                        formData.append('idPersonal', id_personal);
                        formData.append('idStatus', newStatus);
                        formData.append('srtText', motivo);

                        const url = base_url + 'Personal/setStatusPersonal';
                        const response = await fetch(url, { method: 'POST', body: formData });
                        const data = await response.json();

                        if (data.success) {
                            notifi(data.message, "success");
                            tablePersonal.ajax.reload(); // Recargar la tabla
                        } else {
                            notifi(data.message, "error");
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        notifi("Ocurrió un error en la operación.", "error");
                    }
                }
            });
        }
    });
}
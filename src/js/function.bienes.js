/**
 * Archivo: function.bienes.js
 * Descripción: Contiene toda la lógica de JavaScript para la gestión de bienes (activos),
 *              incluyendo la inicialización de la tabla, operaciones CRUD (Crear, Leer,
 *              Actualizar, Eliminar) a través de peticiones asíncronas (fetch),
 *              y el manejo de modales con SweetAlert2.
 * Autor: [Tu Nombre]
 * Fecha: [Fecha Actual]
 */

// Variable global para la instancia de la DataTable
let tableBienes;
let departamentosData = []; // Variable global para almacenar los departamentos

/**
 * Se ejecuta cuando el contenido del DOM ha sido completamente cargado.
 * Es el punto de entrada principal para la inicialización de la página.
 */
document.addEventListener('DOMContentLoaded', function () {
    // Selección de elementos del DOM para un acceso más eficiente
    const formBien = document.querySelector("#formBien");


    // Inicializa la DataTable con configuraciones específicas
    tableBienes = $('#tableBienes').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": {
            "url": base_url + "src/plugins/js/es_es.json"
        },
        "ajax": {
            "url": base_url + "Bienes/getBienes",
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_bien" },
            { "data": "descripcion_bien" },
            { "data": "departamento_bien" },
            { "data": "grupo" },
            { "data": "subgrupo" },
            { "data": "seccion" },
            { "data": "status_bien" },
            { "data": "acciones" }
        ],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
    });

    // Carga los datos iniciales para los selects del formulario
    loadFormSelects();

    // --- INICIO: Event Listeners para botones PDF ---
    document.querySelector('#btnPdfGeneral').addEventListener('click', async function () {
        notifi("Generando PDF general, por favor espere...", "info");
        try {
            const response = await fetch(base_url + 'Bienes/generarPdfGeneral');
            const result = await response.json();
            if (result.success) {
                fntGenerarBienesPDF(result.data, "Reporte General de Bienes");
            } else {
                notifi(result.message, "error");
            }
        } catch (error) {
            notifi("Error al solicitar el reporte general.", "error");
        }
    });

    document.querySelector('#btnPdfDepto').addEventListener('click', async function () {
        const deptoId = document.querySelector('#listDeptoPDF').value;
        if (!deptoId) {
            notifi("Por favor, seleccione un departamento.", "warning");
            return;
        }
        notifi("Generando PDF por departamento, por favor espere...", "info");
        fntGenerarPdfPorDepto(deptoId);
    });

    // --- INICIO: Event Listener para exportar por búsqueda ---
    const btnExportarBusqueda = document.querySelector('#btnExportarBusqueda');
    if (btnExportarBusqueda) {
        btnExportarBusqueda.addEventListener('click', async function () {
            const termino = document.querySelector('#txtBusquedaBien').value.trim();
            if (termino === '') {
                notifi("Por favor, ingrese un término de búsqueda.", "warning");
                return;
            }
            notifi("Generando PDF de la búsqueda, por favor espere...", "info");
            fntGenerarPdfPorBusqueda(termino);
        });
    }
    // --- FIN: Event Listeners para botones PDF ---

    // --- INICIO: Event Listener para filtrar la tabla en tiempo real ---
    const txtBusquedaBien = document.querySelector('#txtBusquedaBien');
    if (txtBusquedaBien) {
        txtBusquedaBien.addEventListener('keyup', function () {
            tableBienes.search(this.value).draw();
        });
    }
    // --- FIN: Event Listener para filtrar la tabla en tiempo real ---

    // Evento de envío del formulario para crear o actualizar un bien
    formBien.addEventListener('submit', async function (e) {
        e.preventDefault();
        const idBien = document.querySelector("#id_bien").value;
        const descripcion = document.querySelector("#descripcion").value;
        const departamento = document.querySelector("#departamento").value;
        const grupo = document.querySelector("#grupo").value;
        const subgrupo = document.querySelector("#subgrupo").value;
        const seccion = document.querySelector("#seccion").value;
        const status = document.querySelector("#status_bien").value;

        if (descripcion === '' || departamento === '' || grupo === '' || subgrupo === '' || seccion === '' || status === '') {
            notifi("Todos los campos con asterisco son obligatorios.", "warning");
            return false;
        }

        try {
            const formData = new FormData(formBien);
            const response = await fetch(base_url + 'Bienes/setBien', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                closeModal();
                tableBienes.ajax.reload();
                notifi(result.msg, "success");
            } else {
                notifi(result.msg, "error");
            }
        } catch (error) {
            notifi("Ocurrió un error en la operación.", "error");
        }
    });
});

/**
 * Carga de forma asíncrona los datos para los menús desplegables (selects) del formulario.
 * Esto evita tener que cargar los datos con PHP en la vista, haciendo la carga inicial más rápida.
 */
async function loadFormSelects() {
    try {
        const url = base_url + 'Bienes/getInitialData';
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const { departamentos, grupos, subgrupos, secciones } = result.data;

            // Guardamos los departamentos en la variable global para usarlos después
            departamentosData = departamentos;

            populateSelect('departamento', departamentos, 'depatamento_bien_id', 'departamento_bien');
            populateSelect('grupo', grupos, 'id_grupo', 'grupo');
            populateSelect('subgrupo', subgrupos, 'subgrupo_id', 'subgrupo');
            populateSelect('seccion', secciones, 'seccion_id', 'seccion');
            populateSelect('listDeptoQR', departamentos, 'depatamento_bien_id', 'departamento_bien'); // Añadido para el modal de QR

            // --- INICIO DE LA CORRECCIÓN ---
            // Poblar el select del PDF aquí también para evitar condiciones de carrera.
            // La tabla puede tardar en inicializarse, pero los datos ya estarán listos.
            populateSelect('listDeptoPDF', departamentos, 'depatamento_bien_id', 'departamento_bien');
            // --- FIN DE LA CORRECCIÓN ---
        }
    } catch (error) {
        console.error("Error al cargar datos para los selects:", error);
    }
}

/**
 * Abre el modal para agregar un nuevo bien.
 * Limpia el formulario y ajusta los textos del modal.
 */
function openModal() {
    document.querySelector('#id_bien').value = "";
    document.querySelector('#titleModal').innerHTML = "Nuevo Bien";
    document.querySelector('#btnActionText').innerHTML = "Guardar";
    document.querySelector("#formBien").reset();
    $('#modalFormBien').modal('show');
}

/**
 * Cierra el modal de formulario de bienes.
 */
function closeModal() {
    $('#modalFormBien').modal('hide');
}

/**
 * Obtiene los datos de un bien específico para editarlo.
 * @param {number} id_bien - El ID del bien a editar.
 */
async function fntEditBien(id_bien) {
    document.querySelector('#formBien').reset();
    document.querySelector('#titleModal').innerHTML = "Actualizar Bien";
    document.querySelector('#btnActionText').innerHTML = "Actualizar";

    try {
        const url = `${base_url}Bienes/getBien/${id_bien}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const bien = result.data;
            // Llenar el formulario con los datos del bien
            document.querySelector("#id_bien").value = bien.id_bien;
            document.querySelector("#descripcion").value = bien.descripcion_bien;
            document.querySelector("#departamento").value = bien.bien_depatamento_id;
            document.querySelector("#grupo").value = bien.grupo_id;
            document.querySelector("#subgrupo").value = bien.subgrupo_id;
            document.querySelector("#seccion").value = bien.seccion_id;
            document.querySelector("#status_bien").value = bien.status_bien;

            // La fecha ya viene formateada desde el controlador
            document.querySelector("#fecha_adquisicion").value = bien.fecha_adquisicion;

            $('#modalFormBien').modal('show');
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error:', error);
        notifi("Ocurrió un error al obtener los datos del bien.", "error");
    }
}

/**
 * Elimina un bien después de una confirmación.
 * @param {number} id_bien - El ID del bien a eliminar.
 */
function fntDelBien(id_bien) {
    Swal.fire({
        title: 'Eliminar Bien',
        text: "¿Realmente quieres eliminar este bien?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('id_bien', id_bien);

                const url = base_url + 'Bienes/delBien';
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    notifi(data.message, "success");
                    tableBienes.ajax.reload();
                } else {
                    notifi(data.message, "error");
                }
            } catch (error) {
                console.error('Error:', error);
                notifi("Ocurrió un error en la operación de eliminación.", "error");
            }
        }
    });
}

/**
 * Función auxiliar reutilizable para poblar elementos <select>.
 * @param {string} selectId - El ID del elemento select.
 * @param {Array} data - El array de objetos para las opciones.
 * @param {string} valueField - El nombre de la propiedad para el `value` de la opción.
 * @param {string} textField - El nombre de la propiedad para el texto de la opción.
 */
function populateSelect(selectId, data, valueField, textField) {
    const select = document.querySelector(`#${selectId}`);
    if (select) {
        select.innerHTML = `<option value="">Seleccionar</option>`;
        data.forEach(item => {
            select.innerHTML += `<option value="${item[valueField]}">${item[textField]}</option>`;
        });
    }
}

/**
 * Función para generar el PDF de bienes.
 * @param {Object} data - Los datos de los bienes.
 * @param {string} titulo - El título del reporte.
 */
function fntGenerarBienesPDF(data, titulo) {
    if (!data) {
        notifi("No hay datos para generar el PDF.", "error");
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/bienes/reporte_bienes.php";
    form.target = '_blank';

    const inputData = document.createElement('input');
    inputData.type = 'hidden';
    inputData.name = 'reporteData';
    inputData.value = JSON.stringify(data);

    const inputTitulo = document.createElement('input');
    inputTitulo.type = 'hidden';
    inputTitulo.name = 'reporteTitulo';
    inputTitulo.value = titulo;

    form.appendChild(inputData);
    form.appendChild(inputTitulo);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Obtiene los datos y genera el PDF para un departamento específico.
 * @param {number} deptoId - El ID del departamento.
 */
async function fntGenerarPdfPorDepto(deptoId) {
    // --- INICIO DE LA CORRECCIÓN ---
    // Se cambia el método a POST y se envía el ID en el cuerpo del formulario
    // para que coincida con la forma en que el controlador espera los datos.
    try {
        const formData = new FormData();
        formData.append('idDepartamento', deptoId);

        const response = await fetch(`${base_url}Bienes/generarPdfPorDepartamento`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            fntGenerarBienesPDF(result.data, `Reporte de Bienes - ${result.departamento}`);
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        notifi("Error al solicitar el reporte por departamento.", "error");
    }
    // --- FIN DE LA CORRECCIÓN ---
}

/**
 * Obtiene los datos y genera el PDF para un término de búsqueda específico.
 * @param {string} termino - El término de búsqueda.
 */
async function fntGenerarPdfPorBusqueda(termino) {
    try {
        const formData = new FormData();
        formData.append('terminoBusqueda', termino);

        const response = await fetch(`${base_url}Bienes/generarPdfPorBusqueda`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            // Usamos la misma función de generación de PDF, pero con un título dinámico
            fntGenerarBienesPDF(result.data, `Reporte de Búsqueda: "${result.termino}"`);
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error al solicitar el reporte por búsqueda:', error);
        notifi("Error al solicitar el reporte por búsqueda.", "error");
    }
}


/**
 * Funciones para el modal de QR (a implementar si es necesario).
 * Estas funciones se dejan como plantilla para la futura implementación de la
 * generación de códigos QR.
 */
function openModalQR() {
    // Lógica para abrir el modal de QR
    $('#modalQR').modal('show');
}

function closeModalQR() {
    const qrResultDiv = document.querySelector('#qrResult');
    const qrcodeDiv = document.querySelector('#qrcode');
    const deptoSelect = document.querySelector('#listDeptoQR');

    // Limpiar el contenido del QR y los botones de acción
    qrcodeDiv.innerHTML = '';
    const existingLinks = qrResultDiv.querySelector('.qr-actions');
    if (existingLinks) {
        existingLinks.remove();
    }

    // Ocultar el resultado y resetear el select
    qrResultDiv.classList.add('d-none');
    deptoSelect.value = '';

    // Ocultar el modal
    $('#modalQR').modal('hide');
}

function generarQR() {
    const deptoSelect = document.querySelector('#listDeptoQR');
    const selectedDeptoId = deptoSelect.value;
    const qrResultDiv = document.querySelector('#qrResult');
    const qrcodeDiv = document.querySelector('#qrcode');

    // 1. Validar que se haya seleccionado un departamento
    if (!selectedDeptoId) {
        notifi("Por favor, selecciona un departamento.", "warning");
        qrResultDiv.classList.add('d-none'); // Ocultar si no hay selección
        return;
    }

    // 2. Construir la URL que se codificará en el QR
    const urlToEncode = `${base_url}data/bienes/bienes_qr.php?departamento_id=${selectedDeptoId}`;

    // 3. Limpiar cualquier QR y enlace anterior
    qrcodeDiv.innerHTML = '';
    const existingLinks = qrResultDiv.querySelector('.qr-actions');
    if (existingLinks) {
        existingLinks.remove();
    }

    // 4. Generar el nuevo código QR usando la librería qrcode.js
    try {
        new QRCode(qrcodeDiv, {
            text: urlToEncode,
            width: 220,
            height: 220,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // 5. Crear y mostrar botones de acción (Descargar y Ver)
        setTimeout(() => {
            const qrImage = qrcodeDiv.querySelector('img');
            const departamentoNombre = deptoSelect.options[deptoSelect.selectedIndex].text;
            if (qrImage) {
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'mt-3 flex justify-center gap-2 qr-actions'; // Clase para fácil selección
                buttonContainer.innerHTML = `
                    <a href="${qrImage.src}" download="QR_${departamentoNombre.replace(/\s+/g, '_')}.png" class="bg-green-500 hover:bg-green-600 text-green font-bold py-2 px-4 rounded-md transition-colors flex items-center text-sm">
                        <i class="fas fa-download mr-2"></i> Descargar
                    </a>
                    <a href="${urlToEncode}" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-red font-bold py-2 px-4 rounded-md transition-colors flex items-center text-sm">
                        <i class="fas fa-external-link-alt mr-2"></i> Ver Tabla
                    </a>
                `;
                qrResultDiv.appendChild(buttonContainer);
            }
        }, 100); // Pequeño timeout para asegurar que la imagen del QR se haya renderizado

        // 6. Mostrar el resultado
        qrResultDiv.classList.remove('d-none');
        notifi("Código QR generado correctamente.", "success");
    } catch (error) {
        console.error("Error al generar el QR:", error);
        notifi("No se pudo generar el código QR. Asegúrate de que la librería qrcode.js esté cargada.", "error");
        qrResultDiv.classList.add('d-none');
    }
}
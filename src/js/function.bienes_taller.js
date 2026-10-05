/**
 * Archivo: function.bienes_taller.js
 * Descripción: Lógica JS para la gestión de bienes de taller.
 * La institución viene por el menú (id_institucion) y se envía en cada petición.
 */

// Variable global para la instancia de la DataTable
let tableBienesTaller;
let departamentosData = [];

// Institución activa (viene del menú, mismo patrón que orden.php)
const idInstitucionTaller = document.getElementById('id_institucion')?.value || 1;

document.addEventListener('DOMContentLoaded', function () {
    const formBienTaller = document.querySelector("#formBienTaller");

    tableBienesTaller = $('#tableBienesTaller').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        "language": {
            "url": base_url + "src/plugins/js/es_es.json"
        },
        "ajax": {
            "url": base_url + "BienesTaller/getBienesTaller?id_institucion=" + idInstitucionTaller,
            "dataSrc": ""
        },
        "columns": [
            { "data": "id_bien_taller" },
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

    loadFormSelects();

    // --- INICIO: Event Listeners para botones PDF ---
    document.querySelector('#btnPdfGeneral').addEventListener('click', async function () {
        notifi("Generando PDF general, por favor espere...", "info");
        try {
            const response = await fetch(base_url + 'BienesTaller/generarPdfGeneral?id_institucion=' + idInstitucionTaller);
            const result = await response.json();
            if (result.success) {
                fntGenerarBienesTallerPDF(result.data, "Reporte General de Bienes de Taller", result.nombreInstitucion);
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

    const btnExportarBusqueda = document.querySelector('#btnExportarBusqueda');
    if (btnExportarBusqueda) {
        btnExportarBusqueda.addEventListener('click', async function () {
            const termino = document.querySelector('#txtBusquedaBienTaller').value.trim();
            if (termino === '') {
                notifi("Por favor, ingrese un término de búsqueda.", "warning");
                return;
            }
            notifi("Generando PDF de la búsqueda, por favor espere...", "info");
            fntGenerarPdfPorBusqueda(termino);
        });
    }
    // --- FIN: Event Listeners para botones PDF ---

    const txtBusquedaBienTaller = document.querySelector('#txtBusquedaBienTaller');
    if (txtBusquedaBienTaller) {
        txtBusquedaBienTaller.addEventListener('keyup', function () {
            tableBienesTaller.search(this.value).draw();
        });
    }

    formBienTaller.addEventListener('submit', async function (e) {
        e.preventDefault();
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
            const formData = new FormData(formBienTaller);
            formData.append('id_institucion', idInstitucionTaller);

            const response = await fetch(base_url + 'BienesTaller/setBienTaller', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                closeModal();
                tableBienesTaller.ajax.reload();
                notifi(result.msg, "success");
            } else {
                notifi(result.msg, "error");
            }
        } catch (error) {
            notifi("Ocurrió un error en la operación.", "error");
        }
    });
});

async function loadFormSelects() {
    try {
        const url = base_url + 'BienesTaller/getInitialData?id_institucion=' + idInstitucionTaller;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const { departamentos, grupos, subgrupos, secciones } = result.data;

            departamentosData = departamentos;

            populateSelect('departamento', departamentos, 'depatamento_bien_id', 'departamento_bien');
            populateSelect('grupo', grupos, 'id_grupo', 'grupo');
            populateSelect('subgrupo', subgrupos, 'subgrupo_id', 'subgrupo');
            populateSelect('seccion', secciones, 'seccion_id', 'seccion');
            populateSelect('listDeptoQR', departamentos, 'depatamento_bien_id', 'departamento_bien');
            populateSelect('listDeptoPDF', departamentos, 'depatamento_bien_id', 'departamento_bien');
        }
    } catch (error) {
        console.error("Error al cargar datos para los selects:", error);
    }
}

function openModal() {
    document.querySelector('#id_bien_taller').value = "";
    document.querySelector('#titleModal').innerHTML = "Nuevo Bien de Taller";
    document.querySelector('#btnActionText').innerHTML = "Guardar";
    document.querySelector("#formBienTaller").reset();
    $('#modalFormBienTaller').modal('show');
}

function closeModal() {
    $('#modalFormBienTaller').modal('hide');
}

async function fntEditBienTaller(id_bien_taller) {
    document.querySelector('#formBienTaller').reset();
    document.querySelector('#titleModal').innerHTML = "Actualizar Bien de Taller";
    document.querySelector('#btnActionText').innerHTML = "Actualizar";

    try {
        const url = `${base_url}BienesTaller/getBienTaller/${id_bien_taller}?id_institucion=${idInstitucionTaller}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const bien = result.data;
            document.querySelector("#id_bien_taller").value = bien.id_bien_taller;
            document.querySelector("#descripcion").value = bien.descripcion_bien;
            document.querySelector("#departamento").value = bien.bien_depatamento_id;
            document.querySelector("#grupo").value = bien.grupo_id;
            document.querySelector("#subgrupo").value = bien.subgrupo_id;
            document.querySelector("#seccion").value = bien.seccion_id;
            document.querySelector("#status_bien").value = bien.status_bien;
            document.querySelector("#fecha_adquisicion").value = bien.fecha_adquisicion;

            $('#modalFormBienTaller').modal('show');
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error:', error);
        notifi("Ocurrió un error al obtener los datos del bien de taller.", "error");
    }
}

function fntDelBienTaller(id_bien_taller) {
    Swal.fire({
        title: 'Eliminar Bien de Taller',
        text: "¿Realmente quieres eliminar este bien de taller?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('id_bien_taller', id_bien_taller);
                formData.append('id_institucion', idInstitucionTaller);

                const url = base_url + 'BienesTaller/delBienTaller';
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    notifi(data.message, "success");
                    tableBienesTaller.ajax.reload();
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
 * Genera el PDF de bienes de taller.
 * @param {Object} data
 * @param {string} titulo
 * @param {string} nombreInstitucion - viene del controlador
 */
function fntGenerarBienesTallerPDF(data, titulo, nombreInstitucion) {
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

    const inputInstitucion = document.createElement('input');
    inputInstitucion.type = 'hidden';
    inputInstitucion.name = 'nombreInstitucion';
    inputInstitucion.value = nombreInstitucion || '';

    form.appendChild(inputData);
    form.appendChild(inputTitulo);
    form.appendChild(inputInstitucion);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

async function fntGenerarPdfPorDepto(deptoId) {
    try {
        const formData = new FormData();
        formData.append('idDepartamento', deptoId);
        formData.append('id_institucion', idInstitucionTaller);

        const response = await fetch(`${base_url}BienesTaller/generarPdfPorDepartamento`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            fntGenerarBienesTallerPDF(result.data, `Reporte de Bienes de Taller - ${result.departamento}`, result.nombreInstitucion);
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        notifi("Error al solicitar el reporte por departamento.", "error");
    }
}

async function fntGenerarPdfPorBusqueda(termino) {
    try {
        const formData = new FormData();
        formData.append('terminoBusqueda', termino);
        formData.append('id_institucion', idInstitucionTaller);

        const response = await fetch(`${base_url}BienesTaller/generarPdfPorBusqueda`, {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            fntGenerarBienesTallerPDF(result.data, `Reporte de Búsqueda: "${result.termino}"`, result.nombreInstitucion);
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error al solicitar el reporte por búsqueda:', error);
        notifi("Error al solicitar el reporte por búsqueda.", "error");
    }
}

function openModalQR() {
    $('#modalQR').modal('show');
}

function closeModalQR() {
    const qrResultDiv = document.querySelector('#qrResult');
    const qrcodeDiv = document.querySelector('#qrcode');
    const deptoSelect = document.querySelector('#listDeptoQR');

    qrcodeDiv.innerHTML = '';
    const existingLinks = qrResultDiv.querySelector('.qr-actions');
    if (existingLinks) {
        existingLinks.remove();
    }

    qrResultDiv.classList.add('d-none');
    deptoSelect.value = '';
}

function generarQR() {
    const deptoSelect = document.querySelector('#listDeptoQR');
    const selectedDeptoId = deptoSelect.value;
    const qrResultDiv = document.querySelector('#qrResult');
    const qrcodeDiv = document.querySelector('#qrcode');

    if (!selectedDeptoId) {
        notifi("Por favor, selecciona un departamento.", "warning");
        qrResultDiv.classList.add('d-none');
        return;
    }

    const urlToEncode = `${base_url}data/bienes/bienes_taller_qr.php?departamento_id=${selectedDeptoId}`;

    qrcodeDiv.innerHTML = '';
    const existingLinks = qrResultDiv.querySelector('.qr-actions');
    if (existingLinks) {
        existingLinks.remove();
    }

    try {
        new QRCode(qrcodeDiv, {
            text: urlToEncode,
            width: 220,
            height: 220,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        setTimeout(() => {
            const qrImage = qrcodeDiv.querySelector('img');
            const departamentoNombre = deptoSelect.options[deptoSelect.selectedIndex].text;
            if (qrImage) {
                const buttonContainer = document.createElement('div');
                buttonContainer.className = 'mt-3 flex justify-center gap-2 qr-actions';
                buttonContainer.innerHTML = `
                    <a href="${qrImage.src}" download="QR_Taller_${departamentoNombre.replace(/\s+/g, '_')}.png" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded-md transition-colors flex items-center text-sm">
                        <i class="fas fa-download mr-2"></i> Descargar
                    </a>
                    <a href="${urlToEncode}" target="_blank" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-md transition-colors flex items-center text-sm">
                        <i class="fas fa-external-link-alt mr-2"></i> Ver Tabla
                    </a>
                `;
                qrResultDiv.appendChild(buttonContainer);
            }
        }, 100);

        qrResultDiv.classList.remove('d-none');
        notifi("Código QR generado correctamente.", "success");
    } catch (error) {
        console.error("Error al generar el QR:", error);
        notifi("No se pudo generar el código QR. Asegúrate de que la librería qrcode.js esté cargada.", "error");
        qrResultDiv.classList.add('d-none');
    }
}
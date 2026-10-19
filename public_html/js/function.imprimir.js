/**
 * Envía los datos de un ticket a un script PHP para impresión local.
 * @param {Object} ticketData - Objeto con los datos del ticket y la bandera de copia.
 */
async function fntImprimirTicket(ticketData) {
    if (!ticketData || !ticketData.ticketData) {
        notifi('Datos de ticket incompletos para la impresión.', 'error');
        return;
    }
    await _sendToPrintScript("ticket.php", { dataTicket: ticketData.ticketData, copia: ticketData.copia }, 'ticket');
}

/**
 * Envía los datos del detalle de ventas a un script PHP para impresión.
 * @param {Array} dataDetalle - Array de objetos con el detalle de las ventas.
 */
async function fntImprimirDetallado(dataDetalle) {
    if (!dataDetalle || dataDetalle.length === 0) {
        notifi('No hay detalles de ventas para imprimir.', 'info');
        return;
    }
    await _sendToPrintScript("detallado.php", { dataTicket: dataDetalle }, 'reporte detallado');
}

/**
 * Envía los datos de un cierre de día a un script PHP para impresión.
 * @param {Object} dataCierre - Objeto con los datos del cierre.
 */
async function fntImprimirCierre(dataCierre) {
    if (!dataCierre) {
        notifi('Datos de cierre incompletos para la impresión.', 'error');
        return;
    }
    await _sendToPrintScript("cierre.php", { dataTicket: dataCierre }, 'reporte de cierre');
}

/**
 * Función auxiliar para enviar datos a los scripts de impresión PHP.
 * @param {string} scriptName - Nombre del script PHP en la carpeta 'data/'.
 * @param {Object} payload - El objeto de datos a enviar.
 * @param {string} printType - El tipo de impresión (para mensajes de error).
 * @private
 */
async function _sendToPrintScript(scriptName, payload, printType) {
    try {
        const response = await fetch(`${base_url}data/${scriptName}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.success) {
            notifi(result.message, 'success');
        } else {
            notifi(result.error || `Error al procesar la impresión del ${printType}.`, 'error');
        }
    } catch (error) {
        notifi(`Error de conexión al imprimir ${printType}: ${error.message}`, 'error');
    }
}

/**
 * Genera y abre un reporte en PDF en una nueva pestaña.
 * @param {Object} reporteData - Objeto con los datos para el reporte.
 */
function fntGenerarPDF(reporteData) {
    // Verificar que los datos existan antes de enviarlos
    if (reporteData) {
        // Crear un formulario oculto
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = base_url + "data/reporte.php"
        form.target = '_blank' // Abrir en una nueva pestaña
        // Crear un input para los datos y asignarle el JSON
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = 'reporteData'
        input.value = JSON.stringify(reporteData)
        // Agregar el input y el formulario al cuerpo del documento
        form.appendChild(input)
        document.body.appendChild(form)
        // Enviar el formulario
        form.submit()
        // Limpiar el formulario después del envío
        document.body.removeChild(form)
    } else {
        console.error("Error: Los datos para el PDF están incompletos.")
    }
}
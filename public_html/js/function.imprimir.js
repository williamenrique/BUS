/**
 * ============================================================
 * SITGO · Impresión vía script PHP (versión legacy)
 * ============================================================
 * Se mantiene por compatibilidad. La versión activa es
 * function.imprimirQZ.js (impresión térmica directa).
 * ============================================================
 */

/* ============================================================
   PREGUNTAR MODO DEL DETALLADO (mismo comportamiento que QZ)
   ============================================================ */
async function fntPreguntarModoDetallado() {
    if (typeof window.sitgoChoice === 'function') {
        try {
            const modo = await window.sitgoChoice({
                title:   '¿Cómo deseas el reporte detallado?',
                message: 'Elige el formato con el que se imprimirá el reporte de ventas del día.',
                options: [
                    { value: 'unificado', label: 'Unificado',  hint: 'Todo convertido a Bs' },
                    { value: 'divisa',    label: 'Con divisa', hint: 'Columna en $ y columna en Bs' },
                ],
                cancelText: 'Cancelar',
                type:  'info',
                icon:  'printer',
            });
            return modo || null;
        } catch (e) {
            console.warn('sitgoChoice falló, usando prompt:', e);
        }
    }

    const v = window.prompt('Tipo de detallado: 1 = unificado, 2 = divisa', '1');
    if (!v) return null;
    return v === '2' ? 'divisa' : 'unificado';
}

/* ============================================================
   TICKET
   ============================================================ */
async function fntImprimirTicket(ticketData) {
    if (!ticketData || !ticketData.ticketData) {
        _notify('Datos de ticket incompletos para la impresión.', 'error');
        return;
    }
    await _sendToPrintScript("ticket.php", { dataTicket: ticketData.ticketData, copia: ticketData.copia }, 'ticket');
}

/* ============================================================
   DETALLADO
   ============================================================ */
async function fntImprimirDetallado(dataDetalle) {
    if (!dataDetalle || dataDetalle.length === 0) {
        _notify('No hay detalles de ventas para imprimir.', 'info');
        return;
    }
    await _sendToPrintScript("detallado.php", { dataTicket: dataDetalle }, 'reporte detallado');
}

/* ============================================================
   CIERRE
   ============================================================ */
async function fntImprimirCierre(dataCierre) {
    if (!dataCierre) {
        _notify('Datos de cierre incompletos para la impresión.', 'error');
        return;
    }
    await _sendToPrintScript("cierre.php", { dataTicket: dataCierre }, 'reporte de cierre');
}

/* ============================================================
   HELPER INTERNO
   ============================================================ */
function _notify(msg, type = 'info') {
    if (typeof window.sitgoToast === 'function') {
        window.sitgoToast(msg, type === 'error' ? 'error' : 'success');
        return;
    }
    if (type === 'error') console.error(msg);
    else console.log(msg);
}

async function _sendToPrintScript(scriptName, payload, printType) {
    try {
        const base = (window.SITGO_BASE || '/');
        const response = await fetch(`${base}data/${scriptName}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await response.json();
        if (result.success) {
            _notify(result.message, 'success');
        } else {
            _notify(result.error || `Error al procesar la impresión del ${printType}.`, 'error');
        }
    } catch (error) {
        _notify(`Error de conexión al imprimir ${printType}: ${error.message}`, 'error');
    }
}

/* ============================================================
   PDF
   ============================================================ */
function fntGenerarPDF(reporteData) {
    if (reporteData) {
        const form = document.createElement('form')
        form.method = 'POST'
        form.action = base_url + "data/reporte.php"
        form.target = '_blank'
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = 'reporteData'
        input.value = JSON.stringify(reporteData)
        form.appendChild(input)
        document.body.appendChild(form)
        form.submit()
        document.body.removeChild(form)
    } else {
        console.error("Error: Los datos para el PDF están incompletos.")
    }
}
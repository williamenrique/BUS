/**
 * ============================================================
 * SITGO · Impresión térmica con QZ Tray
 * ============================================================
 * Adaptado del sistema anterior. Mantiene los mismos comandos
 * ESC/POS y las mismas firmas de función para no romper las
 * llamadas desde estacion.js / dataventa.js / etc.
 * ============================================================
 */

/* ============================================================
   HELPER DE NOTIFICACIÓN
   - Escribe en consola siempre.
   - Si existe toast global, también lo muestra.
   - Si no, no rompe nada.
   ============================================================ */
function _qzNotify(msg, type = 'info') {
    const map = { info: 'log', success: 'log', warning: 'warn', error: 'error' };
    const fn = map[type] || 'log';
    (console[fn] || console.log)('[QZ Tray]', msg);

    try {
        if (typeof window.toast === 'function') {
            window.toast(msg, type === 'error' ? 'error' : (type === 'warning' ? 'error' : 'success'));
            return;
        }
    } catch (_) { /* ignorar */ }

    try {
        let c = document.querySelector('.toast-container');
        if (!c) {
            c = document.createElement('div');
            c.className = 'toast-container';
            document.body.appendChild(c);
        }
        const t = document.createElement('div');
        t.className = 'toast ' + (type === 'error' ? 'error' : 'success');
        t.textContent = msg;
        c.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    } catch (_) { /* ignorar */ }
}

/* ============================================================
   PREGUNTAR MODO DEL DETALLADO
   - Por compatibilidad con estacion.js / dataventa.js.
   - Devuelve 'unificado' o 'divisa'.
   - Si el usuario cancela, devuelve null.
   - Usa Swal si está disponible; si no, cae a prompt().
   ============================================================ */
async function fntPreguntarModoDetallado() {
    // Swal si está disponible
    if (typeof Swal !== 'undefined' && Swal.fire) {
        try {
            const res = await Swal.fire({
                title: '¿Cómo desea el detallado?',
                input: 'radio',
                inputOptions: {
                    'unificado': 'Unificado (todo en Bs)',
                    'divisa':    'Detallado (con columna $)'
                },
                inputValidator: (v) => v ? null : 'Selecciona una opción',
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                showCancelButton: true
            });
            if (!res.isConfirmed || !res.value) return null;
            return res.value;
        } catch (e) {
            console.warn('Swal falló, usando prompt:', e);
        }
    }

    // Fallback: prompt
    const v = window.prompt('Tipo de detallado: 1 = unificado, 2 = divisa', '1');
    if (!v) return null;
    return v === '2' ? 'divisa' : 'unificado';
}

/* ============================================================
   IMPRESIÓN · REPORTE DETALLADO
   ============================================================ */
async function fntImprimirDetallado(dataVentas, modo = 'unificado') {
    if (!dataVentas || dataVentas.length === 0) {
        _qzNotify('No hay datos para imprimir el detallado.', 'error');
        return;
    }

    const primerVenta = dataVentas[0];
    const esDivisa = (modo === 'divisa');

    const data = [
        '\x1B' + '\x40',
        '\x1B' + '\x61' + '\x31',
        `${primerVenta.estacion || ''}\n`,
        'REPORTE DETALLADO DE VENTAS\n',
        '\x1B' + '\x61' + '\x30',
        '--------------------------------\n',
        `Fecha: ${primerVenta.fecha_venta || ''}\n`,
        `Operador: ${primerVenta.operador || ''}\n`,
        `Tasa del Dia: ${parseFloat(primerVenta.tasa_dia || 0).toFixed(2)} Bs\n`,
        '--------------------------------\n',
        '#  | Vehiculo  | Monto Bs | Litros\n',
        '--------------------------------\n',
    ];

    let totalLitros = 0;
    let totalMontoBs = 0;

    for (const venta of dataVentas) {
        let montoVentaBs = 0;
        if (venta.tipo_pago === 'Efectivo Divisa') {
            montoVentaBs = parseFloat(venta.monto || 0) * parseFloat(venta.tasa_dia || 0);
        } else {
            montoVentaBs = parseFloat(venta.monto || 0);
        }

        const linea = [
            String(venta.numero_venta || '').padEnd(3),
            '|',
            String(venta.tipo_vehiculo || '').substring(0, 9).padEnd(10),
            '|',
            montoVentaBs.toFixed(2).padStart(9),
            '|',
            parseFloat(venta.cantidad_litros || 0).toFixed(2).padStart(5) + 'L',
        ].join(' ');
        data.push(linea + '\n');

        totalLitros += parseFloat(venta.cantidad_litros || 0);
        totalMontoBs += montoVentaBs;
    }

    data.push(
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        `Total Vehiculos: ${dataVentas.length}\n`,
        `Total Litros: ${totalLitros.toFixed(2)} L\n`,
        `Total Vendido: ${totalMontoBs.toFixed(2)} Bs\n`,
        '\x1B' + '\x45' + '\x00',
        '--------------------------------\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00'
    );

    await _printToQZ(data);
}

/* ============================================================
   IMPRESIÓN · CIERRE DEL DÍA
   ============================================================ */
async function fntImprimirCierre(dataCierre) {
    if (!dataCierre) {
        _qzNotify('Datos de cierre incompletos para la impresión.', 'error');
        return;
    }

    const totalEfectivoBs = parseFloat(dataCierre.total_general_bs || 0) - parseFloat(dataCierre.total_debito || 0);

    const data = [
        '\x1B' + '\x40',
        '\x1B' + '\x61' + '\x31',
        `${dataCierre.estacion || ''}\n`,
        '\x1B' + '\x61' + '\x30',
        '\x1B' + '\x45' + '\x01',
        '--------------------------------\n',
        `CIERRE DE OPERACIONES #${dataCierre.id_cierre || ''}\n`,
        '\x1B' + '\x45' + '\x00',
        `Operador: ${dataCierre.usuario_nombres || ''} ${dataCierre.usuario_apellidos || ''}\n`,
        `Fecha: ${dataCierre.fecha_cierre || ''}\n`,
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        'VEHICULOS ATENDIDOS\n',
        '\x1B' + '\x45' + '\x00',
        ...(dataCierre.cant_auto > 0 ? [`Autos: ${dataCierre.cant_auto}\n`] : []),
        ...(dataCierre.cant_moto > 0 ? [`Motos: ${dataCierre.cant_moto}\n`] : []),
        ...(dataCierre.cant_camion > 0 ? [`Camiones: ${dataCierre.cant_camion}\n`] : []),
        `Total Atendidos: ${dataCierre.total_ventas || 0}\n`,
        `Total Litros: ${parseFloat(dataCierre.total_litros || 0).toFixed(2)} L\n`,
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        'TOTALES EN BOLIVARES (Bs)\n',
        '\x1B' + '\x45' + '\x00',
        (totalEfectivoBs > 0 ? `Efectivo: ${totalEfectivoBs.toFixed(2)} Bs\n` : ''),
        (dataCierre.total_debito > 0 ? `Punto de Venta: ${parseFloat(dataCierre.total_debito || 0).toFixed(2)} Bs\n` : ''),
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        `Total General: ${parseFloat(dataCierre.total_general_bs || 0).toFixed(2)} Bs\n`,
        '\x1B' + '\x45' + '\x00',
        '--------------------------------\n',
        '\x1B' + '\x61' + '\x31',
        'Cierre realizado con exito\n',
        '\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00'
    ];

    await _printToQZ(data);
}

/* ============================================================
   IMPRESIÓN · TICKET INDIVIDUAL
   ============================================================ */
async function fntImprimirTicket(datTicket) {
    if (!datTicket || !datTicket.ticketData) {
        console.error('Error: Los datos del ticket están incompletos.');
        _qzNotify('Datos de ticket incompletos.', 'error');
        return;
    }

    const ticket = datTicket.ticketData;
    const esCopia = datTicket.copia === 1;

    let simbolo = 'Bs';
    if (ticket.id_tipo_pago == 1) simbolo = '$';

    const data = [
        '\x1B' + '\x40',
        '\x1B' + '\x61' + '\x31',
        `${ticket.estacion || ''}\n`,
        '\x1B' + '\x45' + '\x01',
        `TICKET DE VENTA #${ticket.id_venta || ''}\n`,
        '\x1B' + '\x45' + '\x00',
        '\x1B' + '\x61' + '\x30',
        '--------------------------------\n',
        `Fecha: ${ticket.fecha_venta || ''} ${ticket.hora_venta || ''}\n`,
        `Operador: ${ticket.personal_nombre || ''} ${ticket.personal_apellido || ''}\n`,
        '--------------------------------\n',
        `Pago: ${ticket.tipoPago || ''}\n`,
        `Vehiculo: ${ticket.tipoVehiculo || ''}\n`,
        '\x1B' + '\x61' + '\x31',
        '\x1D\x21\x11',
        '\x1B\x45\x01',
        `${parseFloat(ticket.litros || 0).toFixed(2)} L\n`,
        '\x1B\x45\x00',
        '\x1D\x21\x00',
        `Monto: ${parseFloat(ticket.monto || 0).toFixed(2)} ${simbolo}\n`,
        '\x1B\x45\x00',
        '--------------------------------\n',
        '\x1B\x61\x31',
        'Gracias por su compra\n',
        esCopia ? '\x1B\x45\x01' + 'COPIA\n' : 'ORIGINAL\n',
        '\x1B\x45\x00',
        '\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00'
    ];

    await _printToQZ(data);
}

/* ============================================================
   INICIALIZACIÓN QZ TRAY
   - No lanza si QZ no está. Solo avisa por consola.
   - No bloquea otras vistas.
   ============================================================ */
async function initQZTrayConnection() {
    if (typeof qz === 'undefined') {
        console.warn('[QZ Tray] Librería no cargada. La impresión térmica queda deshabilitada.');
        return false;
    }

    try {
        // Seguridad: permitir conexión sin firmar (entorno local / confianza)
        qz.security.setCertificatePromise(function (resolve) {
            resolve(null);
        });
        qz.security.setSignaturePromise(function () {
            return function (resolve) {
                resolve();
            };
        });

        if (!qz.websocket.isActive()) {
            try {
                await qz.websocket.connect();
                console.log('[QZ Tray] Conectado.');
            } catch (err) {
                console.warn('[QZ Tray] No se pudo conectar:', err?.message || err);
                return false;
            }
        }

        qz.websocket.setClosedCallbacks(function () {
            console.warn('[QZ Tray] Desconectado.');
        });

        qz.websocket.setErrorCallbacks(function (evt) {
            console.warn('[QZ Tray] Error:', evt);
        });

        return true;
    } catch (err) {
        console.warn('[QZ Tray] init falló:', err?.message || err);
        return false;
    }
}

/* ============================================================
   ENVÍO A IMPRESORA
   ============================================================ */
async function _printToQZ(data, printerName = 'XP-80C') {
    try {
        if (typeof qz === 'undefined') {
            _qzNotify('Librería QZ Tray no disponible.', 'error');
            return false;
        }

        if (!qz.websocket.isActive()) {
            await qz.websocket.connect();
        }

        const config = qz.configs.create(printerName);

        // Ejecutar qz.print con timeout de 15s
        await Promise.race([
            qz.print(config, data),
            new Promise((_, reject) =>
                setTimeout(() => reject(new Error('Tiempo de espera agotado (impresora no responde)')), 15000)
            )
        ]);

        _qzNotify('Impresión enviada correctamente.', 'success');
        return true;
    } catch (error) {
        console.error('Error durante la impresión con QZ Tray:', error);
        _qzNotify('Error al imprimir: ' + (error?.message || error), 'error');
        return false;
    }
}

/* ============================================================
   GENERAR PDF (migrado a SITGO_PDF)
   - Ya no abre window.print()
   - Usa SITGO_PDF.generar() con el template del sistema
   ============================================================ */
async function fntGenerarPDF(datosIniciales) {
    if (!datosIniciales || !datosIniciales.fecha) {
        _qzNotify('No hay datos suficientes para generar el reporte.', 'error');
        return;
    }

    const base = (window.SITGO_BASE || '/');
    const url = base.replace(/\/?$/, '/') + 'estacion/data-cierre';

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                id_cierre: datosIniciales.id_cierre ?? 0,
                fecha: datosIniciales.fecha,
                id_user: datosIniciales.idUser
            })
        });

        const res = await response.json();
        if (!res.ok || !res.cierreData) {
            _qzNotify(res.message || 'No se pudo obtener el cierre. Genera el PDF desde el botón del sistema.', 'error');
            return;
        }

        await SITGO_PDF.generar(
            'estacion_reporte_dia',
            { cierre: res.cierreData, ventas: res.ventas, seccion: 'resumen' },
            `cierre_resumen_${datosIniciales.fecha}.pdf`,
            { titulo_documento: 'CIERRE DEL DÍA', doc_color: '#1e40af' }
        );
        _qzNotify('PDF generado correctamente.', 'success');
    } catch (e) {
        console.error('fntGenerarPDF:', e);
        _qzNotify('Error al generar el PDF.', 'error');
    }
}
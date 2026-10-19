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
   HELPER DE NOTIFICACIÓN (reemplaza a "notifi" del sistema viejo)
   - Escribe en consola siempre.
   - Si existe un toast global, también lo muestra.
   - Si no, no rompe nada.
   ============================================================ */
function _qzNotify(msg, type = 'info') {
    const map = { info: 'log', success: 'log', warning: 'warn', error: 'error' };
    const fn = map[type] || 'log';
    (console[fn] || console.log)('[QZ Tray]', msg);

    // Intentar usar toast global si existe (estacion.js / dataventa.js lo definen)
    try {
        if (typeof window.toast === 'function') {
            window.toast(msg, type === 'error' ? 'error' : (type === 'warning' ? 'error' : 'success'));
            return;
        }
    } catch (_) { /* ignorar */ }

    // Fallback: toast simple si hay contenedor
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
   IMPRESIÓN · REPORTE DETALLADO
   ============================================================ */
async function fntImprimirDetallado(dataVentas) {
    if (!dataVentas || dataVentas.length === 0) {
        _qzNotify('No hay datos para imprimir el detallado.', 'error');
        return;
    }

    const primerVenta = dataVentas[0];

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
   - No bloquea otras vistas (llamada blindada desde dataventa.js)
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
        await qz.print(config, data);

        _qzNotify('Impresión enviada correctamente.', 'success');
        return true;
    } catch (error) {
        console.error('Error durante la impresión con QZ Tray:', error);
        _qzNotify('Error al imprimir: ' + (error?.message || error), 'error');
        return false;
    }
}

/* ============================================================
   GENERAR PDF · adaptado a SITGO
   - Usa Swal si está disponible. Si no, usa prompt() como fallback.
   - Envía a /estacion/reporte-pdf (ruta actual del sistema).
   - Abre el PDF en nueva pestaña con un Blob (no depende de
     "data/estacion/reporte.php" del sistema viejo).
   ============================================================ */
async function fntGenerarPDF(datosIniciales) {
    if (!datosIniciales || !datosIniciales.fecha || !datosIniciales.idUser) {
        _qzNotify('No hay datos suficientes para generar el reporte.', 'error');
        return;
    }

    let reportType = 'unificado';

    // Selector de tipo de reporte (Swal si existe, prompt si no)
    if (typeof Swal !== 'undefined' && Swal.fire) {
        try {
            const res = await Swal.fire({
                title: 'Seleccione el tipo de reporte',
                input: 'radio',
                inputOptions: {
                    'unificado': 'Unificado (Efectivo Bs + Divisas en Bs)',
                    'divisa': 'Detallado (con columna para Divisas $)'
                },
                inputValidator: (value) => value ? null : '¡Necesitas elegir una opción!',
                confirmButtonText: 'Generar PDF',
                showCancelButton: true,
                cancelButtonText: 'Cancelar'
            });
            if (!res.isConfirmed || !res.value) return;
            reportType = res.value;
        } catch (e) {
            console.warn('Swal falló, usando prompt:', e);
            const v = window.prompt('Tipo de reporte: 1 = unificado, 2 = divisa', '1');
            if (!v) return;
            reportType = (v === '2') ? 'divisa' : 'unificado';
        }
    } else {
        const v = window.prompt('Tipo de reporte: 1 = unificado, 2 = divisa', '1');
        if (!v) return;
        reportType = (v === '2') ? 'divisa' : 'unificado';
    }

    try {
        const base = (window.SITGO_BASE || '/');
        const url = base.replace(/\/?$/, '/') + 'estacion/reporte-pdf';

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                fecha: datosIniciales.fecha,
                id_user: datosIniciales.idUser,
                reportType: reportType
            })
        });

        const ct = response.headers.get('content-type') || '';
        if (!ct.includes('application/json')) {
            throw new Error(`Respuesta no JSON (HTTP ${response.status})`);
        }

        const res = await response.json();

        // En SITGO los endpoints devuelven { ok: true, data: {...} }
        const ok = res.ok === true || res.success === true;

        if (ok && res.data) {
            _qzNotify('Datos listos. Abriendo ventana de impresión...', 'success');
            _abrirReporteEnNuevaVentana(res.data);
        } else {
            _qzNotify(res.message || 'Error al obtener los datos del reporte.', 'error');
        }
    } catch (error) {
        console.error('Error en fntGenerarPDF:', error);
        _qzNotify('Ocurrió un error al generar el PDF.', 'error');
    }
}

/**
 * Abre una nueva ventana con el JSON del reporte y la librería jsPDF
 * (o con window.print) para generar el PDF sin depender de rutas
 * del sistema anterior.
 */
function _abrirReporteEnNuevaVentana(data) {
    try {
        const w = window.open('', '_blank');
        if (!w) {
            _qzNotify('El navegador bloqueó la ventana emergente.', 'error');
            return;
        }
        w.document.open();
        w.document.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>Reporte</title>
                <style>
                    body { font-family: Arial, sans-serif; font-size: 11px; padding: 15px; }
                    h2 { text-align: center; margin: 0 0 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
                    th, td { border: 1px solid #ccc; padding: 4px 6px; }
                    th { background: #eee; }
                    .right { text-align: right; }
                </style>
            </head>
            <body>
                <h2>Reporte</h2>
                <pre id="json" style="display:none">${JSON.stringify(data).replace(/</g, '\\u003c')}</pre>
                <div id="render"></div>
            </body>
            </html>
        `);
        w.document.close();

        // Render mínimo: si data.detallado existe, pinta tabla
        try {
            const d = data.detallado || [];
            const r = data.resumen || {};
            let html = '';
            if (d.length) {
                html += '<table><thead><tr>' +
                    '<th>#</th><th>Hora</th><th>Vehículo</th><th>Combustible</th>' +
                    '<th class="right">Litros</th><th>Pago</th><th class="right">Monto</th>' +
                    '</tr></thead><tbody>';
                d.forEach(v => {
                    html += `<tr>
                        <td>${v.numero_venta ?? ''}</td>
                        <td>${v.hora_venta ?? ''}</td>
                        <td>${v.tipo_vehiculo ?? ''}</td>
                        <td>${v.tipo_combustible ?? ''}</td>
                        <td class="right">${parseFloat(v.cantidad_litros || 0).toFixed(2)}</td>
                        <td>${v.tipo_pago ?? ''}</td>
                        <td class="right">${parseFloat(v.monto || 0).toFixed(2)}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
            }
            if (r.total_litros) {
                html += `<p style="margin-top:10px"><strong>Total litros:</strong> ${parseFloat(r.total_litros).toFixed(2)} L</p>`;
            }
            if (r.total_bs) {
                html += `<p><strong>Total Bs:</strong> ${parseFloat(r.total_bs).toFixed(2)}</p>`;
            }
            w.document.getElementById('render').innerHTML = html || '<p>Sin datos.</p>';
        } catch (e) {
            console.warn('Render reporte:', e);
        }

        // Disparar print
        setTimeout(() => { try { w.print(); } catch (_) {} }, 400);
    } catch (e) {
        console.error('_abrirReporteEnNuevaVentana:', e);
        _qzNotify('No se pudo abrir el reporte.', 'error');
    }
}
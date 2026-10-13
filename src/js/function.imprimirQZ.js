/**
 * Formatea un número al estilo venezolano: miles con punto, decimales con coma.
 */
function fntFormatBs(valor) {
    const num = parseFloat(valor) || 0;
    const partes = num.toFixed(2).split('.');
    const entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return entero + ',' + partes[1];
}

function _abrevCombustible(nombre) {
    if (!nombre) return '---';
    const n = String(nombre).toLowerCase();
    if (n.includes('diesel') || n.includes('diésel')) return 'DIESEL';
    if (n.includes('gasolina')) return 'GASOL.';
    return String(nombre).substring(0, 6).toUpperCase();
}

function _clasificarCombustible(venta) {
    const n = (venta.tipo_combustible || '').toLowerCase();
    if (n.includes('diesel') || n.includes('diésel')) return 'diesel';
    if (n.includes('gasolina')) return 'gasolina';
    return 'otros';
}

function _montoEnBs(venta) {
    if (venta.tipo_pago === "Efectivo Divisa") {
        return parseFloat(venta.monto) * parseFloat(venta.tasa_dia);
    }
    return parseFloat(venta.monto);
}

/**
 * Imprime un reporte detallado de ventas del día utilizando QZ Tray.
 */
async function fntImprimirDetallado(dataVentas, modo = 'unificado') {
    if (!dataVentas || dataVentas.length === 0) {
        notifi("No hay datos para imprimir el detallado.", "error");
        return;
    }

    const primerVenta = dataVentas[0];
    const imprimirUnificado = (modo === 'unificado' || modo === 'ambos');
    const imprimirSeparado  = (modo === 'separado'  || modo === 'ambos');

    const data = [
        '\x1B' + '\x40',
        '\x1B' + '\x61' + '\x31',
        `${primerVenta.estacion}\n`,
        'REPORTE DETALLADO DE VENTAS\n',
        '\x1B' + '\x61' + '\x30',
        '--------------------------------\n',
        `Fecha: ${primerVenta.fecha_venta}\n`,
        `Operador: ${primerVenta.operador}\n`,
        `Tasa del Dia: ${fntFormatBs(primerVenta.tasa_dia)} Bs\n`,
        '--------------------------------\n',
    ];

    if (imprimirUnificado) {
        data.push(
            '\x1B' + '\x45' + '\x01',
            '*** DETALLE UNIFICADO ***\n',
            '\x1B' + '\x45' + '\x00',
            '# | Vehic  | Comb.  | Bs      | Lts\n',
            '--------------------------------\n'
        );

        let totalLitros = 0, totalMontoBs = 0, totalGas = 0, totalDie = 0;

        for (const venta of dataVentas) {
            const montoBs = _montoEnBs(venta);
            const clasif = _clasificarCombustible(venta);
            const litros = parseFloat(venta.cantidad_litros) || 0;

            if (clasif === 'diesel') totalDie += litros;
            else if (clasif === 'gasolina') totalGas += litros;

            const abrev = _abrevCombustible(venta.tipo_combustible);
            const linea = [
                String(venta.numero_venta).padEnd(2),
                '|',
                String(venta.tipo_vehiculo).substring(0, 6).padEnd(6),
                '|',
                abrev.padEnd(6),
                '|',
                fntFormatBs(montoBs).padStart(9),
                '|',
                litros.toFixed(2).padStart(5) + 'L'
            ].join(' ');
            data.push(linea + '\n');

            totalLitros += litros;
            totalMontoBs += montoBs;
        }

        data.push(
            '--------------------------------\n',
            '\x1B' + '\x45' + '\x01',
            `Total Vehiculos: ${dataVentas.length}\n`,
            `Total Litros: ${totalLitros.toFixed(2)} L\n`,
            ...(totalGas > 0 ? [`  - Gasolina: ${totalGas.toFixed(2)} L\n`] : []),
            ...(totalDie > 0 ? [`  - Diesel:   ${totalDie.toFixed(2)} L\n`] : []),
            `Total Vendido: ${fntFormatBs(totalMontoBs)} Bs\n`,
            '\x1B' + '\x45' + '\x00',
            '--------------------------------\n'
        );
    }

    if (imprimirSeparado) {
        const grupos = { gasolina: [], diesel: [], otros: [] };
        for (const venta of dataVentas) {
            grupos[_clasificarCombustible(venta)].push(venta);
        }

        const titulos = { gasolina: 'GASOLINA', diesel: 'DIESEL', otros: 'OTROS' };

        let granTotalVentas = 0, granTotalLitros = 0, granTotalBs = 0;

        for (const clave of ['gasolina', 'diesel', 'otros']) {
            const lista = grupos[clave];
            if (!lista || lista.length === 0) continue;

            data.push(
                '\n',
                '\x1B' + '\x45' + '\x01',
                `========== ${titulos[clave]} ==========\n`,
                '\x1B' + '\x45' + '\x00',
                '# | Vehic  | Bs      | Lts\n',
                '--------------------------------\n'
            );

            let subLitros = 0, subBs = 0;
            for (const venta of lista) {
                const montoBs = _montoEnBs(venta);
                const litros = parseFloat(venta.cantidad_litros) || 0;

                const linea = [
                    String(venta.numero_venta).padEnd(2),
                    '|',
                    String(venta.tipo_vehiculo).substring(0, 6).padEnd(6),
                    '|',
                    fntFormatBs(montoBs).padStart(9),
                    '|',
                    litros.toFixed(2).padStart(5) + 'L'
                ].join(' ');
                data.push(linea + '\n');

                subLitros += litros;
                subBs += montoBs;
            }

            data.push(
                '--------------------------------\n',
                '\x1B' + '\x45' + '\x01',
                `Subtotal ${titulos[clave]}:\n`,
                `  Ventas: ${lista.length}\n`,
                `  Litros: ${subLitros.toFixed(2)} L\n`,
                `  Monto:  ${fntFormatBs(subBs)} Bs\n`,
                '\x1B' + '\x45' + '\x00'
            );

            granTotalVentas += lista.length;
            granTotalLitros += subLitros;
            granTotalBs += subBs;
        }

        data.push(
            '\n--------------------------------\n',
            '\x1B' + '\x45' + '\x01',
            '===== TOTAL GENERAL =====\n',
            '\x1B' + '\x45' + '\x00',
            `Ventas: ${granTotalVentas}\n`,
            `Litros: ${granTotalLitros.toFixed(2)} L\n`,
            '\x1B' + '\x45' + '\x01',
            `Monto:  ${fntFormatBs(granTotalBs)} Bs\n`,
            '\x1B' + '\x45' + '\x00'
        );
    }

    data.push(
        '--------------------------------\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00'
    );

    await _printToQZ(data);
}

async function fntPreguntarModoDetallado() {
    const { value: modo } = await Swal.fire({
        title: '¿Cómo desea el detallado?',
        input: 'radio',
        inputOptions: {
            'unificado': 'Unificado (una sola tabla)',
            'separado':  'Separado por tipo de combustible',
            'ambos':     'Ambos (imprime los dos)'
        },
        inputValue: 'unificado',
        inputValidator: (value) => {
            if (!value) return '¡Debes elegir una opción!';
        },
        showCancelButton: true,
        confirmButtonText: 'Continuar',
        cancelButtonText: 'Omitir detallado',
        allowOutsideClick: false
    });
    return modo || null;
}

async function fntImprimirCierre(dataCierre) {
    if (!dataCierre) {
        notifi('Datos de cierre incompletos para la impresión.', 'error');
        return;
    }

    const totalEfectivoBs = parseFloat(dataCierre.total_general_bs) - parseFloat(dataCierre.total_debito);

    const cantGasolina = parseInt(dataCierre.cant_gasolina || 0);
    const cantDiesel = parseInt(dataCierre.cant_diesel || 0);
    const litrosGasolina = parseFloat(dataCierre.litros_gasolina || 0);
    const litrosDiesel = parseFloat(dataCierre.litros_diesel || 0);
    const hayCombustible = (cantGasolina + cantDiesel) > 0;

    const data = [
        '\x1B' + '\x40',
        '\x1B' + '\x61' + '\x31',
        `${dataCierre.estacion}\n`,
        '\x1B' + '\x61' + '\x30',
        '\x1B' + '\x45' + '\x01',
        '--------------------------------\n',
        `CIERRE DE OPERACIONES #${dataCierre.id_cierre}\n`,
        '\x1B' + '\x45' + '\x00',
        `Operador: ${dataCierre.usuario_nombres} ${dataCierre.usuario_apellidos}\n`,
        `Fecha: ${dataCierre.fecha_cierre}\n`,
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        'VEHICULOS ATENDIDOS\n',
        '\x1B' + '\x45' + '\x00',
        ...(dataCierre.cant_auto > 0 ? [`Autos: ${dataCierre.cant_auto}\n`] : []),
        ...(dataCierre.cant_moto > 0 ? [`Motos: ${dataCierre.cant_moto}\n`] : []),
        ...(dataCierre.cant_camion > 0 ? [`Camiones: ${dataCierre.cant_camion}\n`] : []),
        `Total Atendidos: ${dataCierre.total_ventas}\n`,
        `Total Litros: ${parseFloat(dataCierre.total_litros).toFixed(2)} L\n`,

        ...(hayCombustible ? [
            '--------------------------------\n',
            '\x1B' + '\x45' + '\x01',
            'DESGLOSE POR COMBUSTIBLE\n',
            '\x1B' + '\x45' + '\x00',
            ...(cantGasolina > 0 ? [`Gasolina: ${cantGasolina} venta(s)\n`] : []),
            ...(cantGasolina > 0 ? [`          ${litrosGasolina.toFixed(2)} L\n`] : []),
            ...(cantDiesel > 0 ? [`Diesel:   ${cantDiesel} venta(s)\n`] : []),
            ...(cantDiesel > 0 ? [`          ${litrosDiesel.toFixed(2)} L\n`] : []),
        ] : []),

        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        'TOTALES EN BOLIVARES (Bs)\n',
        '\x1B' + '\x45' + '\x00',
        (totalEfectivoBs > 0 ? `Efectivo: ${fntFormatBs(totalEfectivoBs)} Bs\n` : ''),
        (dataCierre.total_debito > 0 ? `Punto de Venta: ${fntFormatBs(dataCierre.total_debito)} Bs\n` : ''),
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        `Total General: ${fntFormatBs(dataCierre.total_general_bs)} Bs\n`,
        '\x1B' + '\x45' + '\x00',
        '--------------------------------\n',
        '\x1B' + '\x61' + '\x31',
        'Cierre realizado con exito\n',
        '\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00'
    ];

    await _printToQZ(data);
}

async function fntImprimirTicket(datTicket) {
    if (!datTicket || !datTicket.ticketData) {
        console.error("Error: Los datos del ticket están incompletos.");
        notifi("Datos de ticket incompletos.", "error");
        return;
    }

    const ticket = datTicket.ticketData;
    const esCopia = datTicket.copia === 1;

    let simbolo = 'Bs';
    if (ticket.id_tipo_pago == 1) simbolo = '$';

    const tipoCombustible = ticket.tipoCombustible || 'Gasolina';

    const data = [
        '\x1B' + '\x40',
        '\x1B' + '\x61' + '\x31',
        `${ticket.estacion}\n`,
        '\x1B' + '\x45' + '\x01',
        `TICKET DE VENTA #${ticket.id_venta}\n`,
        '\x1B' + '\x45' + '\x00',
        '\x1B' + '\x61' + '\x30',
        '--------------------------------\n',
        `Fecha: ${ticket.fecha_venta} ${ticket.hora_venta}\n`,
        `Operador: ${ticket.personal_nombre || ''} ${ticket.personal_apellido || ''}\n`,
        '--------------------------------\n',
        `Pago: ${ticket.tipoPago}\n`,
        `Vehiculo: ${ticket.tipoVehiculo}\n`,
        `Combustible: ${tipoCombustible}\n`,
        '\x1B' + '\x61' + '\x31',
        '\x1D\x21\x11',
        '\x1B\x45\x01',
        `${parseFloat(ticket.litros).toFixed(2)} L\n`,
        '\x1B\x45\x00',
        '\x1D\x21\x00',
        `Monto: ${fntFormatBs(ticket.monto)} ${simbolo}\n`,
        '\x1D\x21\x00',
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

async function initQZTrayConnection() {
    if (typeof qz !== 'undefined') {
        qz.security.setCertificatePromise(function (resolve, reject) {
            resolve(null);
        });
        qz.security.setSignaturePromise(function (toSign) {
            return function (resolve, reject) {
                resolve();
            };
        });

        qz.websocket.connect().catch(err => {
            console.error("Error de conexión inicial con QZ Tray:", err);
            notifi("No se pudo conectar con QZ Tray. Asegúrate de que está en ejecución.", "error");
        });

        qz.websocket.setClosedCallbacks(function (evt) {
            console.log("QZ Tray desconectado.");
            notifi("Se ha perdido la conexión con la impresora.", "warning");
        });

        qz.websocket.setErrorCallbacks(function (evt) {
            console.error("Error de QZ Tray:", evt);
            notifi("Error de comunicación con la impresora.", "error");
        });
    } else {
        console.error("La librería QZ Tray no está cargada.");
        notifi("Librería de impresión no encontrada.", "error");
    }
}

async function _printToQZ(data, printerName = 'XP-80C') {
    try {
        if (!qz.websocket.isActive()) {
            notifi("Reconectando con la impresora...", "info");
            await qz.websocket.connect();
        }

        const config = qz.configs.create(printerName);
        await qz.print(config, data);

        notifi("Impresión enviada correctamente.", "success");
    } catch (error) {
        console.error("Error durante la impresión con QZ Tray:", error);
        notifi("Error al imprimir: " + error.toString(), "error");
    }
}

/**
 * Envía un POST a reporte.php para generar UN PDF de la sección indicada.
 * @param {Object} dataReporte - Objeto con dataTotal, dataDetallado, reportType
 * @param {string} seccion     - 'resumen' | 'ventas' | 'gasolina' | 'diesel'
 */
function _abrirPdfSeccion(dataReporte, seccion) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = base_url + "data/estacion/reporte.php";
    form.target = '_blank';
    form.style.display = 'none';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'reporteData';
    input.value = JSON.stringify(Object.assign({}, dataReporte, { seccion: seccion }));
    form.appendChild(input);

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

/**
 * Genera los PDFs del reporte de ventas.
 *
 * PDFs disponibles:
 *   - 'resumen'  : Cuadro de totales y desgloses (sin listado de tickets)
 *   - 'ventas'   : Listado unificado de todos los tickets
 *   - 'gasolina' : Listado solo de gasolina
 *   - 'diesel'   : Listado solo de diesel
 *
 * @param {Object} datosIniciales - { idUser, fecha }
 */
async function fntGenerarPDF(datosIniciales) {
    if (!datosIniciales || !datosIniciales.fecha || !datosIniciales.idUser) {
        notifi("No hay datos suficientes para generar el reporte.", "error");
        return;
    }

    // --- Pregunta 1: tipo de reporte (afecta si hay columna Divisa o se convierte a Bs) ---
    const { value: reportType } = await Swal.fire({
        title: 'Tipo de reporte',
        input: 'radio',
        inputOptions: {
            'unificado': 'Unificado (Divisas convertidas a Bs)',
            'divisa':    'Divisa (con columna separada para Divisas $)'
        },
        inputValue: 'unificado',
        inputValidator: (value) => {
            if (!value) return '¡Necesitas elegir una opción!';
        },
        confirmButtonText: 'Siguiente',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false
    });

    if (!reportType) return;

    // --- Pregunta 2: qué PDFs generar (checkboxes) ---
    const { value: seleccion } = await Swal.fire({
        title: '¿Qué PDFs desea generar?',
        html: `
            <div style="text-align:left; font-size:13px; line-height:2;">
                <p style="margin:0 0 10px 0; color:#666;">
                    <i>Se abrirá una pestaña por cada PDF seleccionado</i>
                </p>
                <label style="display:block; cursor:pointer;">
                    <input type="checkbox" id="pdfResumen" checked style="margin-right:8px;">
                    <b>Resumen</b> — Cuadro de totales y desgloses (sin tickets)
                </label>
                <label style="display:block; cursor:pointer;">
                    <input type="checkbox" id="pdfVentas" checked style="margin-right:8px;">
                    <b>Listado Unificado</b> — Todas las ventas en una tabla
                </label>
                <label style="display:block; cursor:pointer;">
                    <input type="checkbox" id="pdfGasolina" style="margin-right:8px;">
                    <b>Listado Gasolina</b> — Solo ventas de gasolina
                </label>
                <label style="display:block; cursor:pointer;">
                    <input type="checkbox" id="pdfDiesel" style="margin-right:8px;">
                    <b>Listado Diesel</b> — Solo ventas de diesel
                </label>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Generar PDFs',
        cancelButtonText: 'Cancelar',
        allowOutsideClick: false,
        preConfirm: () => {
            return {
                resumen:  document.getElementById('pdfResumen').checked,
                ventas:   document.getElementById('pdfVentas').checked,
                gasolina: document.getElementById('pdfGasolina').checked,
                diesel:   document.getElementById('pdfDiesel').checked
            };
        }
    });

    if (!seleccion) return;

    const ningunaSeleccionada = !seleccion.resumen && !seleccion.ventas && !seleccion.gasolina && !seleccion.diesel;
    if (ningunaSeleccionada) {
        notifi("Debes seleccionar al menos un PDF.", "warning");
        return;
    }

    try {
        const url = base_url + "estacion/generarReportePdf";
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                fecha: datosIniciales.fecha,
                idUser: datosIniciales.idUser,
                reportType: reportType
            })
        });
        const res = await response.json();

        if (!res.success) {
            notifi(res.message || "Error al obtener los datos del reporte.", "error");
            return;
        }

        // Detectar qué combustibles hay realmente en el detallado
        const dataDetallado = res.data.dataDetallado || [];
        const hayGasolina = dataDetallado.some(v => _clasificarCombustible(v) === 'gasolina');
        const hayDiesel   = dataDetallado.some(v => _clasificarCombustible(v) === 'diesel');

        // Armar la lista de secciones a generar
        const secciones = [];
        if (seleccion.resumen)  secciones.push('resumen');
        if (seleccion.ventas)   secciones.push('ventas');
        if (seleccion.gasolina && hayGasolina) secciones.push('gasolina');
        if (seleccion.diesel   && hayDiesel)   secciones.push('diesel');

        if (secciones.length === 0) {
            notifi("No hay datos para generar ningún PDF.", "warning");
            return;
        }

        if ((seleccion.gasolina && !hayGasolina) || (seleccion.diesel && !hayDiesel)) {
            notifi("Algunos PDFs se omitieron porque no hubo ventas de ese combustible.", "info");
        }

        // Generar cada PDF con 700ms de separación para evitar bloqueo de popups
        secciones.forEach((seccion, i) => {
            setTimeout(() => {
                _abrirPdfSeccion(res.data, seccion);
            }, i * 700);
        });

        notifi(`Generando ${secciones.length} PDF(s). Si el navegador bloquea alguna pestaña, permite las ventanas emergentes.`, 'info');

    } catch (error) {
        console.error('Error en fntGenerarPDF:', error);
        notifi("Ocurrió un error al generar el PDF.", "error");
    }
}
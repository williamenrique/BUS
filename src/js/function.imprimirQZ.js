/**
 * Imprime un reporte detallado de ventas del día utilizando QZ Tray.
 * @param {Array} dataVentas - Un array de objetos, donde cada objeto es una venta (estructura de detallado.php).
 */
async function fntImprimirDetallado(dataVentas) {
    if (!dataVentas || dataVentas.length === 0) {
        notifi("No hay datos para imprimir el detallado.", "error");
        return;
    }

    const primerVenta = dataVentas[0];

    const data = [
        '\x1B' + '\x40', // Inicializar impresora
        '\x1B' + '\x61' + '\x31', // Centrar
        `${primerVenta.estacion}\n`,
        'REPORTE DETALLADO DE VENTAS\n',
        '\x1B' + '\x61' + '\x30', // Izquierda
        '--------------------------------\n',
        `Fecha: ${primerVenta.fecha_venta}\n`,
        `Operador: ${primerVenta.operador}\n`,
        `Tasa del Dia: ${parseFloat(primerVenta.tasa_dia).toFixed(2)} Bs\n`,
        '--------------------------------\n',
        '#  | Vehiculo  | Monto Bs | Litros\n',
        '--------------------------------\n',
    ];

    // 1. Calcular totales y separar ventas por tipo de combustible
    let totalLitros = 0;
    let totalLitrosGas = 0;
    let totalLitrosDie = 0;
    let totalMontoBs = 0;
    const ventasGasolina = [];
    const ventasDiesel = [];

    for (const venta of dataVentas) {
        // Separar
        if (venta.tipo_combustible == 2) {
            ventasDiesel.push(venta);
            totalLitrosDie += parseFloat(venta.cantidad_litros);
        } else {
            ventasGasolina.push(venta);
            totalLitrosGas += parseFloat(venta.cantidad_litros);
        }

        // Calcular totales
        totalLitros += parseFloat(venta.cantidad_litros);
        let montoVentaBs = 0;
        if (venta.tipo_pago === "Efectivo Divisa") {
            montoVentaBs = parseFloat(venta.monto) * parseFloat(venta.tasa_dia);
        } else {
            montoVentaBs = parseFloat(venta.monto);
        }
        totalMontoBs += montoVentaBs;
    }

    // 2. Función auxiliar para generar las líneas de impresión
    const generarLineas = (ventas) => {
        let lineas = [];
        for (const venta of ventas) {
            let montoVentaBs = 0;
            if (venta.tipo_pago === "Efectivo Divisa") {
                montoVentaBs = parseFloat(venta.monto) * parseFloat(venta.tasa_dia);
            } else {
                montoVentaBs = parseFloat(venta.monto);
            }
            const tipoLetra = venta.tipo_combustible == 2 ? '(D)' : '(G)';
            const vehiculoStr = `${venta.tipo_vehiculo.substring(0, 6)} ${tipoLetra}`;
            const linea = [
                venta.numero_venta.toString().padEnd(3),
                '|',
                vehiculoStr.padEnd(10),
                '|',
                montoVentaBs.toFixed(2).padStart(9),
                '|',
                parseFloat(venta.cantidad_litros).toFixed(2).padStart(5) + 'L'
            ].join(' ');
            lineas.push(linea + '\n');
        }
        return lineas;
    };

    // 3. Añadir las ventas de Gasolina
    if (ventasGasolina.length > 0) {
        data.push('\x1B' + '\x61' + '\x31'); // Centrar
        data.push('VENTAS GASOLINA\n');
        data.push('\x1B' + '\x61' + '\x30'); // Izquierda
        data.push('--------------------------------\n');
        data.push(...generarLineas(ventasGasolina));
    }

    // 4. Añadir las ventas de Diesel (si existen)
    if (ventasDiesel.length > 0) {
        data.push('--------------------------------\n');
        data.push('\x1B' + '\x61' + '\x31'); // Centrar
        data.push('VENTAS DIESEL\n');
        data.push('\x1B' + '\x61' + '\x30'); // Izquierda
        data.push('--------------------------------\n');
        data.push(...generarLineas(ventasDiesel));
    }

    data.push(
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01', // Negrita
        `Total Vehiculos: ${dataVentas.length}\n`,
        `Total Litros: ${totalLitros.toFixed(2)} L\n`,
        (totalLitrosGas > 0 ? `  Gasolina: ${totalLitrosGas.toFixed(2)} L\n` : ''),
        (totalLitrosDie > 0 ? `  Diesel: ${totalLitrosDie.toFixed(2)} L\n` : ''),
        `Total Vendido: ${totalMontoBs.toFixed(2)} Bs\n`,
        '\x1B' + '\x45' + '\x00', // No Negrita
        '--------------------------------\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00' // Comando de corte parcial
    );

    await _printToQZ(data);
}

/**
 * Imprime el reporte de cierre del día utilizando QZ Tray.
 * @param {Object} dataCierre - Objeto con los datos del cierre (estructura de cierre.php).
 */
async function fntImprimirCierre(dataCierre) {
    if (!dataCierre) {
        notifi('Datos de cierre incompletos para la impresión.', 'error');
        return;
    }

    // Calculamos el total de efectivo en Bs (incluyendo divisas convertidas)
    const totalEfectivoBs = parseFloat(dataCierre.total_general_bs) - parseFloat(dataCierre.total_debito);

    const data = [
        '\x1B' + '\x40', // Inicializar impresora
        '\x1B' + '\x61' + '\x31', // Centrar
        `${dataCierre.estacion}\n`,
        '\x1B' + '\x61' + '\x30', // Izquierda
        '\x1B' + '\x45' + '\x01', // Negrita
        '--------------------------------\n',
        `CIERRE DE OPERACIONES #${dataCierre.id_cierre}\n`, // Esta línea imprime el número de cierre
        '\x1B' + '\x45' + '\x00', // No Negrita
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
        (parseFloat(dataCierre.total_litros_gasolina) > 0 ? `  Gasolina: ${parseFloat(dataCierre.total_litros_gasolina).toFixed(2)} L\n` : ''),
        (parseFloat(dataCierre.total_litros_diesel) > 0 ? `  Diesel: ${parseFloat(dataCierre.total_litros_diesel).toFixed(2)} L\n` : ''),
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        'TOTALES EN BOLIVARES (Bs)\n',
        '\x1B' + '\x45' + '\x00',
        (totalEfectivoBs > 0 ? `Efectivo: ${totalEfectivoBs.toFixed(2)} Bs\n` : ''),
        (dataCierre.total_debito > 0 ? `Punto de Venta: ${parseFloat(dataCierre.total_debito).toFixed(2)} Bs\n` : ''),
        '--------------------------------\n',
        '\x1B' + '\x45' + '\x01',
        `Total General: ${parseFloat(dataCierre.total_general_bs).toFixed(2)} Bs\n`,
        '\x1B' + '\x45' + '\x00',
        '--------------------------------\n',
        '\x1B' + '\x61' + '\x31', // Centrar
        'Cierre realizado con exito\n',
        '\n\n\n', // Avanzar papel
        '\x1D' + '\x56' + '\x42' + '\x00' // Corte
    ];

    await _printToQZ(data);
}

/**
 * Imprime un ticket de venta individual utilizando QZ Tray.
 * @param {Object} datTicket - Objeto que contiene `ticketData` y `copia`.
 */
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

    const tipoCombustible = ticket.tipo_combustible == 2 ? 'Diesel' : 'Gasolina';

    const data = [
        '\x1B' + '\x40', // Inicializar
        '\x1B' + '\x61' + '\x31', // Centrar
        `${ticket.estacion}\n`,
        '\x1B' + '\x45' + '\x01', // Negrita
        `TICKET DE VENTA #${ticket.id_venta}\n`,
        '\x1B' + '\x45' + '\x00', // No Negrita
        '\x1B' + '\x61' + '\x30', // Izquierda
        '--------------------------------\n',
        `Fecha: ${ticket.fecha_venta} ${ticket.hora_venta}\n`,
        `Operador: ${ticket.personal_nombre || ''} ${ticket.personal_apellido || ''}\n`,
        '--------------------------------\n',
        `Pago: ${ticket.tipoPago}\n`,
        `Vehiculo: ${ticket.tipoVehiculo}\n`,
        `Combustible: ${tipoCombustible}\n`,
        '\x1B' + '\x61' + '\x31', // Centrar
        '\x1D\x21\x11', // Doble altura y ancho
        '\x1B\x45\x01', // Negrita
        `${parseFloat(ticket.litros).toFixed(2)} L\n`,
        '\x1B\x45\x00', // No Negrita
        '\x1D\x21\x00', // Tamaño normal
        `Monto: ${parseFloat(ticket.monto).toFixed(2)} ${simbolo}\n`,
        '\x1D\x21\x00', // Tamaño normal
        '\x1B\x45\x00', // No Negrita
        // `Monto: ${parseFloat(ticket.monto).toFixed(2)} ${simbolo}\n`,
        '--------------------------------\n',
        '\x1B\x61\x31', // Centrar
        'Gracias por su compra\n',
        esCopia ? '\x1B\x45\x01' + 'COPIA\n' : 'ORIGINAL\n',
        '\x1B\x45\x00',
        '\n\n\n',
        '\x1D' + '\x56' + '\x42' + '\x00' // Corte
    ];

    await _printToQZ(data);
}

/**
 * Inicializa la conexión con QZ Tray al cargar la página.
 */
async function initQZTrayConnection() {
    if (typeof qz !== 'undefined') {
        // --- INICIO DE LA MODIFICACIÓN ---
        // Configuración de seguridad para evitar los diálogos de confirmación.
        // Esto permite la impresión sin necesidad de firmar cada solicitud,
        // ideal para entornos de confianza donde el usuario ya ha permitido la conexión una vez.
        qz.security.setCertificatePromise(function (resolve, reject) {
            // En un entorno de producción, aquí se cargaría un certificado.
            // Para desarrollo o entornos locales, resolvemos con null para continuar.
            resolve(null);
        });
        qz.security.setSignaturePromise(function (toSign) {
            return function (resolve, reject) {
                // En un entorno de producción, aquí se firmaría el hash 'toSign'.
                // Para desarrollo, resolvemos sin firmar.
                resolve();
            };
        });
        // --- FIN DE LA MODIFICACIÓN ---

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

/**
 * Función interna para conectar con QZ Tray y enviar los datos de impresión.
 * @param {Array} data - Array con los comandos ESC/POS para la impresora.
 * @private
 */ //XP-80C o POS-58
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

// funcion para generar el pdf
async function fntGenerarPDF(datosIniciales) {
    if (!datosIniciales || !datosIniciales.fecha || !datosIniciales.idUser) {
        notifi("No hay datos suficientes para generar el reporte.", "error");
        return;
    }

    const {
        value: reportType
    } = await Swal.fire({
        title: 'Seleccione el tipo de reporte',
        input: 'radio',
        inputOptions: {
            'unificado': 'Unificado (Efectivo Bs + Divisas en Bs)',
            'divisa': 'Detallado (con columna para Divisas $)'
        },
        inputValidator: (value) => {
            if (!value) {
                return '¡Necesitas elegir una opción!'
            }
        },
        confirmButtonText: 'Generar PDF',
        showCancelButton: true,
        cancelButtonText: 'Cancelar'
    });

    if (reportType) {
        try {
            const url = base_url + "estacion/generarReportePdf";
            const data = {
                fecha: datosIniciales.fecha,
                idUser: datosIniciales.idUser,
                reportType: reportType
            };

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const res = await response.json();

            if (res.success) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = base_url + "data/estacion/reporte.php";
                form.target = '_blank';
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'reporteData';
                input.value = JSON.stringify(res.data);
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            } else {
                notifi(res.message || "Error al obtener los datos del reporte.", "error");
            }
        } catch (error) {
            console.error('Error en fntGenerarPDF:', error);
            notifi("Ocurrió un error al generar el PDF.", "error");
        }
    }
}
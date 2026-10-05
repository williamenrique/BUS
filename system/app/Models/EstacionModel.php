<?php
class EstacionModel extends Mysql {
    public function __construct(){
        parent::__construct();
    }
    /* * iniial data
    */
    public function selectTipoVehiculo(){
        $sql = "SELECT id_tipo_vehiculo, nombre FROM table_es_tipos_vehiculo WHERE status_tipo_vehiculo = 1";
        $request = $this->select_all($sql);
        return $request;
    }
    public function updateTasa(float $tasa, int $idEstacion, bool $isAdmin = false) {
        // --- INICIO DE LA CORRECCIÓN ---
        // La tabla de tasa ahora es global, no por estación.
        // Se actualiza el primer registro (id=1) o se crea si no existe.

        // 1. Verificar la última fecha de actualización
        // Si es administrador, saltamos la validación de fecha
        if (!$isAdmin) {
            $sql_check = "SELECT tasa_update FROM table_es_tasa_dia WHERE id_tasa_dia = 1";
            $last_update_data = $this->select($sql_check);
        
            if ($last_update_data && !empty($last_update_data['tasa_update'])) {
                try {
                    $last_update_date = new DateTime($last_update_data['tasa_update']);
                    $current_date = new DateTime();
        
                    // Compara solo la parte de la fecha (Y-m-d)
                    if ($last_update_date->format('Y-m-d') === $current_date->format('Y-m-d')) {
                        return 'already_updated'; // Indicador de que ya se actualizó hoy
                    }
                } catch (Exception $e) {
                    // Manejar error de fecha inválida si es necesario, aunque no debería ocurrir con DATETIME
                }
            }
        }
    
        // 2. Si no se ha actualizado hoy O es admin, proceder con la actualización
        $sql = "UPDATE table_es_tasa_dia SET tasa_dia = ?, tasa_update = NOW() WHERE id_tasa_dia = 1";
        $arrData = array($tasa);
        return $this->update($sql, $arrData);
        // --- FIN DE LA CORRECCIÓN ---
    }
    public function getTasa(int $idEstacion){
        // --- INICIO DE LA CORRECCIÓN ---
        // La tasa ahora es global, se obtiene el primer registro.
        $sql = "SELECT tasa_dia, tasa_update FROM table_es_tasa_dia WHERE id_tasa_dia = 1";
        $request = $this->select($sql);
        return $request;
        // --- FIN DE LA CORRECCIÓN ---
    }
    public function selectTipoPago(){
        $sql = "SELECT id_tipo_pago, nombre FROM table_es_tipos_pago WHERE status_tipo_pago = 1";
        $request = $this->select_all($sql);
        return $request;
    }
    public function getLastTicket(int $intIdUser, string $srtDate, int $idEstacion){
        $sql = "SELECT tVenta.*, tVehiculo.nombre AS tipoVehiculo, e.estacion FROM table_es_venta tVenta
                INNER JOIN table_es_tipos_vehiculo tVehiculo ON tVenta.id_tipo_vehiculo = tVehiculo.id_tipo_vehiculo
                INNER JOIN table_usuarios u ON tVenta.id_user = u.usuario_id
                LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
                WHERE tVenta.fecha_venta = ? AND tVenta.id_user = ? AND tVenta.status_ticket = 1";
        $params = [$srtDate, $intIdUser];
        if ($idEstacion > 0) {
            $sql .= " AND u.usuario_estacion_id = ?";
            $params[] = $idEstacion;
        }
        $sql .= " ORDER BY tVenta.id_venta DESC LIMIT 10";
        $request = $this->select_all($sql, $params);
        return $request;
    }
    public function getDetail(int $intIdUser, string $srtDate, int $idEstacion) {
        $sql = "SELECT
                    COUNT(v.id_venta) AS total_ventas,
                    COALESCE(SUM(v.litros), 0) AS total_litros,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto ELSE 0 END), 0) AS total_divisa,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END), 0) AS total_efectivo,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END), 0) AS total_debito,
                    -- Cálculo del total en Bolívares (divisa convertida + efectivo + débito)
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN CAST(v.monto AS DECIMAL(10,2)) * CAST(v.tasa_dia AS DECIMAL(10,2)) ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0) AS `total_bs`
                FROM table_es_venta v 
                INNER JOIN table_usuarios u ON v.id_user = u.usuario_id";
        $where = " WHERE v.fecha_venta = ? AND v.status_ticket = 1 AND v.id_user = ?"; // 2 placeholders
        $params = [$srtDate, $intIdUser]; // Should be 2 parameters
        if ($idEstacion > 0) {
            $where .= " AND u.usuario_estacion_id = ?";
            $params[] = $idEstacion;
        }
        $resumenGeneral = $this->select($sql . $where, $params);

        $sqlVehiculos = "SELECT
                            tv.nombre AS tipo_vehiculo,
                            COUNT(v.id_tipo_vehiculo) AS cantidad
                        FROM table_es_venta v
                        INNER JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        INNER JOIN table_usuarios u ON v.id_user = u.usuario_id";
        $whereVehiculos = " WHERE v.fecha_venta = ? AND v.status_ticket = 1 AND v.id_user = ?";
        $paramsVehiculos = [$srtDate, $intIdUser];
        if ($idEstacion > 0) {
            $whereVehiculos .= " AND u.usuario_estacion_id = ?";
            $paramsVehiculos[] = $idEstacion;
        }
        $sqlVehiculos .= $whereVehiculos . " GROUP BY v.id_tipo_vehiculo";
        $tiposVehiculo = $this->select_all($sqlVehiculos, $paramsVehiculos);
        $resumenGeneral['tiposVehiculo'] = $tiposVehiculo;
        return $resumenGeneral;
    }
    public function getPendingCierres(int $idUser, int $idEstacion){
        $sql = "SELECT c.id_cierre, c.fecha_cierre FROM table_es_cierre c
                INNER JOIN table_usuarios u ON c.id_user = u.usuario_id
                WHERE c.id_user = ? AND u.usuario_estacion_id = ? AND c.status_cierre = 0";
        $request = $this->select_all($sql, [$idUser, $idEstacion]);
        return $request;
    }
    public function getPendingVentas(int $idUser, int $idEstacion) {
        // --- INICIO DE LA CORRECCIÓN ---
        $sql = "SELECT DISTINCT fecha_venta, id_user
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON v.id_user = u.usuario_id
                WHERE v.id_user = ? AND v.fecha_venta != ? AND (v.id_cierre_diario IS NULL OR v.id_cierre_diario = 0)";
        
        $fechaActual = date("Y-m-d"); // Formato YYYY-MM-DD
        $params = [$idUser, $fechaActual];
        // Si no es admin, filtramos por su estación
        if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
            $sql .= " AND u.usuario_estacion_id = ?";
            $params[] = $idEstacion;
        }
        $sql .= " ORDER BY fecha_venta DESC";
        $request = $this->select_all($sql, $params);
        // --- FIN DE LA CORRECCIÓN ---
        return $request;
    }
    /* * end initial data
    */
    public function setVenta(int $useId, int $idEstacion, int $tipoVehiculo, float $litros, int $tipoPago, float $monto, float $tasa) {
        if ($idEstacion == 0) {
            // Un admin no puede registrar una venta si no tiene una estación seleccionada.
            return 0;
        }
        $fecha = date("Y-m-d");
        $hora = date("H:i:s"); // Esto es correcto para una columna TIME

        // --- INICIO DE LA CORRECCIÓN ---
        // Obtener el rol del usuario que realiza la venta, en lugar de usar una constante.
        $idRol = $this->select("SELECT usuario_rol_id FROM table_usuarios WHERE usuario_id = ?", [$useId])['usuario_rol_id'] ?? 0;
        // --- FIN DE LA CORRECCIÓN ---
        $statusTicket = 1;
        $idCierreDiario = 0;

        // --- INICIO DE LA CORRECCIÓN ---
        // Corregir la obtención del número de ticket, añadiendo el filtro por estación para evitar duplicados entre estaciones.
        $sql_count = "SELECT COUNT(v.id_venta) as total_ventas FROM table_es_venta v
                      INNER JOIN table_usuarios u ON v.id_user = u.usuario_id
                      WHERE v.fecha_venta = ? AND v.id_user = ? AND u.usuario_estacion_id = ?";
        $request_count = $this->select($sql_count, [$fecha, $useId, $idEstacion]);
        $numeroTicket = ($request_count['total_ventas'] ?? 0) + 1;

        $sql_insert = "INSERT INTO table_es_venta(id_venta, id_user, id_tipo_pago, id_tipo_vehiculo, litros, monto, id_cierre_diario,fecha_venta, hora_venta, tasa_dia, id_rol, status_ticket) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)";
        $arrData = [$numeroTicket, $useId, $tipoPago, $tipoVehiculo, $litros, $monto, $idCierreDiario, $fecha, $hora, $tasa, $idRol, $statusTicket];
        // echo $this->debugQuery($sql_insert, $arrData);
        $request_insert = $this->insert($sql_insert, $arrData);
        
        if ($request_insert == 0) {
            return $numeroTicket; // Devolver el número de ticket solo si la inserción fue exitosa
        }
        return $numeroTicket; // Devolver 0 si la inserción falló
    }
    // obtener la data despues de registrar venta o imprimir un ticket
    public function getTicketData(int $intIdVenta,int $intUser,string $srtFecha, int $idEstacion){
        $sql = "SELECT tVenta.*, tu.*, p.*, tv.nombre AS tipoVehiculo, tp.nombre AS tipoPago, e.estacion AS estacion
                FROM table_es_venta tVenta 
                        INNER JOIN table_usuarios tu ON tVenta.id_user = tu.usuario_id 
                        INNER JOIN table_personal p ON tu.usuario_id_personal = p.id_personal
                        INNER JOIN table_es_tipos_pago tp ON tVenta.id_tipo_pago = tp.id_tipo_pago
                        INNER JOIN table_es_tipos_vehiculo tv ON tVenta.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        INNER JOIN table_es_estacion e ON e.id_estacion = tu.usuario_estacion_id
                WHERE tVenta.id_venta = ? AND tVenta.fecha_venta = ? AND tVenta.id_user = ?";
        $params = [$intIdVenta, $srtFecha, $intUser];
        if ($idEstacion > 0) {
            $sql .= " AND tu.usuario_estacion_id = ?";
            $params[] = $idEstacion;
        }
        $request = $this->select($sql, $params);
        return $request;
    }
    // cerrar dia o alguno pendiente
    public function setDailyCierre(int $idUser, string $fechaCierre, int $idEstacion){
        // --- INICIO DE LA CORRECCIÓN ---
        $sql = "INSERT INTO table_es_cierre(id_user, fecha_cierre, id_estacion, status_cierre) VALUES (?, ?, ?, 1)";
        $request = $this->insert($sql, [$idUser, $fechaCierre, $idEstacion]);
        if($request > 0){
            // Se actualizan las ventas del usuario y fecha para asignarles el ID del cierre recién creado.
            $sql_update = "UPDATE table_es_venta SET id_cierre_diario = ?, status_ticket = 0 WHERE id_user = ? AND fecha_venta = ? AND status_ticket = 1";
            $this->update($sql_update, [$request, $idUser, $fechaCierre]);
        }
        return $request;
        // --- FIN DE LA CORRECCIÓN ---
    }
    // obtener data despues de cerrar dia o alguno pendiente
    public function getDataCierre(int $intIdUser, string $srtDate, int $idCierre) {
        // --- INICIO DE LA CORRECCIÓN ---
        // La firma del método ahora espera $idCierre en lugar de $idEstacion.
        // Usamos directamente el ID del cierre para obtener los datos, lo que es más preciso.
        if (empty($idCierre)) {
            return null; // No se encontró un cierre, no podemos generar el reporte.
        }

        // Usamos el método unificado getDatosParaReporte con el ID del cierre.
        return $this->getDatosParaReporte($idCierre);
        // --- FIN DE LA CORRECCIÓN ---
    }
    // obtener data para imprimir detallado de ventas
    public function getDetallado(int $intIdUser, string $srtDate, int $idEstacion, bool $forReport = false, string $reportType = 'unificado'){
        $this->intIdUser = $intIdUser;
        $this->srtDate = $srtDate;
        // --- INICIO DE LA CORRECCIÓN ---
        // Consulta optimizada y con alias consistentes para el PDF
        $sql = "SELECT 
                    v.id_venta AS numero_venta,
                    v.fecha_venta,
                    tv.nombre AS tipo_vehiculo,
                    v.litros AS cantidad_litros,
                    v.monto,
                    v.tasa_dia,
                    tp.nombre AS tipo_pago, -- Nombre del tipo de pago
                    -- Columna para Divisa ($)
                    CASE 
                        WHEN tp.id_tipo_pago = 1 THEN v.monto 
                        ELSE 0 
                    END AS monto_divisa,
                    -- Cálculo para 'efectivob': incluye efectivo en Bs y divisa convertida a Bs
                    CASE 
                        WHEN tp.id_tipo_pago = 1 AND '{$reportType}' = 'unificado' THEN v.monto * v.tasa_dia -- Divisa convertida (solo si es unificado)
                        WHEN tp.id_tipo_pago = 2 THEN v.monto -- Efectivo en Bs
                        ELSE 0 -- Otros tipos de pago no son efectivo
                    END AS efectivob,
                    -- Cálculo para 'tarjeta_debito': solo si el tipo de pago es débito
                    CASE 
                        WHEN tp.id_tipo_pago = 3 THEN v.monto 
                        ELSE 0 -- Otros tipos de pago no son débito
                    END AS tarjeta_debito,
                    CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS operador,
                    e.estacion
                FROM table_es_venta v
                JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                JOIN table_es_tipos_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
                JOIN table_usuarios u ON v.id_user = u.usuario_id
                JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
                WHERE v.fecha_venta = ? AND v.id_user = ? AND u.usuario_estacion_id = ?";
        $params = [$srtDate, $intIdUser, $idEstacion];
        if (!$forReport) { // Si no es para un reporte general, solo mostrar tickets abiertos
            $sql .= " AND v.status_ticket = 1";
        }
        $sql .= "
                ORDER BY 
                    v.id_venta ASC";
        return $this->select_all($sql, $params);
    }
    // metodo para obtener datos para pdf
    public function getDataVenta(string $srtDate, int $intIdUser, int $idEstacion, int $idCierre = null, bool $forReport = false){
        $sql = "SELECT 
            tVenta.id_venta AS numero_venta,
            tVenta.fecha_venta,
            tVenta.hora_venta,
            tvehiculo.nombre AS tipo_vehiculo,
            tVenta.litros AS cantidad_litros,
            tVenta.monto,
            tVenta.id_cierre_diario,
            tVenta.id_user,
            tVenta.tasa_dia,
            tpago.nombre AS tipo_pago, -- Nombre del tipo de pago
            -- Cálculo para 'efectivob': incluye efectivo en Bs y divisa convertida a Bs
            CASE 
                WHEN tpago.id_tipo_pago = 1 THEN tVenta.monto * tVenta.tasa_dia -- Divisa convertida
                WHEN tpago.id_tipo_pago = 2 THEN tVenta.monto -- Efectivo en Bs
                ELSE 0 -- Otros tipos de pago no son efectivo
            END AS efectivob,
            -- Cálculo para 'tarjeta_debito': solo si el tipo de pago es débito
            CASE 
                WHEN tpago.id_tipo_pago = 3 THEN tVenta.monto 
                ELSE 0 -- Otros tipos de pago no son débito
            END AS tarjeta_debito,
            CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS empleado
        FROM 
            table_es_venta tVenta
        JOIN 
            table_es_tipos_vehiculo tvehiculo ON tVenta.id_tipo_vehiculo = tvehiculo.id_tipo_vehiculo
        JOIN 
            table_es_tipos_pago tpago ON tVenta.id_tipo_pago = tpago.id_tipo_pago
        JOIN 
            table_usuarios u ON tVenta.id_user = u.usuario_id
        JOIN
            table_personal p ON u.usuario_id_personal = p.id_personal
        WHERE
            tVenta.fecha_venta = ? 
            AND tVenta.id_user = ? AND u.usuario_estacion_id = ?";

        $params = [$srtDate, $intIdUser, $idEstacion];

        // --- CORRECCIÓN: Añadir filtro por id_cierre_diario si se proporciona ---
        if ($idCierre !== null && $idCierre > 0) {
            $sql .= " AND tVenta.id_cierre_diario = ?";
            $params[] = $idCierre;
        }
        $sql .= " ORDER BY tVenta.id_venta ASC";
        $request = $this->select_all($sql, $params);
        return $request;
    }
    // metodo para total del pdf
    public function getTotal(string $srtDate, int $intIdUser, int $idEstacion, bool $forReport = false, string $reportType = 'unificado'){
        $sql = "SELECT
                COUNT(CASE WHEN tVenta.id_tipo_vehiculo = 1 THEN 0 END) AS Automovil,
                COUNT(CASE WHEN tVenta.id_tipo_vehiculo = 2 THEN 0 END) AS Motocicleta,
                COUNT(CASE WHEN tVenta.id_tipo_vehiculo = 3 THEN 0 END) AS Camion,
                tVenta.fecha_venta AS fecha,
                COUNT(*) AS total_ventas,
                tVenta.tasa_dia AS tasa_dia,
                CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS empleado,
                e.estacion as estacion,
                SUM(CASE WHEN tVenta.id_tipo_vehiculo = 1 THEN litros ELSE 0 END) AS litrosAuto,
                SUM(CASE WHEN tVenta.id_tipo_vehiculo = 2 THEN litros ELSE 0 END) AS litrosMoto,
                SUM(CASE WHEN tVenta.id_tipo_vehiculo = 3 THEN litros ELSE 0 END) AS litrosCamion,
                SUM(monto) AS total_general,
                SUM(litros) AS total_litros,
                SUM(CASE WHEN id_tipo_pago = 1 THEN monto ELSE 0 END) AS total_divisa,
                SUM(CASE WHEN id_tipo_pago = 3 THEN monto ELSE 0 END) AS total_debito,
                SUM(
                    CASE 
                        WHEN id_tipo_pago = 1 AND '{$reportType}' = 'unificado' THEN monto * CAST(tVenta.tasa_dia AS DECIMAL(10,2)) -- Divisa a Bs (solo si es unificado)
                        WHEN id_tipo_pago = 2 THEN monto -- Efectivo Bs
                        ELSE 0 
                    END
                ) AS total_efectivo_bs -- Suma de Divisa convertida y Efectivo Bs
                FROM table_es_venta tVenta
                JOIN table_es_tipos_vehiculo tVehiculo ON tVenta.id_tipo_vehiculo = tVehiculo.id_tipo_vehiculo
                JOIN table_usuarios u ON tVenta.id_user = u.usuario_id
                JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                LEFT JOIN table_es_estacion e ON e.id_estacion = u.usuario_estacion_id
                WHERE tVenta.fecha_venta = ? 
                AND tVenta.id_user = ? AND u.usuario_estacion_id = ?";
        $params = [$srtDate, $intIdUser, $idEstacion];
        if (!$forReport) { // Si no es para un reporte general, solo mostrar tickets abiertos
            $sql .= " AND tVenta.status_ticket = 1";
        }
        $sql .= "
                GROUP BY tVenta.fecha_venta, p.personal_nombre, p.personal_apellido, e.estacion";
        $request = $this->select($sql, $params);
        return $request;
    }

    public function getLitrosTotalesSistema() {
        $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros FROM table_es_venta";
        $request = $this->select($sql);
        return $request['total_litros'] ?? 0;
    }
    // En EstacionModel.php, mejorar la función existente:
    public function getLitrosPorFecha(string $srtDate, string $type = 'day', string $endDate = null) {
        if ($type === 'month') {
            if (!empty($endDate)) {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros 
                        FROM table_es_venta 
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') BETWEEN ? AND ?";
                $request = $this->select($sql, [$srtDate, $endDate]);
            } else {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros 
                        FROM table_es_venta 
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ?";
                $request = $this->select($sql, [$srtDate]);
            }
        } else {
            if (!empty($endDate) && $endDate != $srtDate) {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros 
                        FROM table_es_venta 
                        WHERE fecha_venta BETWEEN ? AND ?";
                $request = $this->select($sql, [$srtDate, $endDate]);
            } else {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros 
                        FROM table_es_venta 
                        WHERE fecha_venta = ?";
                $request = $this->select($sql, [$srtDate]);
            }
        }
        return $request['total_litros'] ?? 0;
    }

    public function selectReporteLitros(string $fecha, string $type, string $fechaFin = null) {
        if ($type === 'month') {
            if (!empty($fechaFin)) {
                // Reporte por rango de meses: Agrupar por mes
                $sql = "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as mes, COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) as total_litros 
                        FROM table_es_venta 
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') BETWEEN ? AND ?
                        GROUP BY mes
                        ORDER BY mes ASC";
                $request = $this->select_all($sql, [$fecha, $fechaFin]);
            } else {
                // Reporte de un solo mes: Detalle diario
                $sql = "SELECT fecha_venta, COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) as total_litros 
                        FROM table_es_venta 
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ?
                        GROUP BY fecha_venta
                        ORDER BY fecha_venta ASC";
                $request = $this->select_all($sql, [$fecha]);
            }
        } else {
            if (!empty($fechaFin) && $fechaFin != $fecha) {
                // Reporte por rango de días: Agrupar por tipo de vehículo (resumen del periodo)
                $sql = "SELECT tv.nombre as tipo_vehiculo, COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) as total_litros, COUNT(*) as cantidad_ventas
                        FROM table_es_venta v
                        JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        WHERE v.fecha_venta BETWEEN ? AND ?
                        GROUP BY tv.nombre";
                $request = $this->select_all($sql, [$fecha, $fechaFin]);
            } else {
                // Reporte de un solo día
                $sql = "SELECT tv.nombre as tipo_vehiculo, COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) as total_litros, COUNT(*) as cantidad_ventas
                        FROM table_es_venta v
                        JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        WHERE v.fecha_venta = ?
                        GROUP BY tv.nombre";
                $request = $this->select_all($sql, [$fecha]);
            }
        }
        return $request;
    }

    // mostrar historia de cierres para mantenimiento
    public function getHistorialCierres() {
        $sql = "SELECT 
                c.id_cierre,
                c.fecha_cierre, 
                c.id_user,
                p.personal_nombre AS usuario_nombres,
                p.personal_apellido AS usuario_apellidos,
                u.usuario_estacion_id as id_estacion,
                v.tasa_dia,
                (COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN CAST(v.monto AS DECIMAL(10,2)) * CAST(v.tasa_dia AS DECIMAL(10,2)) ELSE 0 END), 0) +
                COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0)) AS `efectivo_bs`,
                COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0) AS `debito_bs`,
                ((COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN CAST(v.monto AS DECIMAL(10,2)) * CAST(v.tasa_dia AS DECIMAL(10,2)) ELSE 0 END), 0) +
                COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0)) +
                COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0)) AS `total_bs`,
                COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS `total_litros_vendidos`
                FROM table_es_cierre c
                INNER JOIN table_usuarios u ON c.id_user = u.usuario_id
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                INNER JOIN table_es_venta v ON c.id_cierre = v.id_cierre_diario
                GROUP BY c.id_cierre, c.fecha_cierre, c.id_user, p.personal_apellido, p.personal_nombre, v.tasa_dia
                ORDER BY c.fecha_cierre DESC, c.id_user";

        $request = $this->select_all($sql);
        return $request;
    }
    // obtener ventas del dia seleccionado por usuario y cierre
    public function getVentasByCierre($idCierre,$idUser,$fechaCierre) {
        $sql = "SELECT 
                tVenta.*,
                tv.nombre as tipo_vehiculo,
                tp.nombre as tipo_pago
                FROM table_es_venta tVenta
                JOIN table_es_tipos_vehiculo tv ON tVenta.id_tipo_vehiculo = tv.id_tipo_vehiculo
                JOIN table_es_tipos_pago tp ON tVenta.id_tipo_pago = tp.id_tipo_pago
                WHERE tVenta.fecha_venta = ?
                AND tVenta.id_user =  ?
                AND tVenta.id_cierre_diario = ?";
        return  $this->select_all($sql, [$fechaCierre,$idUser]);
    }
    // obtener ventas abiertas (en curso) de un usuario y fecha
    public function getVentasAbiertas(string $srtDate, int $intIdUser, int $idEstacion){
        $sql = "SELECT 
            tVenta.id_venta AS numero_venta,
            tVenta.fecha_venta,
            tVenta.hora_venta,
            tvehiculo.nombre AS tipo_vehiculo,
            tVenta.litros AS cantidad_litros,
            tVenta.monto,
            tVenta.id_cierre_diario,
            tVenta.id_user,
            tVenta.tasa_dia,
            tpago.nombre AS tipo_pago,
            CASE 
                WHEN tpago.id_tipo_pago = 1 THEN tVenta.monto * tVenta.tasa_dia
                WHEN tpago.id_tipo_pago = 2 THEN tVenta.monto
                ELSE 0
            END AS efectivob,
            CASE 
                WHEN tpago.id_tipo_pago = 3 THEN tVenta.monto 
                ELSE 0
            END AS tarjeta_debito,
            CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS empleado
        FROM 
            table_es_venta tVenta
        JOIN 
            table_es_tipos_vehiculo tvehiculo ON tVenta.id_tipo_vehiculo = tvehiculo.id_tipo_vehiculo
        JOIN 
            table_es_tipos_pago tpago ON tVenta.id_tipo_pago = tpago.id_tipo_pago
        JOIN 
            table_usuarios u ON tVenta.id_user = u.usuario_id
        JOIN
            table_personal p ON u.usuario_id_personal = p.id_personal
        WHERE
            tVenta.fecha_venta = ? 
            AND tVenta.id_user = ? 
            AND u.usuario_estacion_id = ?
            AND tVenta.status_ticket = 1
        ORDER BY tVenta.id_venta ASC";

        $params = [$srtDate, $intIdUser, $idEstacion];
        $request = $this->select_all($sql, $params);
        return $request;
    }
    // obtener datos para reporte detallado de cierre sin cerrar
     public function getDatosParaReporte($idCierre) {
        // Consulta unificada que calcula los totales directamente desde las ventas asociadas al cierre.
        $sql = "SELECT 
                    c.id_cierre,
                    c.fecha_cierre,
                    p.personal_nombre AS usuario_nombres,
                    p.personal_apellido AS usuario_apellidos,
                    e.estacion,
                    -- Usamos MAX para obtener un solo valor de tasa_dia, asumiendo que es constante por cierre
                    MAX(v.tasa_dia) AS tasa_dia,
                    COUNT(v.id_venta) AS total_ventas,
                    SUM(v.litros) AS total_litros,
                    
                    -- Cálculos de montos por tipo de pago
                    SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto ELSE 0 END) AS total_divisa,
                    SUM(CASE WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END) AS total_efectivo,
                    SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END) AS total_debito,
                    
                    -- Cálculo del total general en Bolívares
                    SUM(
                        CASE 
                            WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia -- Divisa a Bs
                            ELSE v.monto -- Efectivo Bs y Débito Bs
                        END
                    ) AS total_general_bs,

                    -- Conteo por tipo de vehículo
                    COUNT(CASE WHEN v.id_tipo_vehiculo = 1 THEN 1 END) AS cant_auto,
                    COUNT(CASE WHEN v.id_tipo_vehiculo = 2 THEN 1 END) AS cant_moto,
                    COUNT(CASE WHEN v.id_tipo_vehiculo = 3 THEN 1 END) AS cant_camion
                FROM table_es_cierre c
                JOIN table_es_venta v ON c.id_cierre = v.id_cierre_diario
                JOIN table_usuarios u ON c.id_user = u.usuario_id
                JOIN table_personal p ON u.usuario_id_personal = p.id_personal 
                LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion 
                WHERE c.id_cierre = ?
                GROUP BY c.id_cierre, c.fecha_cierre, p.personal_nombre, p.personal_apellido, e.estacion";
        return $this->select($sql, [$idCierre]);
    }
    public function deleteVenta($idVenta, $srtFecha, $idUser) {
        // --- INICIO DE LA CORRECCIÓN ---
        // La `id_venta` es un contador diario por usuario, por lo que la combinación
        // de id_venta, fecha_venta y id_user es la clave única para una venta.
        // La consulta original ya era correcta en su lógica, pero la firma del método tenía una coma extra.
        // Se limpia la firma y se asegura que los parámetros se usen correctamente.
        $sql = "DELETE FROM table_es_venta WHERE id_venta = ? AND fecha_venta = ? AND id_user = ?";
        return $this->delete($sql, [$idVenta, $srtFecha, $idUser]);
        // --- FIN DE LA CORRECCIÓN ---
    }
    // mostrar datos para lista
    public function getFechasConVentas() {
        $sql = "SELECT DISTINCT fecha_venta FROM table_es_venta ORDER BY fecha_venta DESC";
        return $this->select_all($sql);
    }
    /**
     * Elimina un cierre y reinicia las ventas asociadas sin usar transacciones.
     * @param {int} idCierre - ID del cierre diario a eliminar
     * @param {int} idUsuario - ID del usuario del cierre
     * @param {int} idEstacion - ID de la estación del cierre
     * @param {string} fechaCierre - Fecha del cierre
     * @return {bool} - True si la operación es exitosa
     */
    public function deleteCierreAndResetVentas(int $idCierre, int $idUsuario, int $idEstacion, string $fechaCierre) {
            // 1. Actualizar tabla de ventas usando JOIN con table_usuarios para el id_estacion
            $sqlUpdateVentas = "UPDATE table_es_venta v JOIN table_usuarios u ON v.id_user = u.usuario_id 
                                SET v.id_cierre_diario = 0, v.status_ticket = 1
                                WHERE v.id_cierre_diario = ? AND v.fecha_venta = ? AND u.usuario_estacion_id = ?";
            
            // Los parámetros para esta consulta
            $arrDataVentas = array($idCierre, $fechaCierre, $idEstacion);
            
            // Verificamos si la actualización de ventas es exitosa
            $updateVentas = $this->update($sqlUpdateVentas, $arrDataVentas);
            
            // Si la actualización de ventas falló, detenemos la ejecución y retornamos false.
            if (!$updateVentas) {
                return false;
            }
            // 2. Si la actualización fue exitosa, procedemos a eliminar el registro de cierre.
            $sqlDeleteCierre = "DELETE FROM table_es_cierre WHERE id_cierre = ? AND id_user = ? AND fecha_cierre = ?";
            $arrDataCierre = array($idCierre, $idUsuario, $fechaCierre);
            
            // Retornamos el resultado de la eliminación
            // echo $this->debugQuery($sqlDeleteCierre, $arrDataCierre);
            return $this->delete($sqlDeleteCierre, $arrDataCierre);
    }
    /**
     * Obtiene una lista de ventas con estatus = 1 (abiertas)
     * @return array
     */
    public function getOpenSales() {
        $sql = "SELECT 
            v.id_user, 
            v.fecha_venta,
            SUM(CAST(v.litros AS DECIMAL(10,2))) AS total_litros,
            p.personal_nombre AS nombre,
            p.personal_apellido AS apellido
        FROM table_es_venta v
        JOIN table_usuarios u ON v.id_user = u.usuario_id
        JOIN table_personal p ON u.usuario_id_personal = p.id_personal
        WHERE v.status_ticket = 1
        GROUP BY v.id_user, v.fecha_venta, p.personal_nombre, p.personal_apellido
        ORDER BY v.fecha_venta DESC";
        $request = $this->select_all($sql);
        return $request;
    }

    /**
     * Cierra una venta cambiando su status a 0
     * @param int $idVenta
     * @return bool
     */
    public function closeSale(int $idVenta) {
        $sql = "UPDATE table_es_venta SET status_ticket = 0 WHERE id_venta = ?";
        $arrData = array($idVenta);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    /**
     * Verifica de forma rápida si un usuario tiene ventas abiertas en una fecha específica.
     * @param int $idUser
     * @param string $fecha
     * @param int $idEstacion
     * @return bool
     */
    public function hasOpenSales(int $idUser, string $fecha, int $idEstacion): bool {
        $sql = "SELECT 1 FROM table_es_venta v
                INNER JOIN table_usuarios u ON v.id_user = u.usuario_id
                WHERE v.id_user = ? AND v.fecha_venta = ? AND u.usuario_estacion_id = ? AND v.status_ticket = 1 LIMIT 1";
        return !empty($this->select($sql, [$idUser, $fecha, $idEstacion]));
    }

    /**
     * Obtiene datos básicos de un usuario, como su estación.
     * @param int $idUser
     * @return array|false
     */
    public function getUsuario(int $idUser) {
        $sql = "SELECT usuario_id, usuario_estacion_id FROM table_usuarios WHERE usuario_id = ?";
        return $this->select($sql, [$idUser]);
    }

    /**
     * Elimina todas las ventas abiertas (status_ticket = 1) de un usuario en una fecha específica.
     * @param int $idUser
     * @param string $fecha
     * @return bool
     */
    public function deleteAllOpenSales(int $idUser, string $fecha) {
        $sql = "DELETE FROM table_es_venta WHERE id_user = ? AND fecha_venta = ? AND status_ticket = 1";
        return $this->delete($sql, [$idUser, $fecha]);
    }

    /**
     * Elimina un cierre y TODAS las ventas asociadas a él.
     * @param int $idCierre
     * @param int $idUser
     * @param string $fecha
     * @return bool
     */
    public function deleteCierreTotal(int $idCierre, int $idUser, string $fecha) {
        // 1. Eliminar ventas asociadas al cierre, verificando usuario y fecha
        $sqlVentas = "DELETE FROM table_es_venta WHERE id_cierre_diario = ? AND id_user = ? AND fecha_venta = ?";
        $this->delete($sqlVentas, [$idCierre, $idUser, $fecha]);

        // 2. Eliminar el registro de cierre
        $sqlCierre = "DELETE FROM table_es_cierre WHERE id_cierre = ? AND id_user = ? AND fecha_cierre = ?";
        return $this->delete($sqlCierre, [$idCierre, $idUser, $fecha]);
    }
}
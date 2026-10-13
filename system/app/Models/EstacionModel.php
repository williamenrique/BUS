<?php
class EstacionModel extends Mysql {
    public function __construct(){
        parent::__construct();
    }

    /* ==========================================================
     * INICIO: TIPOS DE COMBUSTIBLE
     * ========================================================== */
    /**
     * Devuelve los tipos de combustible activos (Gasolina, Diesel, ...)
     * @return array
     */
    public function selectTipoCombustible(){
        $sql = "SELECT id_tipo_combustible, nombre FROM table_es_tipos_combustible WHERE status_tipo_combustible = 1";
        return $this->select_all($sql);
    }
    /* ==========================================================
     * FIN: TIPOS DE COMBUSTIBLE
     * ========================================================== */

    /* * iniial data
    */
    public function selectTipoVehiculo(){
        $sql = "SELECT id_tipo_vehiculo, nombre FROM table_es_tipos_vehiculo WHERE status_tipo_vehiculo = 1";
        $request = $this->select_all($sql);
        return $request;
    }

    public function updateTasa(float $tasa, int $idEstacion, bool $isAdmin = false) {
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
                    // Manejar error de fecha inválida si es necesario
                }
            }
        }

        // 2. Si no se ha actualizado hoy O es admin, proceder con la actualización
        $sql = "UPDATE table_es_tasa_dia SET tasa_dia = ?, tasa_update = NOW() WHERE id_tasa_dia = 1";
        $arrData = array($tasa);
        return $this->update($sql, $arrData);
    }

    public function getTasa(int $idEstacion){
        // La tasa ahora es global, se obtiene el primer registro.
        $sql = "SELECT tasa_dia, tasa_update FROM table_es_tasa_dia WHERE id_tasa_dia = 1";
        $request = $this->select($sql);
        return $request;
    }

    public function selectTipoPago(){
        $sql = "SELECT id_tipo_pago, nombre FROM table_es_tipos_pago WHERE status_tipo_pago = 1";
        $request = $this->select_all($sql);
        return $request;
    }

    public function getLastTicket(int $intIdUser, string $srtDate, int $idEstacion){
        $sql = "SELECT tVenta.*, tVehiculo.nombre AS tipoVehiculo,
                       tc.nombre AS tipoCombustible,
                       e.estacion
                FROM table_es_venta tVenta
                INNER JOIN table_es_tipos_vehiculo tVehiculo ON tVenta.id_tipo_vehiculo = tVehiculo.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tVenta.id_tipo_combustible = tc.id_tipo_combustible
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
                    -- Desglose por tipo de combustible (litros)
                    COALESCE(SUM(CASE WHEN v.id_tipo_combustible = 1 THEN v.litros ELSE 0 END), 0) AS total_litros_gasolina,
                    COALESCE(SUM(CASE WHEN v.id_tipo_combustible = 2 THEN v.litros ELSE 0 END), 0) AS total_litros_diesel,
                    -- Cálculo del total en Bolívares (divisa convertida + efectivo + débito)
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN CAST(v.monto AS DECIMAL(10,2)) * CAST(v.tasa_dia AS DECIMAL(10,2)) ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0) +
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0) AS `total_bs`
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON v.id_user = u.usuario_id";
        $where = " WHERE v.fecha_venta = ? AND v.status_ticket = 1 AND v.id_user = ?";
        $params = [$srtDate, $intIdUser];
        if ($idEstacion > 0) {
            $where .= " AND u.usuario_estacion_id = ?";
            $params[] = $idEstacion;
        }
        $resumenGeneral = $this->select($sql . $where, $params);

        // Desglose por tipo de vehículo
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

        // Desglose por tipo de combustible
        $sqlCombustibles = "SELECT
                                tc.id_tipo_combustible,
                                tc.nombre AS tipo_combustible,
                                COUNT(v.id_venta) AS cantidad,
                                COALESCE(SUM(v.litros), 0) AS litros
                            FROM table_es_venta v
                            INNER JOIN table_es_tipos_combustible tc ON v.id_tipo_combustible = tc.id_tipo_combustible
                            INNER JOIN table_usuarios u ON v.id_user = u.usuario_id";
        $whereCombustibles = " WHERE v.fecha_venta = ? AND v.status_ticket = 1 AND v.id_user = ?";
        $paramsCombustibles = [$srtDate, $intIdUser];
        if ($idEstacion > 0) {
            $whereCombustibles .= " AND u.usuario_estacion_id = ?";
            $paramsCombustibles[] = $idEstacion;
        }
        $sqlCombustibles .= $whereCombustibles . " GROUP BY v.id_tipo_combustible ORDER BY tc.id_tipo_combustible ASC";
        $tiposCombustible = $this->select_all($sqlCombustibles, $paramsCombustibles);
        $resumenGeneral['tiposCombustible'] = $tiposCombustible;

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
        $sql = "SELECT DISTINCT fecha_venta, id_user
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON v.id_user = u.usuario_id
                WHERE v.id_user = ? AND v.fecha_venta != ? AND (v.id_cierre_diario IS NULL OR v.id_cierre_diario = 0)";

        $fechaActual = date("Y-m-d");
        $params = [$idUser, $fechaActual];
        if ($_SESSION['userData']['departamento_nombre'] != 'SISTEMA') {
            $sql .= " AND u.usuario_estacion_id = ?";
            $params[] = $idEstacion;
        }
        $sql .= " ORDER BY fecha_venta DESC";
        $request = $this->select_all($sql, $params);
        return $request;
    }
    /* * end initial data
    */

    /**
     * Registra una venta.
     * @param int $tipoCombustible 1=Gasolina, 2=Diesel
     */
    public function setVenta(int $useId, int $idEstacion, int $tipoVehiculo, int $tipoCombustible, float $litros, int $tipoPago, float $monto, float $tasa) {
        if ($idEstacion == 0) {
            // Un admin no puede registrar una venta si no tiene una estación seleccionada.
            return 0;
        }
        // Sanitizar combustible (por si llega 0 o null → Gasolina por defecto)
        if ($tipoCombustible <= 0) {
            $tipoCombustible = 1;
        }

        $fecha = date("Y-m-d");
        $hora = date("H:i:s");

        // Obtener el rol del usuario que realiza la venta
        $idRol = $this->select("SELECT usuario_rol_id FROM table_usuarios WHERE usuario_id = ?", [$useId])['usuario_rol_id'] ?? 0;

        $statusTicket = 1;
        $idCierreDiario = 0;

        // Corregir la obtención del número de ticket, con filtro por estación
        $sql_count = "SELECT COUNT(v.id_venta) as total_ventas FROM table_es_venta v
                      INNER JOIN table_usuarios u ON v.id_user = u.usuario_id
                      WHERE v.fecha_venta = ? AND v.id_user = ? AND u.usuario_estacion_id = ?";
        $request_count = $this->select($sql_count, [$fecha, $useId, $idEstacion]);
        $numeroTicket = ($request_count['total_ventas'] ?? 0) + 1;

        $sql_insert = "INSERT INTO table_es_venta(id_venta, id_user, id_tipo_pago, id_tipo_vehiculo, id_tipo_combustible, litros, monto, id_cierre_diario, fecha_venta, hora_venta, tasa_dia, id_rol, status_ticket) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $arrData = [$numeroTicket, $useId, $tipoPago, $tipoVehiculo, $tipoCombustible, $litros, $monto, $idCierreDiario, $fecha, $hora, $tasa, $idRol, $statusTicket];
        $request_insert = $this->insert($sql_insert, $arrData);

        // Se devuelve el número de ticket (el id_venta se asigna manualmente)
        return $numeroTicket;
    }

    // obtener la data despues de registrar venta o imprimir un ticket
    public function getTicketData(int $intIdVenta, int $intUser, string $srtFecha, int $idEstacion){
        $sql = "SELECT tVenta.*, tu.*, p.*,
                       tv.nombre AS tipoVehiculo,
                       tp.nombre AS tipoPago,
                       tc.nombre AS tipoCombustible,
                       e.estacion AS estacion
                FROM table_es_venta tVenta
                        INNER JOIN table_usuarios tu ON tVenta.id_user = tu.usuario_id
                        INNER JOIN table_personal p ON tu.usuario_id_personal = p.id_personal
                        INNER JOIN table_es_tipos_pago tp ON tVenta.id_tipo_pago = tp.id_tipo_pago
                        INNER JOIN table_es_tipos_vehiculo tv ON tVenta.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        INNER JOIN table_es_tipos_combustible tc ON tVenta.id_tipo_combustible = tc.id_tipo_combustible
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
        $sql = "INSERT INTO table_es_cierre(id_user, fecha_cierre, id_estacion, status_cierre) VALUES (?, ?, ?, 1)";
        $request = $this->insert($sql, [$idUser, $fechaCierre, $idEstacion]);
        if($request > 0){
            // Se actualizan las ventas del usuario y fecha para asignarles el ID del cierre recién creado.
            $sql_update = "UPDATE table_es_venta SET id_cierre_diario = ?, status_ticket = 0 WHERE id_user = ? AND fecha_venta = ? AND status_ticket = 1";
            $this->update($sql_update, [$request, $idUser, $fechaCierre]);
        }
        return $request;
    }

    // obtener data despues de cerrar dia o alguno pendiente
    public function getDataCierre(int $intIdUser, string $srtDate, int $idCierre) {
        // La firma del método ahora espera $idCierre en lugar de $idEstacion.
        if (empty($idCierre)) {
            return null;
        }
        // Usamos el método unificado getDatosParaReporte con el ID del cierre.
        return $this->getDatosParaReporte($idCierre);
    }

    // obtener data para imprimir detallado de ventas
    public function getDetallado(int $intIdUser, string $srtDate, int $idEstacion, bool $forReport = false, string $reportType = 'unificado'){
        // --- Consulta optimizada y con alias consistentes para el PDF ---
        $sql = "SELECT
                    v.id_venta AS numero_venta,
                    v.fecha_venta,
                    tv.nombre AS tipo_vehiculo,
                    tc.nombre AS tipo_combustible,
                    v.litros AS cantidad_litros,
                    v.monto,
                    v.tasa_dia,
                    tp.nombre AS tipo_pago,
                    -- Columna para Divisa ($)
                    CASE
                        WHEN tp.id_tipo_pago = 1 THEN v.monto
                        ELSE 0
                    END AS monto_divisa,
                    -- Cálculo para 'efectivob': incluye efectivo en Bs y divisa convertida a Bs
                    CASE
                        WHEN tp.id_tipo_pago = 1 AND '{$reportType}' = 'unificado' THEN v.monto * v.tasa_dia
                        WHEN tp.id_tipo_pago = 2 THEN v.monto
                        ELSE 0
                    END AS efectivob,
                    -- Cálculo para 'tarjeta_debito'
                    CASE
                        WHEN tp.id_tipo_pago = 3 THEN v.monto
                        ELSE 0
                    END AS tarjeta_debito,
                    CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS operador,
                    e.estacion
                FROM table_es_venta v
                JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                JOIN table_es_tipos_combustible tc ON v.id_tipo_combustible = tc.id_tipo_combustible
                JOIN table_es_tipos_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
                JOIN table_usuarios u ON v.id_user = u.usuario_id
                JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
                WHERE v.fecha_venta = ? AND v.id_user = ? AND u.usuario_estacion_id = ?";
        $params = [$srtDate, $intIdUser, $idEstacion];
        if (!$forReport) { // Si no es para un reporte general, solo mostrar tickets abiertos
            $sql .= " AND v.status_ticket = 1";
        }
        $sql .= " ORDER BY v.id_venta ASC";
        return $this->select_all($sql, $params);
    }

    // metodo para obtener datos para pdf
    public function getDataVenta(string $srtDate, int $intIdUser, int $idEstacion, int $idCierre = null, bool $forReport = false){
        $sql = "SELECT
            tVenta.id_venta AS numero_venta,
            tVenta.fecha_venta,
            tVenta.hora_venta,
            tvehiculo.nombre AS tipo_vehiculo,
            tc.nombre AS tipo_combustible,
            tVenta.litros AS cantidad_litros,
            tVenta.monto,
            tVenta.id_cierre_diario,
            tVenta.id_user,
            tVenta.tasa_dia,
            tpago.nombre AS tipo_pago,
            -- Cálculo para 'efectivob'
            CASE
                WHEN tpago.id_tipo_pago = 1 THEN tVenta.monto * tVenta.tasa_dia
                WHEN tpago.id_tipo_pago = 2 THEN tVenta.monto
                ELSE 0
            END AS efectivob,
            -- Cálculo para 'tarjeta_debito'
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
            table_es_tipos_combustible tc ON tVenta.id_tipo_combustible = tc.id_tipo_combustible
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

        // Añadir filtro por id_cierre_diario si se proporciona
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
                -- Desglose por tipo de combustible
                COUNT(CASE WHEN tVenta.id_tipo_combustible = 1 THEN 1 END) AS total_ventas_gasolina,
                COUNT(CASE WHEN tVenta.id_tipo_combustible = 2 THEN 1 END) AS total_ventas_diesel,
                SUM(CASE WHEN tVenta.id_tipo_combustible = 1 THEN litros ELSE 0 END) AS litros_gasolina,
                SUM(CASE WHEN tVenta.id_tipo_combustible = 2 THEN litros ELSE 0 END) AS litros_diesel,
                SUM(
                    CASE
                        WHEN id_tipo_pago = 1 AND '{$reportType}' = 'unificado' THEN monto * CAST(tVenta.tasa_dia AS DECIMAL(10,2))
                        WHEN id_tipo_pago = 2 THEN monto
                        ELSE 0
                    END
                ) AS total_efectivo_bs
                FROM table_es_venta tVenta
                JOIN table_es_tipos_vehiculo tVehiculo ON tVenta.id_tipo_vehiculo = tVehiculo.id_tipo_vehiculo
                JOIN table_es_tipos_combustible tc ON tVenta.id_tipo_combustible = tc.id_tipo_combustible
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

    /**
     * Obtiene el total de litros por fecha (o rango de fechas), opcionalmente filtrado por combustible.
     * @param string      $srtDate       Fecha o mes (YYYY-MM-DD o YYYY-MM según $type)
     * @param string      $type          'day' o 'month'
     * @param string|null $endDate       Fecha final (para rangos)
     * @param int|null    $idCombustible 1=Gasolina, 2=Diesel, null=todos
     */
    public function getLitrosPorFecha(string $srtDate, string $type = 'day', string $endDate = null, int $idCombustible = null) {
        if ($type === 'month') {
            if (!empty($endDate)) {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros
                        FROM table_es_venta
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') BETWEEN ? AND ?";
                $params = [$srtDate, $endDate];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $request = $this->select($sql, $params);
            } else {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros
                        FROM table_es_venta
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ?";
                $params = [$srtDate];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $request = $this->select($sql, $params);
            }
        } else {
            if (!empty($endDate) && $endDate != $srtDate) {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros
                        FROM table_es_venta
                        WHERE fecha_venta BETWEEN ? AND ?";
                $params = [$srtDate, $endDate];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $request = $this->select($sql, $params);
            } else {
                $sql = "SELECT COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) AS total_litros
                        FROM table_es_venta
                        WHERE fecha_venta = ?";
                $params = [$srtDate];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $request = $this->select($sql, $params);
            }
        }
        return $request['total_litros'] ?? 0;
    }

    /**
     * Reporte de litros. Puede filtrar por tipo de combustible.
     * @param int|null $idCombustible Si es null, no filtra (todos los combustibles).
     */
    public function selectReporteLitros(string $fecha, string $type, string $fechaFin = null, int $idCombustible = null) {
        if ($type === 'month') {
            if (!empty($fechaFin)) {
                // Reporte por rango de meses: Agrupar por mes
                $sql = "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') as mes, COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) as total_litros
                        FROM table_es_venta
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') BETWEEN ? AND ?";
                $params = [$fecha, $fechaFin];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $sql .= " GROUP BY mes ORDER BY mes ASC";
                $request = $this->select_all($sql, $params);
            } else {
                // Reporte de un solo mes: Detalle diario
                $sql = "SELECT fecha_venta, COALESCE(SUM(CAST(litros AS DECIMAL(10,2))), 0) as total_litros
                        FROM table_es_venta
                        WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ?";
                $params = [$fecha];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $sql .= " GROUP BY fecha_venta ORDER BY fecha_venta ASC";
                $request = $this->select_all($sql, $params);
            }
        } else {
            if (!empty($fechaFin) && $fechaFin != $fecha) {
                // Reporte por rango de días
                $sql = "SELECT tv.nombre as tipo_vehiculo, COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) as total_litros, COUNT(*) as cantidad_ventas
                        FROM table_es_venta v
                        JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        WHERE v.fecha_venta BETWEEN ? AND ?";
                $params = [$fecha, $fechaFin];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND v.id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $sql .= " GROUP BY tv.nombre";
                $request = $this->select_all($sql, $params);
            } else {
                // Reporte de un solo día
                $sql = "SELECT tv.nombre as tipo_vehiculo, COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) as total_litros, COUNT(*) as cantidad_ventas
                        FROM table_es_venta v
                        JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                        WHERE v.fecha_venta = ?";
                $params = [$fecha];
                if ($idCombustible !== null && $idCombustible > 0) {
                    $sql .= " AND v.id_tipo_combustible = ?";
                    $params[] = $idCombustible;
                }
                $sql .= " GROUP BY tv.nombre";
                $request = $this->select_all($sql, $params);
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
                COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS `total_litros_vendidos`,
                -- Desglose de litros por combustible
                COALESCE(SUM(CASE WHEN v.id_tipo_combustible = 1 THEN CAST(v.litros AS DECIMAL(10,2)) ELSE 0 END), 0) AS `litros_gasolina`,
                COALESCE(SUM(CASE WHEN v.id_tipo_combustible = 2 THEN CAST(v.litros AS DECIMAL(10,2)) ELSE 0 END), 0) AS `litros_diesel`
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
    public function getVentasByCierre($idCierre, $idUser, $fechaCierre) {
        $sql = "SELECT
                tVenta.*,
                tv.nombre as tipo_vehiculo,
                tc.nombre as tipo_combustible,
                tp.nombre as tipo_pago
                FROM table_es_venta tVenta
                JOIN table_es_tipos_vehiculo tv ON tVenta.id_tipo_vehiculo = tv.id_tipo_vehiculo
                JOIN table_es_tipos_combustible tc ON tVenta.id_tipo_combustible = tc.id_tipo_combustible
                JOIN table_es_tipos_pago tp ON tVenta.id_tipo_pago = tp.id_tipo_pago
                WHERE tVenta.fecha_venta = ?
                AND tVenta.id_user =  ?
                AND tVenta.id_cierre_diario = ?";
        return  $this->select_all($sql, [$fechaCierre, $idUser, $idCierre]);
    }

    // obtener ventas abiertas (en curso) de un usuario y fecha
    public function getVentasAbiertas(string $srtDate, int $intIdUser, int $idEstacion){
        $sql = "SELECT
            tVenta.id_venta AS numero_venta,
            tVenta.fecha_venta,
            tVenta.hora_venta,
            tvehiculo.nombre AS tipo_vehiculo,
            tc.nombre AS tipo_combustible,
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
            table_es_tipos_combustible tc ON tVenta.id_tipo_combustible = tc.id_tipo_combustible
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
                            WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                            ELSE v.monto
                        END
                    ) AS total_general_bs,

                    -- Conteo por tipo de vehículo
                    COUNT(CASE WHEN v.id_tipo_vehiculo = 1 THEN 1 END) AS cant_auto,
                    COUNT(CASE WHEN v.id_tipo_vehiculo = 2 THEN 1 END) AS cant_moto,
                    COUNT(CASE WHEN v.id_tipo_vehiculo = 3 THEN 1 END) AS cant_camion,

                    -- Desglose por tipo de combustible
                    COUNT(CASE WHEN v.id_tipo_combustible = 1 THEN 1 END) AS cant_gasolina,
                    COUNT(CASE WHEN v.id_tipo_combustible = 2 THEN 1 END) AS cant_diesel,
                    COALESCE(SUM(CASE WHEN v.id_tipo_combustible = 1 THEN v.litros ELSE 0 END), 0) AS litros_gasolina,
                    COALESCE(SUM(CASE WHEN v.id_tipo_combustible = 2 THEN v.litros ELSE 0 END), 0) AS litros_diesel
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
        // La `id_venta` es un contador diario por usuario, por lo que la combinación
        // de id_venta, fecha_venta y id_user es la clave única para una venta.
        $sql = "DELETE FROM table_es_venta WHERE id_venta = ? AND fecha_venta = ? AND id_user = ?";
        return $this->delete($sql, [$idVenta, $srtFecha, $idUser]);
    }

    // mostrar datos para lista
    public function getFechasConVentas() {
        $sql = "SELECT DISTINCT fecha_venta FROM table_es_venta ORDER BY fecha_venta DESC";
        return $this->select_all($sql);
    }

    /**
     * Elimina un cierre y reinicia las ventas asociadas sin usar transacciones.
     */
    public function deleteCierreAndResetVentas(int $idCierre, int $idUsuario, int $idEstacion, string $fechaCierre) {
            // 1. Actualizar tabla de ventas usando JOIN con table_usuarios para el id_estacion
            $sqlUpdateVentas = "UPDATE table_es_venta v JOIN table_usuarios u ON v.id_user = u.usuario_id
                                SET v.id_cierre_diario = 0, v.status_ticket = 1
                                WHERE v.id_cierre_diario = ? AND v.fecha_venta = ? AND u.usuario_estacion_id = ?";
            $arrDataVentas = array($idCierre, $fechaCierre, $idEstacion);
            $updateVentas = $this->update($sqlUpdateVentas, $arrDataVentas);

            if (!$updateVentas) {
                return false;
            }
            // 2. Si la actualización fue exitosa, procedemos a eliminar el registro de cierre.
            $sqlDeleteCierre = "DELETE FROM table_es_cierre WHERE id_cierre = ? AND id_user = ? AND fecha_cierre = ?";
            $arrDataCierre = array($idCierre, $idUsuario, $fechaCierre);
            return $this->delete($sqlDeleteCierre, $arrDataCierre);
    }

    /**
     * Obtiene una lista de ventas con estatus = 1 (abiertas)
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
     */
    public function closeSale(int $idVenta) {
        $sql = "UPDATE table_es_venta SET status_ticket = 0 WHERE id_venta = ?";
        $arrData = array($idVenta);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    /**
     * Verifica de forma rápida si un usuario tiene ventas abiertas en una fecha específica.
     */
    public function hasOpenSales(int $idUser, string $fecha, int $idEstacion): bool {
        $sql = "SELECT 1 FROM table_es_venta v
                INNER JOIN table_usuarios u ON v.id_user = u.usuario_id
                WHERE v.id_user = ? AND v.fecha_venta = ? AND u.usuario_estacion_id = ? AND v.status_ticket = 1 LIMIT 1";
        return !empty($this->select($sql, [$idUser, $fecha, $idEstacion]));
    }

    /**
     * Obtiene datos básicos de un usuario, como su estación.
     */
    public function getUsuario(int $idUser) {
        $sql = "SELECT usuario_id, usuario_estacion_id FROM table_usuarios WHERE usuario_id = ?";
        return $this->select($sql, [$idUser]);
    }

    /**
     * Elimina todas las ventas abiertas (status_ticket = 1) de un usuario en una fecha específica.
     */
    public function deleteAllOpenSales(int $idUser, string $fecha) {
        $sql = "DELETE FROM table_es_venta WHERE id_user = ? AND fecha_venta = ? AND status_ticket = 1";
        return $this->delete($sql, [$idUser, $fecha]);
    }

    /**
     * Elimina un cierre y TODAS las ventas asociadas a él.
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
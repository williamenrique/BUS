<?php
class HomeModel extends Mysql {
	public function __construct(){
		parent::__construct();
	}

	public function getAvailableMonths() {
		$sql = "SELECT DISTINCT DATE_FORMAT(fecha_venta, '%Y-%m') AS mes
				FROM table_es_venta
				ORDER BY mes ASC";
		return $this->select_all($sql);
	}

	public function getMonthlyLiters($startMonth, $endMonth) {
		$startDate = date('Y-m-d', strtotime($startMonth . '-01'));
		$endDate = date('Y-m-t', strtotime($endMonth . '-01'));
		$sql = "SELECT
					DATE_FORMAT(v.fecha_venta, '%Y-%m') AS mes_venta,
					COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS total_litros
				FROM table_es_venta v
				WHERE v.fecha_venta BETWEEN ? AND ?
				GROUP BY
					mes_venta
				ORDER BY
					mes_venta ASC";
		return $this->select_all($sql, [$startDate, $endDate]);
	}

	public function getEstacionDashboardData() {
        $fechaHoy = date('Y-m-d');
        
        $sql_summary = "SELECT 
				COUNT(ve.id_venta) as total_ventas,
				COUNT(DISTINCT se.id) AS user_activo,
				COALESCE(SUM(CAST(ve.litros AS DECIMAL(10,2))), 0) as total_litros,
				COALESCE(SUM(CASE WHEN ve.id_tipo_pago = 1 THEN ve.monto * ve.tasa_dia ELSE ve.monto END), 0) as total_bs
			FROM table_es_venta ve
			LEFT JOIN table_usuario_sessions se ON ve.id_user = se.usuario_id
			WHERE ve.fecha_venta = ?";
        return $this->select($sql_summary, [$fechaHoy]);
    }

    /**
     * =====================================================================
     * DASHBOARD DE ALMACÉN (multi-institución)
     * =====================================================================
     */
	public function getAlmacenDashboard(int $idInstitucion = 1) {
        $mes_actual = date('Y-m');
        $mes_anterior = date('Y-m', strtotime('-1 month'));
        
        $sql_consumibles = "SELECT
                                COALESCE(SUM(CASE WHEN DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ? THEN rd.cant_despacho ELSE 0 END), 0) as mes_actual,
                                COALESCE(SUM(CASE WHEN DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ? THEN rd.cant_despacho ELSE 0 END), 0) as mes_anterior
                            FROM table_alm_relacion_despacho rd
                            JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                            JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                            JOIN table_alm_enlace_producto ep ON p.id_enlace_producto = ep.id_enlace_producto
                            WHERE ep.enlace_producto = 'LUBRICANTES' 
                              AND d.status_despacho = 1
                              AND d.id_institucion = ?";

        $sql_top_product = "SELECT p.producto, SUM(rd.cant_despacho) as total_despachado
                            FROM table_alm_relacion_despacho rd
                            JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                            JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                            WHERE DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ? 
                              AND d.status_despacho = 1
                              AND d.id_institucion = ?
                            GROUP BY p.id_producto, p.producto 
                            ORDER BY total_despachado DESC LIMIT 1";

        $sql_orders_despachadas = "SELECT COUNT(id_despacho) as total_despachadas 
                                   FROM table_alm_despacho 
                                   WHERE estado_orden = 3 
                                     AND status_despacho = 1 
                                     AND DATE_FORMAT(fecha_despacho, '%Y-%m') = ?
                                     AND id_institucion = ?";
        
        $sql_orders_aprobadas = "SELECT COUNT(id_despacho) as total_aprobadas 
                                 FROM table_alm_despacho 
                                 WHERE estado_orden = 2 
                                   AND status_despacho = 1
                                   AND id_institucion = ?";

        $sql_sin_stock = "SELECT COUNT(DISTINCT p.id_producto) as total
                          FROM table_alm_producto p
                          INNER JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                          WHERE p.status_producto = 1
                            AND rp.cant_producto <= 0
                            AND p.id_institucion = ?";

        $sql_stock_bajo = "SELECT COUNT(DISTINCT p.id_producto) as total
                           FROM table_alm_producto p
                           INNER JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                           WHERE p.status_producto = 1
                             AND rp.cant_producto > 0
                             AND rp.cant_producto < 10
                             AND p.id_institucion = ?";

        $sql_total_productos = "SELECT COUNT(DISTINCT p.id_producto) as total
                                FROM table_alm_producto p
                                WHERE p.status_producto = 1
                                  AND p.id_institucion = ?";

        $data = [];
        $data['consumibles'] = $this->select($sql_consumibles, [$mes_actual, $mes_anterior, $idInstitucion]);
        $data['top_product'] = $this->select($sql_top_product, [$mes_actual, $idInstitucion]);
        $data['orders_despachadas'] = $this->select($sql_orders_despachadas, [$mes_actual, $idInstitucion]);
        $data['orders_aprobadas'] = $this->select($sql_orders_aprobadas, [$idInstitucion]);
        $data['productos_sin_stock'] = $this->select($sql_sin_stock, [$idInstitucion])['total'] ?? 0;
        $data['productos_stock_bajo'] = $this->select($sql_stock_bajo, [$idInstitucion])['total'] ?? 0;
        $data['total_productos'] = $this->select($sql_total_productos, [$idInstitucion])['total'] ?? 0;

        return $data;
    }

    /**
     * Top 10 productos más despachados en el mes actual.
     */
    public function getTopProductosDespachados(int $idInstitucion = 1, int $limit = 10): array {
        $mes_actual = date('Y-m');
        $sql = "SELECT 
                    p.id_producto,
                    p.producto,
                    p.present_producto,
                    e.enlace_producto,
                    u.ubicacion,
                    SUM(rd.cant_despacho) as total_despachado
                FROM table_alm_relacion_despacho rd
                INNER JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                INNER JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                WHERE DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ?
                  AND d.status_despacho = 1
                  AND d.id_institucion = ?
                GROUP BY p.id_producto, p.producto, p.present_producto, e.enlace_producto, u.ubicacion
                ORDER BY total_despachado DESC
                LIMIT $limit";
        return $this->select_all($sql, [$mes_actual, $idInstitucion]);
    }

    /**
     * Últimas órdenes registradas.
     */
    public function getUltimasOrdenes(int $idInstitucion = 1, int $limit = 10): array {
        $sql = "SELECT 
                    d.id_despacho,
                    d.numero_orden,
                    d.fecha_despacho,
                    d.operador,
                    d.estado_orden,
                    f.id_unidad,
                    mo.modelo_unidad,
                    (SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE d.status_despacho = 1
                  AND d.id_institucion = ?
                ORDER BY d.id_despacho DESC
                LIMIT $limit";
        return $this->select_all($sql, [$idInstitucion]);
    }

    /**
     * =====================================================================
     * DASHBOARD DE COMPRAS (multi-institución)
     * =====================================================================
     */

    /**
     * Datos generales del dashboard de Compras.
     */
    public function getComprasDashboardData(int $idInstitucion = 1): array {
        $mes_actual = date('Y-m');

        // 1. Requisiciones pendientes (estado 1)
        $sql_requisiciones = "SELECT COUNT(id_despacho) as total 
                              FROM table_alm_despacho 
                              WHERE estado_orden = 1 
                                AND status_despacho = 1
                                AND id_institucion = ?";
        $requisiciones = $this->select($sql_requisiciones, [$idInstitucion])['total'] ?? 0;

        // 2. Órdenes aprobadas pendientes de despacho (estado 2)
        $sql_aprobadas = "SELECT COUNT(id_despacho) as total 
                          FROM table_alm_despacho 
                          WHERE estado_orden = 2 
                            AND status_despacho = 1
                            AND id_institucion = ?";
        $aprobadas = $this->select($sql_aprobadas, [$idInstitucion])['total'] ?? 0;

        // 3. Artículos sin stock
        $sql_sin_stock = "SELECT COUNT(DISTINCT p.id_producto) as total
                          FROM table_alm_producto p
                          INNER JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                          WHERE p.status_producto = 1
                            AND rp.cant_producto <= 0
                            AND p.id_institucion = ?";
        $sin_stock = $this->select($sql_sin_stock, [$idInstitucion])['total'] ?? 0;

        // 4. Artículos con stock bajo (< 10)
        $sql_stock_bajo = "SELECT COUNT(DISTINCT p.id_producto) as total
                           FROM table_alm_producto p
                           INNER JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                           WHERE p.status_producto = 1
                             AND rp.cant_producto > 0
                             AND rp.cant_producto < 10
                             AND p.id_institucion = ?";
        $stock_bajo = $this->select($sql_stock_bajo, [$idInstitucion])['total'] ?? 0;

        // 5. Órdenes despachadas del mes
        $sql_despachadas_mes = "SELECT COUNT(id_despacho) as total 
                                FROM table_alm_despacho 
                                WHERE estado_orden = 3 
                                  AND status_despacho = 1
                                  AND DATE_FORMAT(fecha_despacho, '%Y-%m') = ?
                                  AND id_institucion = ?";
        $despachadas_mes = $this->select($sql_despachadas_mes, [$mes_actual, $idInstitucion])['total'] ?? 0;

        // 6. Órdenes costeadas del mes
        $sql_costeadas_mes = "SELECT COUNT(DISTINCT cp.id_despacho) as total
                              FROM table_compras_costos cc
                              INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                              WHERE DATE_FORMAT(cc.fecha_costeo, '%Y-%m') = ?
                                AND cc.id_institucion = ?";
        $costeadas_mes = $this->select($sql_costeadas_mes, [$mes_actual, $idInstitucion])['total'] ?? 0;

        // 7. Total Divisas del mes
        $sql_total_divisa = "SELECT COALESCE(SUM(cc.monto_divisa), 0) as total
                             FROM table_compras_costos cc
                             WHERE DATE_FORMAT(cc.fecha_costeo, '%Y-%m') = ?
                               AND cc.id_institucion = ?";
        $total_divisa = $this->select($sql_total_divisa, [$mes_actual, $idInstitucion])['total'] ?? 0;

        // 8. Total Bolívares del mes
        $sql_total_bs = "SELECT COALESCE(SUM(cc.monto_bs), 0) as total
                         FROM table_compras_costos cc
                         WHERE DATE_FORMAT(cc.fecha_costeo, '%Y-%m') = ?
                           AND cc.id_institucion = ?";
        $total_bs = $this->select($sql_total_bs, [$mes_actual, $idInstitucion])['total'] ?? 0;

        return [
            'requisiciones_pendientes' => $requisiciones,
            'ordenes_aprobadas' => $aprobadas,
            'articulos_sin_stock' => $sin_stock,
            'articulos_stock_bajo' => $stock_bajo,
            'ordenes_despachadas_mes' => $despachadas_mes,
            'ordenes_costeadas_mes' => $costeadas_mes,
            'total_divisa_mes' => floatval($total_divisa),
            'total_bs_mes' => floatval($total_bs)
        ];
    }

    /**
     * Top 10 productos costeados en el mes.
     */
    public function getTopProductosCosteados(int $idInstitucion = 1, int $limit = 10): array {
        $mes_actual = date('Y-m');
        $sql = "SELECT 
                    p.id_producto,
                    p.producto,
                    p.present_producto,
                    prov.empresa_proveedor AS proveedor,
                    e.enlace_producto,
                    COUNT(cc.id_costo) as veces_costeadas,
                    SUM(cc.monto_divisa) as total_divisa,
                    SUM(cc.monto_bs) as total_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                INNER JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                LEFT JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                LEFT JOIN table_proveedor prov ON rp.id_proveedor = prov.id_proveedor
                WHERE DATE_FORMAT(cc.fecha_costeo, '%Y-%m') = ?
                  AND cc.id_institucion = ?
                GROUP BY p.id_producto, p.producto, p.present_producto, prov.empresa_proveedor, e.enlace_producto
                ORDER BY total_divisa DESC
                LIMIT $limit";
        return $this->select_all($sql, [$mes_actual, $idInstitucion]);
    }

    /**
     * Últimas 10 requisiciones (órdenes en estado 1).
     */
    public function getUltimasRequisiciones(int $idInstitucion = 1, int $limit = 10): array {
        $sql = "SELECT 
                    d.id_despacho,
                    d.numero_orden,
                    d.fecha_despacho,
                    d.operador AS solicitante,
                    d.estado_orden,
                    f.id_unidad,
                    mo.modelo_unidad,
                    (SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE d.status_despacho = 1
                  AND d.id_institucion = ?
                ORDER BY d.id_despacho DESC
                LIMIT $limit";
        return $this->select_all($sql, [$idInstitucion]);
    }

    /**
     * Últimas 10 compras costeadas.
     */
    public function getUltimasComprasCosteadas(int $idInstitucion = 1, int $limit = 10): array {
        $sql = "SELECT 
                    d.id_despacho,
                    d.numero_orden,
                    DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                    f.id_unidad,
                    mo.modelo_unidad,
                    COUNT(cc.id_costo) as articulos_costeados,
                    SUM(cc.monto_divisa) as total_divisa,
                    SUM(cc.monto_bs) as total_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE cc.id_institucion = ?
                GROUP BY d.id_despacho, d.numero_orden, d.fecha_despacho, f.id_unidad, mo.modelo_unidad
                ORDER BY d.id_despacho DESC
                LIMIT $limit";
        return $this->select_all($sql, [$idInstitucion]);
    }

    /**
     * Dashboard de Operaciones (Flota) filtrado por institución.
     */
    public function getOperacionesDashboard(int $idInstitucion = 1) {
        $sql_status = "SELECT 
            SUM(CASE WHEN status_unidad = 1 THEN 1 ELSE 0 END) as operativas,
            SUM(CASE WHEN status_unidad = 2 THEN 1 ELSE 0 END) as inoperativas,
            SUM(CASE WHEN status_unidad = 3 THEN 1 ELSE 0 END) as mantenimiento,
            SUM(CASE WHEN status_unidad = 5 THEN 1 ELSE 0 END) as criticas
            FROM table_flota
            WHERE id_institucion = ?";

        $sql_aceite_status = "SELECT 
            SUM(CASE 
                WHEN f.proximo_cambio_km > 0 AND (f.proximo_cambio_km - f.kilometraje_actual) <= 0 THEN 1 
                ELSE 0 
            END) as requerido,
            SUM(CASE 
                WHEN f.proximo_cambio_km > 0 AND (f.proximo_cambio_km - f.kilometraje_actual) > 0 AND (f.proximo_cambio_km - f.kilometraje_actual) <= 1000 THEN 1 
                ELSE 0 
            END) as proximo,
            SUM(CASE 
                WHEN f.proximo_cambio_km > 0 AND (f.proximo_cambio_km - f.kilometraje_actual) > 1000 THEN 1 
                ELSE 0 
            END) as ok
        FROM (
            SELECT 
                tf.id_flota,
                COALESCE((SELECT km.kilometraje_actual 
                          FROM table_flota_kilometraje km 
                          WHERE km.id_flota = tf.id_flota 
                          ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC 
                          LIMIT 1), 0) as kilometraje_actual,
                COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                          FROM table_flota_aceite_historial ah 
                          WHERE ah.id_flota = tf.id_flota 
                          ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC 
                          LIMIT 1), 0) as proximo_cambio_km
            FROM table_flota tf
            WHERE tf.status_unidad = 1
              AND tf.id_institucion = ?
        ) as f";

        $sql_grouped = "SELECT m.marca_unidad, mo.modelo_unidad, f.transmision, f.tipo_combustible, 
            COUNT(f.id_flota) as total, 
            SUM(CASE WHEN f.status_unidad = 1 THEN 1 ELSE 0 END) as operativas,
            SUM(CASE WHEN f.status_unidad != 1 THEN 1 ELSE 0 END) as inoperativas
            FROM table_flota f 
            JOIN table_flota_marca m ON f.id_marca = m.id_marca 
            JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo 
            WHERE f.id_institucion = ?
            GROUP BY m.marca_unidad, mo.modelo_unidad, f.transmision, f.tipo_combustible 
            ORDER BY total DESC";

        return [
            'status' => $this->select($sql_status, [$idInstitucion]), 
            'grouped' => $this->select_all($sql_grouped, [$idInstitucion]),
            'aceite_status' => $this->select($sql_aceite_status, [$idInstitucion])
        ];
    }

    /**
     * Dashboard de Compras filtrado por institución (compatibilidad).
     */
    public function getComprasDashboard(int $idInstitucion = 1): array {
        $data = $this->getComprasDashboardData($idInstitucion);
        // Retornar en el formato anterior para compatibilidad
        return [
            'requisiciones_pendientes' => $data['requisiciones_pendientes'],
            'articulos_sin_stock' => $data['articulos_sin_stock']
        ];
    }

	public function getBienesDashboardData() {
        $sql_summary = "SELECT
                            COUNT(id_bien) as total_bienes,
                            SUM(CASE WHEN status_bien IN ('EN USO', 'GUARDADO') THEN 1 ELSE 0 END) as total_activos,
                            SUM(CASE WHEN status_bien = 'DAÑADO' THEN 1 ELSE 0 END) as total_reparacion,
                            SUM(CASE WHEN status_bien = 'FALTANTE POR UBICAR' THEN 1 ELSE 0 END) as total_baja
                        FROM table_bienes_inventario
                        WHERE status = 1";

        $sql_recent = "SELECT b.descripcion_bien, b.fecha_adquisicion, d.departamento_bien
                       FROM table_bienes_inventario b
                       JOIN table_bienes_departamentos d ON b.bien_depatamento_id = d.depatamento_bien_id
                       WHERE b.status = 1 
                       ORDER BY STR_TO_DATE(b.fecha_adquisicion, '%Y-%m-%d') DESC 
                       LIMIT 5";

        return ['summary' => $this->select($sql_summary), 'recent' => $this->select_all($sql_recent)];
    }

	public function getDailySalesByUser($fecha = null, $userRol = '', $userEstacionId = 0) {
		if ($fecha === null) {
			$fecha = date('d-m-y');
		}

		$whereEstacion = "";
		$params = [$fecha];

		if (strtoupper($userRol) === 'ADMINISTRADOR' || strtoupper($userRol) === 'SISTEMA') {
			$whereEstacion = "AND u.usuario_estacion_id != 0";
		} 
		elseif ($userEstacionId != 0) {
			$whereEstacion = "AND u.usuario_estacion_id = ?";
			$params[] = $userEstacionId;
		}

		$sql = "SELECT 
					u.usuario_nick as usuario,
					COUNT(v.id_venta) as total_ventas,
					COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) as total_litros,
					COALESCE(SUM(CAST(v.monto AS DECIMAL(10,2))), 0) as total_monto
				FROM table_usuarios u
				LEFT JOIN table_es_venta v ON u.usuario_id = v.id_user AND v.fecha_venta = ?
				WHERE u.usuario_status = 1 {$whereEstacion}
				GROUP BY u.usuario_id, u.usuario_nick
				ORDER BY total_litros DESC";
		
		return $this->select_all($sql, $params);
	}

	public function getDailySalesSummary($fecha = null) {
		if ($fecha === null) {
			$fecha = date('Y-m-d');
		}
		$sql = "SELECT 
					COUNT(v.id_venta) as total_ventas,
					COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) as total_litros,
					COALESCE(SUM(CAST(v.monto AS DECIMAL(10,2))), 0) as total_monto,
					COUNT(DISTINCT v.id_user) as total_usuarios
				FROM table_es_venta v
				WHERE v.fecha_venta = ?";
		
		return $this->select($sql, [$fecha]);
	}

	public function selectActiveUsers()
	{
		$sql = "SELECT
					s.session_id,
					s.usuario_id,
					s.ip_address,
					s.created_at,
					u.usuario_nick,
					u.usuario_imagen, 
					p.personal_nombre AS usuario_nombres,
					p.personal_apellido AS usuario_apellidos,
					r.rol_nombre, 
					d.departamento_nombre as departamento
				FROM table_usuario_sessions s
				INNER JOIN table_usuarios u ON s.usuario_id = u.usuario_id
				INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
				LEFT JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id
				LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
				WHERE u.usuario_status = 1
				ORDER BY s.created_at DESC";
		
		$request = $this->select_all($sql);
		return $request;
	}

	public function selectAllUsersForAdmin()
	{
		$sql = "SELECT 
					u.usuario_id,
					u.usuario_nick,
					p.personal_nombre AS usuario_nombres,
					p.personal_apellido AS usuario_apellidos,
					r.rol_nombre,
					d.departamento_nombre,
					u.usuario_status
				FROM table_usuarios u
				INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                LEFT JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id
                LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
				ORDER BY u.usuario_id DESC";
		return $this->select_all($sql);
	}

	public function getPendingNotifications(int $userId, string $userRole, string $userDepartmentName) {
		$notifications = [];
		$totalCount = 0;
		
		$isSistemasAdmin = (strtoupper($userDepartmentName) === 'SISTEMAS' || strtoupper($userDepartmentName) === 'SISTEMA');
		$isCompras = false;
		$isAlmacen = false;

		if ($isSistemasAdmin || $isCompras) {
			$sql_requisiciones = "SELECT
									id_notificacion,
									'nueva_requisicion' as tipo_notificacion,
									id_referencia,
									mensaje,
									DATE_FORMAT(fecha_creacion, '%d/%m %h:%i %p') as fecha_creacion
								FROM table_notificaciones
								WHERE tipo_notificacion = 'nueva_requisicion' AND leido = 0";
			$requisition_notifications = $this->select_all($sql_requisiciones);
			if ($requisition_notifications) {
				$notifications = array_merge($notifications, $requisition_notifications);
			}
		}

		if ($isSistemasAdmin) {
			$sql_recovery = "SELECT
								r.id as id_referencia,
								'recuperacion_usuario' as tipo_notificacion,
								CONCAT('Solicitud de ', p.personal_nombre) as mensaje,
								DATE_FORMAT(r.request_date, '%d/%m %h:%i %p') as fecha_creacion
							FROM table_recovery_requests r
							JOIN table_usuarios u ON r.user_id = u.usuario_id
							JOIN table_personal p ON u.usuario_id_personal = p.id_personal
							WHERE r.status = 0";
			$recovery_notifications = $this->select_all($sql_recovery);
			if ($recovery_notifications) {
				$notifications = array_merge($notifications, $recovery_notifications);
			}
		}

		if ($isSistemasAdmin || $isAlmacen) {
			$sql_despachos = "SELECT
									id_notificacion,
									'despacho_pendiente' as tipo_notificacion,
									id_referencia,
									mensaje,
									DATE_FORMAT(fecha_creacion, '%d/%m %h:%i %p') as fecha_creacion
								FROM table_notificaciones
								WHERE tipo_notificacion = 'despacho_pendiente' AND leido = 0";
			$despacho_notifications = $this->select_all($sql_despachos);
			if ($despacho_notifications) {
				$notifications = array_merge($notifications, $despacho_notifications);
			}
		}

		if (!empty($notifications)) {
			usort($notifications, function ($a, $b) {
				return strtotime(str_replace('/', '-', $b['fecha_creacion'])) - strtotime(str_replace('/', '-', $a['fecha_creacion']));
			});
		}

		$totalCount = count($notifications);
		$limited_notifications = array_slice($notifications, 0, 10);

		return [
			'count' => $totalCount,
			'notifications' => $limited_notifications
		];
	}

	public function updateUserStation(int $userId, int $stationId): bool
	{
		$sql = "UPDATE table_usuarios SET usuario_estacion_id = ? WHERE usuario_id = ?";
		return $this->update($sql, [$stationId, $userId]);
	}

	public function refreshSession(int $userId)
	{
		$sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_rol_id, r.rol_nombre, u.usuario_departamento_id, u.usuario_estacion_id, u.usuario_imagen, u.usuario_status,
					   p.personal_nombre, p.personal_apellido, p.personal_cedula, p.personal_email, p.personal_tlf,
					   d.departamento_nombre,
					   e.estacion
				FROM table_usuarios u
				INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
				INNER JOIN table_per_roles r ON u.usuario_rol_id = r.rol_id 
				INNER JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id 
				LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
				WHERE u.usuario_id = ?";
		$request = $this->select($sql, [$userId]);
		if ($request) {
			$departamentoId = intval($request['usuario_departamento_id'] ?? 0);
			$departamentoNombre = strtoupper($request['departamento_nombre'] ?? '');
			
			if ($departamentoId === 1 || $departamentoNombre === 'SISTEMA' || $departamentoNombre === 'SISTEMAS') {
				$request['id_institucion'] = 0;
			} elseif ($departamentoId === 8 || $departamentoNombre === 'TALLER') {
				$request['id_institucion'] = 2;
			} else {
				$request['id_institucion'] = 1;
			}
			$request['es_admin'] = ($request['id_institucion'] === 0);
			
			$_SESSION['userData'] = $request;
		}
		return $request;
	}
}
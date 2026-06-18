<?php
class HomeModel extends Mysql {
	public function __construct(){
		parent::__construct();
	}
	public function getAvailableMonths() {
		$sql = "SELECT DISTINCT DATE_FORMAT(fecha_venta, '%Y-%m') AS mes
				FROM table_es_venta
				ORDER BY mes ASC";
		// Solo retorna los datos, no imprime nada
		return $this->select_all($sql);
	}
	public function getMonthlyLiters($startMonth, $endMonth) {
		// Convertir los meses YYYY-MM a fechas válidas para la comparación
		$startDate = date('Y-m-d', strtotime($startMonth . '-01'));
		$endDate = date('Y-m-t', strtotime($endMonth . '-01')); // 't' da el último día del mes
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
        
        // Obtener resumen de ventas de hoy
        $sql_summary = "SELECT 
				COUNT(ve.id_venta) as total_ventas,
				COUNT(DISTINCT se.id) AS user_activo,
				COALESCE(SUM(CAST(ve.litros AS DECIMAL(10,2))), 0) as total_litros,
				COALESCE(SUM(CASE WHEN ve.id_tipo_pago = 1 THEN ve.monto * ve.tasa_dia ELSE ve.monto END), 0) as total_bs
			FROM table_es_venta ve
			LEFT JOIN table_usuario_sessions se ON ve.id_user = se.usuario_id -- Ajusta la condición de JOIN según tu esquema
			WHERE ve.fecha_venta = ?";
        return $this->select($sql_summary, [$fechaHoy]);
    }
	public function getAlmacenDashboard() {
        $mes_actual = date('Y-m');
        $mes_anterior = date('Y-m', strtotime('-1 month'));
        
        // CORRECCIÓN: La consulta ahora usa dos placeholders que coinciden con los dos parámetros que se le pasan.
        $sql_consumibles = "SELECT
                                COALESCE(SUM(CASE WHEN DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ? THEN rd.cant_despacho ELSE 0 END), 0) as mes_actual,
                                COALESCE(SUM(CASE WHEN DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ? THEN rd.cant_despacho ELSE 0 END), 0) as mes_anterior
                            FROM table_alm_relacion_despacho rd
                            JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                            JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                            JOIN table_alm_enlace_producto ep ON p.id_enlace_producto = ep.id_enlace_producto
                            WHERE ep.enlace_producto = 'LUBRICANTES' AND d.status_despacho = 1";

        $sql_top_product = "SELECT p.producto, SUM(rd.cant_despacho) as total_despachado
                            FROM table_alm_relacion_despacho rd
                            JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                            JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                            WHERE DATE_FORMAT(d.fecha_despacho, '%Y-%m') = ? AND d.status_despacho = 1
                            GROUP BY p.id_producto, p.producto ORDER BY total_despachado DESC LIMIT 1";

        // CORRECCIÓN: Contar órdenes despachadas (estado 3) en el mes actual
        $sql_orders_despachadas = "SELECT COUNT(id_despacho) as total_despachadas FROM table_alm_despacho WHERE estado_orden = 3 AND status_despacho = 1 AND DATE_FORMAT(fecha_despacho, '%Y-%m') = ?";
        // NUEVO: Contar órdenes aprobadas pendientes de despacho (estado 2)
        $sql_orders_aprobadas = "SELECT COUNT(id_despacho) as total_aprobadas FROM table_alm_despacho WHERE estado_orden = 2 AND status_despacho = 1";
        
        $data['consumibles'] = $this->select($sql_consumibles, [$mes_actual, $mes_anterior]);
        $data['top_product'] = $this->select($sql_top_product, [$mes_actual]);
        $data['orders_despachadas'] = $this->select($sql_orders_despachadas, [$mes_actual]);
        $data['orders_aprobadas'] = $this->select($sql_orders_aprobadas);
        return $data;
    }
    public function getOperacionesDashboard() {
        $sql_status = "SELECT 
            SUM(CASE WHEN status_unidad = 1 THEN 1 ELSE 0 END) as operativas,
            SUM(CASE WHEN status_unidad = 2 THEN 1 ELSE 0 END) as inoperativas,
            SUM(CASE WHEN status_unidad = 3 THEN 1 ELSE 0 END) as mantenimiento,
            SUM(CASE WHEN status_unidad = 5 THEN 1 ELSE 0 END) as criticas
            FROM table_flota";

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
                -- Se calcula el próximo cambio sumando 5000 al último cambio registrado
                COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                          FROM table_flota_aceite_historial ah 
                          WHERE ah.id_flota = tf.id_flota 
                          ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC 
                          LIMIT 1), 0) as proximo_cambio_km
            FROM table_flota tf
            WHERE tf.status_unidad = 1 -- Solo unidades activas
        ) as f";

        $sql_grouped = "SELECT m.marca_unidad, mo.modelo_unidad, f.transmision, f.tipo_combustible, COUNT(f.id_flota) as total, 
            SUM(CASE WHEN f.status_unidad = 1 THEN 1 ELSE 0 END) as operativas,
            SUM(CASE WHEN f.status_unidad != 1 THEN 1 ELSE 0 END) as inoperativas
            FROM table_flota f JOIN table_flota_marca m ON f.id_marca = m.id_marca JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo 
            GROUP BY m.marca_unidad, mo.modelo_unidad, f.transmision, f.tipo_combustible ORDER BY total DESC";

        return [
            'status' => $this->select($sql_status), 
            'grouped' => $this->select_all($sql_grouped),
            'aceite_status' => $this->select($sql_aceite_status)
        ];
    }
	// funciones para mostrar resumen por usuario en grafica de barras
    /**
     * Obtiene los datos para el dashboard del departamento de Compras.
     * @return array
     */
    public function getComprasDashboard(): array {
        // Contar requisiciones pendientes (estado_orden = 1 en table_alm_despacho)
        $sql_requisiciones = "SELECT COUNT(id_despacho) as total 
                              FROM table_alm_despacho 
                              WHERE estado_orden = 1 AND status_despacho = 1";
        $data['requisiciones_pendientes'] = $this->select($sql_requisiciones)['total'] ?? 0;

        // Contar artículos con stock <= 0
        $sql_stock_cero = "SELECT COUNT(p.id_producto) as total
                           FROM table_alm_producto p
                           JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                           WHERE p.status_producto = 1 AND rp.cant_producto <= 0";
        $data['articulos_sin_stock'] = $this->select($sql_stock_cero)['total'] ?? 0;

        return $data;
    }
	public function getBienesDashboardData() {
        // Resumen de bienes por estado
        $sql_summary = "SELECT
                            COUNT(id_bien) as total_bienes,
                            SUM(CASE WHEN status_bien IN ('EN USO', 'GUARDADO') THEN 1 ELSE 0 END) as total_activos,
                            SUM(CASE WHEN status_bien = 'DAÑADO' THEN 1 ELSE 0 END) as total_reparacion,
                            SUM(CASE WHEN status_bien = 'FALTANTE POR UBICAR' THEN 1 ELSE 0 END) as total_baja
                        FROM table_bienes_inventario
                        WHERE status = 1"; // Solo bienes no eliminados lógicamente

        // Últimos 5 bienes agregados
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
			$fecha = date('d-m-y'); // Fecha actual en formato dd-mm-yy
		}

		$whereEstacion = "";
		$params = [$fecha];

		// Si el usuario es Administrador o de Sistema, muestra todos los de las estaciones.
		if (strtoupper($userRol) === 'ADMINISTRADOR' || strtoupper($userRol) === 'SISTEMA') {
			$whereEstacion = "AND u.usuario_estacion_id != 0";
		} 
		// Si el usuario pertenece a una estación, muestra solo los de su estación.
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
			$fecha = date('Y-m-d'); // Fecha actual en formato Y-m-d
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

	/**
	 * Selecciona los usuarios que tienen una sesión activa registrada.
	 * @return array Lista de usuarios activos con sus detalles.
	 */
	public function selectActiveUsers()
	{
		// CORRECCIÓN: Se añade el JOIN a table_personal para obtener los datos del usuario.
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
				WHERE u.usuario_status = 1 -- Opcional: Muestra solo usuarios con status activo
				ORDER BY s.created_at DESC";
		
		$request = $this->select_all($sql);
		return $request;
	}

	/**
	 * Selecciona todos los usuarios para la gestión del administrador.
	 * @return array Lista de todos los usuarios con su estado.
	 */
	public function selectAllUsersForAdmin()
	{
		// CORRECCIÓN: Se añade el JOIN a table_personal para obtener los datos del usuario.
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

	/**
	 * Obtiene un resumen de las notificaciones pendientes.
	 * @return array Conteo total y las 5 solicitudes más recientes.
	 */
	public function getPendingNotifications(int $userId, string $userRole, string $userDepartmentName) {
		// =================================================================
		// INICIO DE LA LÓGICA CENTRALIZADA DE NOTIFICACIONES
		// =================================================================
		$notifications = [];
		$totalCount = 0;
		
		// Determinar los permisos basados en el rol y departamento
		$isSistemasAdmin = (strtoupper(trim($userDepartmentName)) === 'SISTEMAS' || strtoupper(trim($userDepartmentName)) === 'SISTEMA' || strtoupper(trim($userRole)) === 'ADMINISTRADOR'); // Admin/Sistema ve todo
		
		// Encargados de cada departamento ven sus notificaciones específicas
		$isEncargadoCompras = (strtoupper(trim($userDepartmentName)) === 'COMPRAS' && strtoupper(trim($userRole)) === 'ENCARGADO');
		$isEncargadoAlmacen = (strtoupper(trim($userDepartmentName)) === 'ALMACEN' && strtoupper(trim($userRole)) === 'ENCARGADO');
		$isEncargadoOperaciones = (strtoupper(trim($userDepartmentName)) === 'OPERACIONES' && strtoupper(trim($userRole)) === 'ENCARGADO');

		// 1. Notificaciones de nuevas requisiciones (visibles para Compras y Sistemas/Admin)
		if ($isSistemasAdmin || $isEncargadoCompras) {
			$sql_requisiciones = "SELECT
									id_notificacion,
									'nueva_requisicion' as tipo_notificacion,
									id_referencia,
									mensaje,
									DATE_FORMAT(fecha_creacion, '%d/%m %h:%i %p') as fecha_creacion,
									fecha_creacion as fecha_raw
								FROM table_notificaciones
								WHERE tipo_notificacion = 'nueva_requisicion' AND leido = 0";
			$requisition_notifications = $this->select_all($sql_requisiciones);
			if ($requisition_notifications) {
				$notifications = array_merge($notifications, $requisition_notifications);
			}
		}

		// 2. Notificaciones de recuperación de cuenta de usuario (visibles solo para Sistemas/Admin)
		if ($isSistemasAdmin) {
			$sql_recovery = "SELECT
								r.id as id_referencia,
								'recuperacion_usuario' as tipo_notificacion,
								CONCAT('Solicitud de ', p.personal_nombre) as mensaje,
								DATE_FORMAT(r.request_date, '%d/%m %h:%i %p') as fecha_creacion,
								r.request_date as fecha_raw
							FROM table_recovery_requests r
							JOIN table_usuarios u ON r.user_id = u.usuario_id
							JOIN table_personal p ON u.usuario_id_personal = p.id_personal
							WHERE r.status = 0";
			$recovery_notifications = $this->select_all($sql_recovery);
			if ($recovery_notifications) {
				$notifications = array_merge($notifications, $recovery_notifications);
			}
		}

		// 3. Notificaciones de despachos pendientes (visibles para Almacén y Sistemas/Admin)
		if ($isSistemasAdmin || $isEncargadoAlmacen) {
			$sql_despachos = "SELECT
									id_notificacion,
									'despacho_pendiente' as tipo_notificacion,
									id_referencia,
									mensaje,
									DATE_FORMAT(fecha_creacion, '%d/%m %h:%i %p') as fecha_creacion,
									fecha_creacion as fecha_raw
								FROM table_notificaciones
								WHERE tipo_notificacion = 'despacho_pendiente' AND leido = 0";
			$despacho_notifications = $this->select_all($sql_despachos);
			if ($despacho_notifications) {
				$notifications = array_merge($notifications, $despacho_notifications);
			}
		}

		// 4. Notificaciones de requisiciones en proceso (visibles para Operaciones y Sistemas/Admin)
		if ($isSistemasAdmin || $isEncargadoOperaciones) {
			$sql_proceso = "SELECT
									id_notificacion,
									tipo_notificacion,
									id_referencia,
									mensaje,
									DATE_FORMAT(fecha_creacion, '%d/%m %h:%i %p') as fecha_creacion,
									fecha_creacion as fecha_raw
								FROM table_notificaciones
								WHERE tipo_notificacion IN ('requisicion_proceso', 'orden_en_proceso', 'orden_aprobada_ops', 'orden_despachada_ops') 
								AND leido = 0";
			$proceso_notifications = $this->select_all($sql_proceso);
			if ($proceso_notifications) {
				$notifications = array_merge($notifications, $proceso_notifications);
			}
		}

		// 5. Ordenar todas las notificaciones por fecha de creación descendente
		if (!empty($notifications)) {
			usort($notifications, function ($a, $b) {
				return strtotime($b['fecha_raw']) - strtotime($a['fecha_raw']);
			});
		}

		// 4. Contar y limitar el número de notificaciones a mostrar
		$totalCount = count($notifications);
		$limited_notifications = array_slice($notifications, 0, 10);

		return [
			'count' => $totalCount,
			'notifications' => $limited_notifications
		];
		// =================================================================
		// FIN DE LA LÓGICA CENTRALIZADA DE NOTIFICACIONES
		// =================================================================
	}

	/**
	 * Actualiza la estación de un usuario en la base de datos.
	 * @param int $userId El ID del usuario a actualizar.
	 * @param int $stationId El ID de la nueva estación.
	 * @return bool True si la actualización fue exitosa, false en caso contrario.
	 */
	public function updateUserStation(int $userId, int $stationId): bool
	{
		$sql = "UPDATE table_usuarios SET usuario_estacion_id = ? WHERE usuario_id = ?";
		return $this->update($sql, [$stationId, $userId]);
	}

	/**
	 * Refresca los datos de la sesión del usuario y los actualiza en $_SESSION.
	 * @param int $userId El ID del usuario.
	 * @return array|false Los nuevos datos del usuario o false si no se encuentra.
	 */
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
			$_SESSION['userData'] = $request;
		}
		return $request;
	}
}
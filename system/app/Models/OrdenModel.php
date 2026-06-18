<?php
class OrdenModel extends Mysql {

	public function __construct(){
		parent::__construct();
	}

	/**************************************************/
	/********* TODO: SELECTS PARA FORMULARIOS *********/
	/**************************************************/
	/****funcion para traer todas las unidades para el select ****/
	public function selectListFlota(){
		$sql = "SELECT flota.*, modelo.modelo_unidad FROM table_flota flota
					INNER JOIN table_flota_modelo modelo ON flota.id_modelo = modelo.id_modelo";
		$request = $this->select_all($sql);
		return $request;
	}
	/****funcion para traer una unidad ****/
	public function selectUnidad(int $intIdUnidad){
        $this->intIdUnidad = $intIdUnidad;
		$sql = "SELECT f.*, mo.*, ma.* FROM table_flota f
						INNER JOIN table_flota_marca ma ON f.id_marca = ma.id_marca
						INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
						WHERE f.id_flota = ?";
		$request = $this->select($sql, [$intIdUnidad]);
		return $request;
	}
	/****funcion para traer un personal especifico ****/
	public function selectPersonal(int $intDesp){
        $this->intDesp = $intDesp;
		$sql = "SELECT * FROM table_personal  WHERE id_personal = ?";
		$request = $this->select($sql, [$this->intDesp]);
		return $request;
	}
	/****funcion para traer operadores para el select ****/
	public function selectListOper(){
		$sql = "SELECT p.*, c.* FROM table_personal p
						INNER JOIN  table_per_cargo c  ON p.personal_cargo = c.id_cargo 
                        AND p.personal_status  <> 0 WHERE p.personal_cargo = 23 ORDER BY p.personal_cedula DESC  ";
		$request = $this->select_all($sql);
		return $request;
	}
	/****funcion para traer mecanicos para el select ****/
	public function selectListMec(){
		$sql = "SELECT p.*, c.* FROM table_personal p
                        INNER JOIN  table_per_cargo c  ON p.personal_cargo = c.id_cargo 
                        AND p.personal_status  <> 0 WHERE (p.personal_cargo  
		BETWEEN 26 AND 29) OR p.personal_cargo = 32 OR p.personal_cargo = 43  ORDER BY p.personal_cedula DESC";
		$request = $this->select_all($sql);
		return $request;
	}
	/****funcion para traer despachadores de almacen ****/
	public function selectListDesp(){
		$sql = "SELECT p.*, c.* FROM table_personal p
						INNER JOIN  table_per_cargo c  ON p.personal_cargo = c.id_cargo 
                        AND p.personal_status  <> 0 WHERE p.personal_tag = 2 ORDER BY p.personal_cedula DESC ";
		$request = $this->select_all($sql);
		return $request;
	}
	/****funcion para traer un articulo  de almacen para el select ****/
	public function selectArt(int $intIdArt){
		$this->intIdArt = $intIdArt;
		$sql = "SELECT cant_producto FROM table_alm_relacion_producto WHERE id_producto = $this->intIdArt";
		$request = $this->select($sql);
		return $request;
	}
	/****funcion para traer los articulos  de almacen para el select ****/
	public function selectListArt(){
		$sql = "SELECT producto.*, relacionP.*, enlace.enlace_producto FROM table_alm_producto producto
					INNER JOIN table_alm_enlace_producto enlace ON enlace.id_enlace_producto = producto.id_enlace_producto
					INNER JOIN table_alm_relacion_producto relacionP ON producto.id_producto = relacionP.id_producto
					WHERE producto.status_producto > 0";
		$request = $this->select_all($sql);
		return $request;
	}

	/**************************************************/
	/*********** TODO: CREACIÓN DE ÓRDENES ************/
	/**************************************************/

	/**** insertar despacho ****/
	public function insertDespacho(int $intUnidad, string $srtOper, string $srtMec, string $srtDesp, int $intIdUser, string $srtObs, string $strDate){
		$queryInsert = "INSERT INTO table_alm_despacho(id_flota, operador, mecanico, despachador, fecha_despacho, user_id, observacion, status_despacho, estado_orden, origen_despacho) VALUES(?,?,?,?,?,?,?,?,?, 'Requisicion')";
		$idDespacho = $this->insert($queryInsert,[$intUnidad, $srtOper, $srtMec, $srtDesp, $strDate, $intIdUser, $srtObs, 1, 1]);

		if ($idDespacho > 0) {
			$sql_user = "SELECT p.personal_nombre, p.personal_apellido 
                         FROM table_usuarios u 
                         JOIN table_personal p ON u.usuario_id_personal = p.id_personal 
                         WHERE u.usuario_id = ?";
			$user_info = $this->select($sql_user, [$intIdUser]);
			$creator_name = $user_info ? trim($user_info['personal_nombre'] . ' ' . $user_info['personal_apellido']) : 'Usuario del sistema';

			$sql_notificacion = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje) VALUES (?, ?, ?)";
			$mensaje = "Despacho Directo #{$idDespacho} creado por {$creator_name}.";
			$this->insert($sql_notificacion, ['nueva_requisicion', $idDespacho, $mensaje]);
		}
		return $idDespacho;
	}
	/**** insertar relacion despacho ****/
	public function insertRDespacho(int $idDespacho, int $idArticulo, float $cantidad, int $idFlota, string $fechaDespacho){
		$sqlRelacion = "INSERT INTO table_alm_relacion_despacho(id_despacho, id_producto, cant_despacho) VALUES(?,?,?)";
		$requestRelacion = $this->insert($sqlRelacion, [$idDespacho, $idArticulo, $cantidad]);
		return $requestRelacion;
	}

	/**
	 * Procesa el despacho final de una orden.
	 * 1. Actualiza el registro de despacho con el operador y despachador.
	 * 2. Cambia el estado de la orden a 'Despachada' (3).
	 * 3. Descuenta los artículos del inventario.
	 * Todo se ejecuta en una transacción.
	 */
	public function procesarDespachoFinal(int $idDespacho, string $nombreOperador, string $nombreDespachador) {
		$this->beginTransaction();
		try {
			// 1. Verificar que la orden exista y esté aprobada (estado 2)
			$orden = $this->select("SELECT estado_orden FROM table_alm_despacho WHERE id_despacho = ?", [$idDespacho]);
			if (empty($orden) || $orden['estado_orden'] != 2) {
				throw new Exception("La orden no existe o no está aprobada para despacho.");
			}

			// 2. Obtener los artículos de la requisición original
			$sql_articulos = "SELECT rd.cantidad_solicitada, rd.id_producto, p.producto
							  FROM table_alm_requisicion r
							  JOIN table_alm_requisicion_detalle rd ON r.id_requisicion = rd.id_requisicion_fk
							  JOIN table_alm_producto p ON rd.id_producto = p.id_producto
							  WHERE r.id_despacho_fk = ?";
			$articulos = $this->select_all($sql_articulos, [$idDespacho]);

			// 3. Insertar los artículos en la tabla de relación de despacho
			foreach ($articulos as $item) {
				$sql_relacion = "INSERT INTO table_alm_relacion_despacho (id_despacho, id_producto, cant_despacho) VALUES (?, ?, ?)";
				$this->insert($sql_relacion, [$idDespacho, $item['id_producto'], $item['cantidad_solicitada']]);
			}

			// 4. Actualizar la orden de despacho a 'Despachada' (estado 3) y completar los datos
			$sql_update_despacho = "UPDATE table_alm_despacho SET operador = ?, despachador = ?, estado_orden = 3 WHERE id_despacho = ?";
			$this->update($sql_update_despacho, [$nombreOperador, $nombreDespachador, $idDespacho]);

			// 4.5. Actualizar el estado en la tabla de requisición específica a 'Despachada' (3)
			$sql_update_req = "UPDATE table_alm_requisicion SET status_requisicion = 3 WHERE id_despacho_fk = ?";
			$this->update($sql_update_req, [$idDespacho]);

			// 5. Marcar la notificación de 'despacho_pendiente' como leída
			$sql_update_notif = "UPDATE table_notificaciones SET leido = 1 WHERE tipo_notificacion = 'despacho_pendiente' AND id_referencia = ?";
			$this->update($sql_update_notif, [$idDespacho]);

			// Limpiar notificaciones previas para Operaciones (En proceso o Aprobada)
			$sql_clear_ops = "UPDATE table_notificaciones SET leido = 1 WHERE tipo_notificacion IN ('orden_en_proceso', 'orden_aprobada_ops') AND id_referencia = ? AND leido = 0";
			$this->update($sql_clear_ops, [$idDespacho]);

			// 6. Crear notificación para Compras: orden despachada
			$despachoData = $this->select("SELECT d.id_despacho, f.id_unidad FROM table_alm_despacho d JOIN table_flota f ON d.id_flota = f.id_flota WHERE d.id_despacho = ?", [$idDespacho]);
			$idUnidad = $despachoData['id_unidad'] ?? $idDespacho;
			$sql_notif_compras = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje) VALUES ('orden_despachada', ?, ?)";
			$this->insert($sql_notif_compras, [$idDespacho, "Orden #{$idDespacho} despachada para unidad {$idUnidad}. Stock descontado."]);

			// 7. Crear notificación para Operaciones: orden despachada
			$sql_notif_ops = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje) VALUES ('orden_despachada_ops', ?, ?)";
			$this->insert($sql_notif_ops, [$idDespacho, "Orden #{$idDespacho} (Unidad {$idUnidad}) ha sido DESPACHADA. Puede retirar los artículos en Almacén."]);

			$this->commit();
			return true;

		} catch (Exception $e) {
			$this->rollBack();
			error_log("Error en procesarDespachoFinal: " . $e->getMessage());
			throw $e;
		}
	}

	/**************************************************/
	/**** TODO: CONSULTA Y BÚSQUEDA DE ÓRDENES ********/
	/**************************************************/

	/**
	 * Obtiene las órdenes para DataTables con procesamiento del lado del servidor.
	 * @param int $start Inicio del límite de registros.
	 * @param int $length Número de registros a obtener.
	 * @param string $searchValue Valor de búsqueda.
	 * @param string $orderColumn Columna por la cual ordenar.
	 * @param string $orderDir Dirección del ordenamiento (asc/desc).
	 * @return array Datos para DataTables.
	 */
	public function getOrdenesServerSide(int $start, int $length, string $searchValue, string $orderColumn, string $orderDir): array
	{
		$sql = "SELECT 
					d.id_despacho,
					d.fecha_despacho,
					f.id_unidad, 
					m.marca_unidad, 
					mo.modelo_unidad,
					d.operador AS operador_nombre,
					d.mecanico AS mecanico_nombre,
					d.despachador AS despachador_nombre,
					(SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos,
					u.usuario_nick as usuario_registro
				FROM table_alm_despacho d
				INNER JOIN table_flota f ON d.id_flota = f.id_flota
				INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
				INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
				INNER JOIN table_usuarios u ON d.user_id = u.usuario_id
				WHERE d.status_despacho = 1";

		// Búsqueda
		$sqlSearch = "";
		if (!empty($searchValue)) {
			$sqlSearch = " AND (d.id_despacho LIKE '%$searchValue%' OR 
							   d.fecha_despacho LIKE '%$searchValue%' OR 
							   f.id_unidad LIKE '%$searchValue%' OR 
							   d.operador LIKE '%$searchValue%')";
		}

		// Conteo total de registros filtrados
		$sqlFiltered = "SELECT COUNT(d.id_despacho) as total FROM table_alm_despacho d 
						INNER JOIN table_flota f ON d.id_flota = f.id_flota
						WHERE d.status_despacho = 1 " . $sqlSearch;
		$totalFiltered = $this->select($sqlFiltered)['total'];

		// Conteo total de registros sin filtrar
		$sqlTotal = "SELECT COUNT(id_despacho) as total FROM table_alm_despacho WHERE status_despacho = 1";
		$totalRecords = $this->select($sqlTotal)['total'];

		// Consulta principal con ordenamiento y paginación
		$sqlData = $sql . $sqlSearch . "
				   ORDER BY $orderColumn $orderDir 
				   LIMIT $start, $length";
		$data = $this->select_all($sqlData);

		return [
			"data" => $data,
			"total" => $totalRecords,
			"total_filtered" => $totalFiltered
		];
	}

	/**
	 * Obtiene todas las órdenes para DataTables con procesamiento del lado del cliente.
	 * @return array Datos para DataTables.
	 */
	public function selectOrdenes(): array
	{
		$sql = "SELECT 
					d.id_despacho,
					COALESCE(d.fecha_aprobacion, d.fecha_despacho) as fecha_aprobacion,
					f.id_unidad, 
					m.marca_unidad, 
					mo.modelo_unidad,
					CONCAT(p.personal_nombre, ' ', p.personal_apellido) as creador_nombre,
					(SELECT COUNT(*) FROM table_alm_requisicion_detalle rd JOIN table_alm_requisicion r ON rd.id_requisicion_fk = r.id_requisicion WHERE r.id_despacho_fk = d.id_despacho) AS total_articulos,
					d.estado_orden,
					(SELECT EXISTS(
						SELECT 1 FROM table_alm_requisicion_detalle rd
						JOIN table_alm_requisicion r ON rd.id_requisicion_fk = r.id_requisicion
						JOIN table_alm_relacion_producto rpr ON rd.id_producto = rpr.id_producto
						WHERE r.id_despacho_fk = d.id_despacho AND rpr.cant_producto < rd.cantidad_solicitada
					)) AS has_insufficient_stock_items
				FROM table_alm_despacho d
				INNER JOIN table_flota f ON d.id_flota = f.id_flota
				INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
				INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
				INNER JOIN table_usuarios u ON d.user_id = u.usuario_id
				INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
				WHERE d.status_despacho = 1
				ORDER BY d.id_despacho DESC";
		
		$data = $this->select_all($sql);
		return $data;
	}

	/**
	 * Obtiene órdenes filtradas por un estado específico.
	 * @param int $estado El estado de la orden (1: Requisición, 2: Aprobada, 3: Despachada).
	 * @return array Datos para DataTables.
	 */
	public function selectOrdenesPorEstado(int $estado): array
	{
		$sql = "SELECT 
					d.id_despacho,
					d.fecha_aprobacion,
					f.id_unidad, 
					m.marca_unidad, 
					mo.modelo_unidad,
					CONCAT(p.personal_nombre, ' ', p.personal_apellido) as creador_nombre,
					(SELECT COUNT(*) FROM table_alm_requisicion_detalle rd JOIN table_alm_requisicion r ON rd.id_requisicion_fk = r.id_requisicion WHERE r.id_despacho_fk = d.id_despacho) AS total_articulos,
					d.estado_orden
				FROM table_alm_despacho d
				LEFT JOIN table_flota f ON d.id_flota = f.id_flota
				LEFT JOIN table_flota_marca m ON f.id_marca = m.id_marca
				LEFT JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
				LEFT JOIN table_usuarios u ON d.user_id = u.usuario_id
				LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
				WHERE d.status_despacho = 1 AND d.estado_orden = ?
				ORDER BY d.id_despacho ASC";
		return $this->select_all($sql, [$estado]);
	}

	// obtener la lista de articulos por cada despacho y mostralos en el tmeline de ordenes
	public function getListArtDesp(int $intDesp){
		$this->intDesp = $intDesp;
		$sql = "SELECT producto.*, relacionP.cant_despacho, enlaceP.* , u.ubicacion
			FROM table_alm_producto producto 
			JOIN table_alm_relacion_despacho relacionP ON producto.id_producto = relacionP.id_producto 
			JOIN table_alm_enlace_producto enlaceP ON enlaceP.id_enlace_producto = producto.id_enlace_producto
			JOIN table_alm_ubicacion u ON u.id_ubicacion = producto.id_ubicacion
			WHERE relacionP.id_despacho = ? ";
		$request = $this->select_all($sql, [$this->intDesp]);
		return $request;
	}
	// obtener codigo  fecha o unidad para buscador
	public function getListBuscarOrdenes(string $strCod,string $strFecha, string $strUnidad,string $strArt){
		$sql = "SELECT 
					d.id_despacho,
					d.fecha_despacho,
					f.id_unidad, 
					m.marca_unidad, 
					mo.modelo_unidad,
					d.operador AS operador_nombre,
					d.mecanico AS mecanico_nombre,
					d.despachador AS despachador_nombre,
					(SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos,
					u.usuario_nick as usuario_registro
				FROM table_alm_despacho d
				INNER JOIN table_flota f ON d.id_flota = f.id_flota
				INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
				INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
				INNER JOIN table_usuarios u ON d.user_id = u.usuario_id
				WHERE d.status_despacho = 1";

		$params = [];
		$conditions = [];

		if (!empty($strCod)) {
			$conditions[] = "d.id_despacho = ?";
			$params[] = $strCod;
		}
		if (!empty($strFecha)) {
			$conditions[] = "d.fecha_despacho = ?";
			$params[] = $strFecha;
		}
		if (!empty($strUnidad)) {
			$conditions[] = "f.id_unidad LIKE ?";
			$params[] = "%".$strUnidad."%";
		}
		if (!empty($strArt)) {
			$conditions[] = "d.id_despacho IN (SELECT rd.id_despacho FROM table_alm_relacion_despacho rd JOIN table_alm_producto p ON rd.id_producto = p.id_producto WHERE p.producto LIKE ?)";
			$params[] = "%".$strArt."%";
		}

		if (!empty($conditions)) {
			$sql .= " AND (" . implode(' OR ', $conditions) . ")";
		}

		$sql .= " ORDER BY d.id_despacho DESC";
		$request = $this->select_all($sql, $params);
		return $request;
	}
	// obtener orden de despacho para la impersion y generar pdf
	public function selectDepacho(int $strCod){
		$this->idDespacho = $strCod;
		$sql = "SELECT 
					desp.id_despacho, desp.fecha_despacho, desp.observacion, desp.estado_orden,
					desp.operador AS operador_nombre,
					desp.mecanico AS mecanico_nombre,
					desp.despachador AS despachador_nombre,
					flota.id_unidad, flota.vim_unidad,
					modelo.modelo_unidad, 
					marca.marca_unidad,
					CONCAT(p.personal_nombre, ' ', p.personal_apellido) as usuario_registro
				FROM table_alm_despacho desp
				INNER JOIN table_flota flota ON desp.id_flota = flota.id_flota
				INNER JOIN table_usuarios usuario ON desp.user_id = usuario.usuario_id
				INNER JOIN table_personal p ON usuario.usuario_id_personal = p.id_personal
				INNER JOIN table_flota_modelo modelo ON modelo.id_modelo = flota.id_modelo
				INNER JOIN table_flota_marca marca ON marca.id_marca = flota.id_marca 
				WHERE desp.id_despacho = ?";
		$request = $this->select($sql,[$this->idDespacho]);
		return $request;
	}

	/**************************************************/
	/** TODO: ELIMINACIÓN Y REVERSIÓN DE ÓRDENES ******/
	/**************************************************/

	// eliminar orden y hacewr cambios de devolucion de ordenes
	public function delDesp(int $idDesp, string $srtText, int $intUserId){
		$this->idDesp = $idDesp;
		$this->srtText = $srtText;
		$this->srtInfo = strtoupper('USUARIO '.$_SESSION['userData']['usuario_nick'].' ELIMINO ORDEN DE DESPACHO #' . $idDesp);
		$this->intUserId = $intUserId;
		$intStatus = 0;
		$sql = "UPDATE table_alm_despacho SET status_despacho = ? WHERE id_despacho = ?";
		$arrData = array($intStatus, $this->idDesp); 
		$request = $this->update($sql,$arrData);
		if($request){
			$insertH = "INSERT INTO table_alm_historial_cambio(obs, info, id_user) VALUES(?,?,?)";
			$arrDataH = array($this->srtText,$this->srtInfo,$this->intUserId);
			$requestInsert = $this->insert($insertH,$arrDataH);
		}
		return $request;
	}
	// obtener los articulos de la orden a eliminar para revertir los cambios y sumarles las cantidades
	public function artDespacho(int $idDesp){
		$this->idDesp = $idDesp;
		$sql = "SELECT tProducto.producto, tRelacionP.cant_producto, tRelacionD.* FROM table_alm_producto tProducto
				INNER JOIN table_alm_relacion_producto tRelacionP ON tProducto.id_producto = tRelacionP.id_producto
				INNER JOIN table_alm_relacion_despacho tRelacionD ON tRelacionD.id_producto = tRelacionP.id_producto 
				WHERE tRelacionD.id_despacho = ?";
		$request = $this->select_all($sql, [$this->idDesp]);
		return $request;
	}
	// actualizar la tabla de productos con la devolucion de articulos
	public function updateCantN(int $intIdArticulo, float $intCant){
		$this->intIdArticulo = $intIdArticulo;
		$this->intCant = $intCant;
		$queryUpdate = "UPDATE table_alm_relacion_producto SET cant_producto = ? WHERE id_producto = ?";
		$arrData = array($this->intCant, $this->intIdArticulo);
		$requestUpdate = $this->update($queryUpdate, $arrData);
		return $requestUpdate;
	}

	/**
	 * Restaura una orden eliminada cambiando su estado a 1.
	 * @param int $idDespacho ID de la orden a restaurar.
	 * @return bool
	 */
	public function restoreOrder(int $idDespacho) {
		$sql = "UPDATE table_alm_despacho SET status_despacho = 1 WHERE id_despacho = ?";
		return $this->update($sql, [$idDespacho]);
	}

	/**************************************************/
	/*********** TODO: ESTADÍSTICAS VARIAS ************/
	/**************************************************/

	public function insertRaw(string $sql, array $params): void {
		$this->insert($sql, $params);
	}

	/** Obtiene el conteo de órdenes del mes actual para la barra de progreso */
	public function getMonthlyOrderCount() {
		$currentMonth = date('Y-m');
		$sql = "SELECT COUNT(id_despacho) as total_ordenes 
				FROM table_alm_despacho 
				WHERE DATE_FORMAT(fecha_despacho, '%Y-%m') = ? AND status_despacho = 1";
		return $this->select($sql, [$currentMonth]);
	}

	/**
	 * Verifica si ya existe una notificación 'en_proceso' activa para una orden hoy.
	 */
	public function existeNotifEnProceso(int $idDespacho): bool {
		$sql = "SELECT COUNT(*) as total FROM table_notificaciones
				WHERE tipo_notificacion = 'orden_en_proceso'
				  AND id_referencia = ?
				  AND leido = 0
				  AND DATE(fecha_creacion) = CURDATE()";
		$result = $this->select($sql, [$idDespacho]);
		return ($result['total'] ?? 0) > 0;
	}

	/**
	 * Inserta una notificación de orden en proceso para Operaciones.
	 */
	public function insertarNotifEnProceso(int $idDespacho, string $nick, string $unidad): void {
		$sql = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje)
				VALUES ('orden_en_proceso', ?, ?)";
		$mensaje = "Orden #{$idDespacho} (Unidad {$unidad}) en proceso de compra. Notificado por {$nick}.";
		$this->insert($sql, [$idDespacho, $mensaje]);
	}
}
?>
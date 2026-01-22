<?php
require_once 'system/app/Models/PersonalModel.php'; // Asegúrate de que la ruta sea correcta

class RequisicionModel extends Mysql {
    private $personalModel;

    public function __construct(){
        parent::__construct();
        $this->personalModel = new PersonalModel();
    }
    /**
     * Selecciona todas las requisiciones para la DataTable.
     */
    public function selectRequisiciones($status = null) {
        $sql = "SELECT 
                    d.id_despacho as id_requisicion,
                    d.fecha_despacho as fecha_requisicion,
                    f.id_unidad,
                    'N/A' as tipo_orden, -- Este campo no existe en despachos, se pone un placeholder
                    d.estado_orden as status_requisicion,
                    d.despachador as creador -- Usamos el despachador como creador
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                WHERE 1=1"; // Placeholder para futuras condiciones

        if ($status === 'Pendiente') {
            $sql .= " AND d.estado_orden = 1"; // 1 = Requisicion (Pendiente de aprobación)
        }

        $sql .= " ORDER BY d.id_despacho DESC";
        return $this->select_all($sql);
    }

    /**
     * Selecciona una requisición específica con sus detalles.
     */
    public function selectRequisicionById(int $idRequisicion){ // $idRequisicion aquí es el id_despacho_fk
        // El $idRequisicion que recibimos es en realidad el id_despacho de table_alm_despacho.
        // El $idRequisicion que recibimos es en realidad el id_despacho que actúa como ID de flujo.
        $sql = "SELECT 
                    r.id_requisicion as id_requisicion_interna,
                    d.id_despacho as id_despacho_flujo,
                    d.fecha_despacho as fecha_requisicion,
                    COALESCE(r.observacion, d.observacion) as diagnostico,
                    -- CORRECCIÓN: Usar el estado de la tabla de despacho, que es el estado maestro del flujo.
                    d.estado_orden as status_requisicion,
                    d.id_flota,
                    COALESCE(r.mecanico_cedula, d.mecanico) as mecanico_cedula,
                    COALESCE(r.tipo_orden, 'Despacho Directo') as tipo_orden,
                    d.user_id as user_id_creador,
                    -- CORRECCIÓN: Obtener el operador y despachador final. Usar COALESCE para mostrar el nombre guardado si no hay coincidencia en la tabla de personal.
                    COALESCE(CONCAT(p_operador.personal_nombre, ' ', p_operador.personal_apellido), d.operador) as operador_final,
                    COALESCE(CONCAT(p_despachador.personal_nombre, ' ', p_despachador.personal_apellido), d.despachador) as despachador_final,
                    CONCAT(p_creador.personal_nombre, ' ', p_creador.personal_apellido) as jefe_patio_nombre,
                    COALESCE(CONCAT(p_mec.personal_nombre, ' ', p_mec.personal_apellido), d.mecanico) as mecanico_nombre,
                    f.id_unidad,
                    fm.modelo_unidad
                FROM table_alm_despacho d
                LEFT JOIN table_alm_requisicion r ON d.id_despacho = r.id_despacho_fk
                INNER JOIN table_usuarios u ON d.user_id = u.usuario_id
                INNER JOIN table_personal p_creador ON u.usuario_id_personal = p_creador.id_personal
                LEFT JOIN table_personal p_operador ON d.operador = p_operador.id_personal
                LEFT JOIN table_personal p_despachador ON d.despachador = p_despachador.id_personal
                LEFT JOIN table_personal p_mec ON r.mecanico_cedula = p_mec.personal_cedula
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE d.id_despacho = ?";
        
        $requisicion = $this->select($sql, [$idRequisicion]);

        if($requisicion){
            // Obtenemos los artículos de la tabla de detalle de requisición usando el ID interno
            if (!empty($requisicion['id_requisicion_interna'])) {
                $sql_articulos = "SELECT rd.cantidad_solicitada as cant_despacho, p.id_producto, p.producto, p.present_producto FROM table_alm_requisicion_detalle rd JOIN table_alm_producto p ON rd.id_producto = p.id_producto WHERE rd.id_requisicion_fk = ?";
                $requisicion['articulos'] = $this->select_all($sql_articulos, [$requisicion['id_requisicion_interna']]);
            } else {
                // Si no hay requisición interna, buscamos en la relación de despacho (para despachos directos)
                $sql_articulos = "SELECT rd.cant_despacho, p.id_producto, p.producto, p.present_producto FROM table_alm_relacion_despacho rd JOIN table_alm_producto p ON rd.id_producto = p.id_producto WHERE rd.id_despacho = ?";
                $requisicion['articulos'] = $this->select_all($sql_articulos, [$idRequisicion]);
            }
        }
        return $requisicion;
    }

    /**
     * Aprueba una requisición, genera una orden de despacho y actualiza el inventario.
     * Todo dentro de una transacción para garantizar la integridad de los datos.
     */
    public function aprobarRequisicionYGenerarDespacho(int $idRequisicion, int $idUsuarioAprobador) {
        try {
            // 1. Obtener datos del despacho (que funciona como requisición) y sus artículos
            $requisicion = $this->selectRequisicionById($idRequisicion);
            if (empty($requisicion) || $requisicion['status_requisicion'] != 1) {
                throw new Exception('La requisición no existe o ya fue procesada.');
            }

            // 2. Validar stock de todos los artículos ANTES de procesar
            foreach ($requisicion['articulos'] as $item) {
                $stockActual = $this->select("SELECT cant_producto FROM table_alm_relacion_producto WHERE id_producto = ?", [$item['id_producto']])['cant_producto'] ?? 0;
                if ($stockActual < $item['cant_despacho']) {
                    throw new Exception("Stock insuficiente para el artículo '{$item['producto']}'. Solicitado: {$item['cant_despacho']}, Disponible: {$stockActual}.");
                }
            }

            // 3. Descontar el stock del inventario
            foreach ($requisicion['articulos'] as $item) {
                // Actualizar (restar) stock en `table_alm_relacion_producto`
                $sql_update_stock = "UPDATE table_alm_relacion_producto SET cant_producto = cant_producto - ? WHERE id_producto = ?";
                $this->update($sql_update_stock, [$item['cant_despacho'], $item['id_producto']]);
            }

            // 4. Actualizar el estado del despacho a "Aprobada" (estado_orden = 2) para que Almacén pueda procesarlo.
            $sql_update_despacho = "UPDATE table_alm_despacho SET estado_orden = 2 WHERE id_despacho = ?";
            $this->update($sql_update_despacho, [$idRequisicion]);

            // 5. Marcar la notificación de 'nueva_requisicion' como leída
            $sql_update_notif = "UPDATE table_notificaciones SET leido = 1 WHERE tipo_notificacion = 'nueva_requisicion' AND id_referencia = ?";
            $this->update($sql_update_notif, [$idRequisicion]);

            // 6. Crear una nueva notificación para Almacén para que despachen la orden
            $sql_notificacion_almacen = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje, leido) VALUES (?, ?, ?, 0)";
            $mensaje_almacen = "Despacho #${idRequisicion} pendiente de preparación en Almacén.";
            $this->insert($sql_notificacion_almacen, ['despacho_pendiente', $idRequisicion, $mensaje_almacen]);

            return $idRequisicion; // Devolvemos el mismo ID

        } catch (Exception $e) {
            // Loguear el error y lanzarlo para que el controlador lo capture
            error_log("Error en aprobarRequisicionYGenerarDespacho: " . $e->getMessage());
            throw $e;
        }
    }

    /**
	 * Inserta una requisición completa:
	 * 1. Crea un registro en `table_alm_despacho` para mantener el flujo y notificaciones.
	 * 2. Crea el registro principal en `table_alm_requisicion`.
	 * 3. Inserta los artículos en `table_alm_requisicion_detalle`.
	 * 4. Genera la notificación para Compras.
	 * Todo esto se hace dentro de una transacción.
	 */
	public function insertRequisicionCompleta(int $intUnidad, string $srtMec, string $tipoOrden, string $srtObs, int $intIdUser, string $strDate, array $articulos) {
		try {
			// Paso 1: Crear la entrada en `table_alm_despacho` para generar la notificación y mantener el flujo.
			// Usamos placeholders para operador y despachador, ya que se llenarán después.
            // Obtener el nombre completo del mecánico a partir de su cédula
            $mechanicInfo = $this->personalModel->selectPersonalFullNameByCedula($srtMec); // $srtMec es la cédula
            $mechanicName = $srtMec; // Valor por defecto en caso de no encontrar el nombre
            if ($mechanicInfo) {
                $mechanicName = trim($mechanicInfo['personal_nombre'] . ' ' . $mechanicInfo['personal_apellido']);
            }

			$queryDespacho = "INSERT INTO table_alm_despacho(id_flota, operador, mecanico, despachador, fecha_despacho, user_id, observacion, status_despacho, estado_orden) VALUES(?,?,?,?,?,?,?,?,?)";
			$idDespacho = $this->insert($queryDespacho, [$intUnidad, 'N/A', $mechanicName, 'N/A', $strDate, $intIdUser, $srtObs, 1, 1]);

			if ($idDespacho <= 0) {
				throw new Exception("No se pudo crear el registro de despacho base.");
			}

			// Paso 2: Crear el registro principal en `table_alm_requisicion`
			$queryRequisicion = "INSERT INTO table_alm_requisicion (id_despacho_fk, id_flota, mecanico_cedula, tipo_orden, observacion, user_id_creador) VALUES (?, ?, ?, ?, ?, ?)";
			$idRequisicion = $this->insert($queryRequisicion, [$idDespacho, $intUnidad, $srtMec, $tipoOrden, $srtObs, $intIdUser]);

			if ($idRequisicion <= 0) {
				throw new Exception("No se pudo crear la requisición principal.");
			}

			// Paso 3: Insertar los artículos en `table_alm_requisicion_detalle`
			foreach ($articulos as $articulo) {
				$idArticulo = $articulo['id'];
				$cantidad = $articulo['cantidad'];
				$queryDetalle = "INSERT INTO table_alm_requisicion_detalle (id_requisicion_fk, id_producto, cantidad_solicitada) VALUES (?, ?, ?)";
				$this->insert($queryDetalle, [$idRequisicion, $idArticulo, $cantidad]);
			}

			// Paso 4: Generar la notificación para Compras (aprovechando la lógica existente)
			$sql_user = "SELECT p.personal_nombre, p.personal_apellido FROM table_usuarios u JOIN table_personal p ON u.usuario_id_personal = p.id_personal WHERE u.usuario_id = ?";
            $user_info = $this->select($sql_user, [$intIdUser]);
            $creator_name = $user_info ? trim($user_info['personal_nombre'] . ' ' . $user_info['personal_apellido']) : 'Usuario del sistema';

            $sql_notificacion = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje) VALUES (?, ?, ?)";
            $mensaje = "Nueva Requisición #{$idDespacho} creada por {$creator_name}.";
            $this->insert($sql_notificacion, ['nueva_requisicion', $idDespacho, $mensaje]);

			return $idDespacho; // Devolvemos el ID del despacho que es el que controla el flujo

		} catch (Exception $e) {
			error_log("Error en insertRequisicionCompleta: " . $e->getMessage());
			return 0; // Retornar 0 en caso de error
		}
	}

    /**
     * Obtiene la lista de todas las requisiciones para la DataTable.
     * Se basa en la tabla `table_alm_despacho` que actúa como el registro maestro del flujo.
     */
    public function getRequisiciones() {
        $sql = "SELECT 
                    d.id_despacho,
                    d.fecha_despacho,
                    f.id_unidad,
                    req.tipo_orden,
                    p.personal_nombre as creador_nombre,
                    p.personal_apellido as creador_apellido,
                    d.estado_orden
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_usuarios u ON d.user_id = u.usuario_id
                INNER JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                LEFT JOIN table_alm_requisicion req ON d.id_despacho = req.id_despacho_fk
                WHERE d.status_despacho = 1
                ORDER BY d.id_despacho DESC";
        return $this->select_all($sql);
    }

    /**
     * Aprueba una requisición cambiando su estado y notificando a Almacén.
     * @param int $idDespacho El ID del despacho que representa la requisición.
     * @param int $idUsuarioAprobador El ID del usuario de Compras que aprueba.
     * @return bool
     */
    public function aprobarRequisicion(int $idDespacho, int $idUsuarioAprobador) {
        try {
            // 1. Verificar que la orden exista y esté pendiente
            $orden = $this->select("SELECT estado_orden FROM table_alm_despacho WHERE id_despacho = ?", [$idDespacho]);
            if (empty($orden) || $orden['estado_orden'] != 1) {
                throw new Exception("La requisición no existe o ya fue procesada.");
            }

            // 2. Actualizar el estado de la orden en `table_alm_despacho` a 'Aprobada' (2)
            $sql_update_despacho = "UPDATE table_alm_despacho SET estado_orden = 2, usuario_aprobador_id = ?, fecha_aprobacion = NOW() WHERE id_despacho = ?";
            $this->update($sql_update_despacho, [$idUsuarioAprobador, $idDespacho]);

            // 3. Actualizar el estado en la tabla de requisición específica
            $sql_update_req = "UPDATE table_alm_requisicion SET status_requisicion = 2 WHERE id_despacho_fk = ?";
            $this->update($sql_update_req, [$idDespacho]);

            // 4. Marcar la notificación original para Compras como leída
            $sql_update_notif_compras = "UPDATE table_notificaciones SET leido = 1 WHERE tipo_notificacion = 'nueva_requisicion' AND id_referencia = ?";
            $this->update($sql_update_notif_compras, [$idDespacho]);

            // 5. Crear una nueva notificación para Almacén
            $sql_notificacion_almacen = "INSERT INTO table_notificaciones (tipo_notificacion, id_referencia, mensaje, leido) VALUES (?, ?, ?, 0)";
            $mensaje_almacen = "Requisición #${idDespacho} aprobada. Pendiente de despacho en Almacén.";
            $this->insert($sql_notificacion_almacen, ['despacho_pendiente', $idDespacho, $mensaje_almacen]);

            return true;
        } catch (Exception $e) {
            throw $e; // Re-lanzar la excepción para que el controlador la capture
        }
    }

}
<?php
class PublicoModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * =================================================================
     * INSTITUCIONES
     * =================================================================
     */
    
    /**
     * Devuelve las instituciones activas para los selectores.
     */
    public function getInstituciones() {
        $query = "SELECT id_institucion, nombre 
                  FROM table_instituciones 
                  WHERE status = 1 
                  ORDER BY id_institucion ASC";
        return $this->select_all($query);
    }
    
    /**
     * Devuelve el nombre de una institución por su ID.
     */
    public function getNombreInstitucion($idInstitucion) {
        $query = "SELECT nombre FROM table_instituciones WHERE id_institucion = ?";
        $result = $this->select($query, [intval($idInstitucion)]);
        return $result['nombre'] ?? 'Institución Desconocida';
    }
    
    /**
     * =================================================================
     * ESTADO DE FLOTA (con filtro por institución)
     * =================================================================
     */
    
    /**
     * Devuelve conteos de flota por estado para una institución.
     */
    public function getEstadoFlota($idInstitucion) {
        $query = "SELECT status_unidad, COUNT(*) as total 
                  FROM table_flota 
                  WHERE status_unidad BETWEEN 1 AND 4 
                    AND id_institucion = ? 
                  GROUP BY status_unidad";
        $statusCounts = $this->select_all($query, [intval($idInstitucion)]);
        
        $operativas = 0; 
        $inoperativas = 0; 
        $enMantenimiento = 0; 
        $criticas = 0;
        
        foreach ($statusCounts as $row) {
            switch ($row['status_unidad']) {
                case 1: $operativas = (int)$row['total']; break;
                case 2: $inoperativas = (int)$row['total']; break;
                case 3: $enMantenimiento = (int)$row['total']; break;
                case 4: $criticas = (int)$row['total']; break;
            }
        }
        
        $total = $operativas + $inoperativas + $enMantenimiento + $criticas;
        
        return [
            'total' => $total,
            'operativas' => $operativas,
            'inoperativas' => $inoperativas,
            'en_mantenimiento' => $enMantenimiento,
            'criticas' => $criticas
        ];
    }
    
    /**
     * Devuelve conteos de cambio de aceite para una institución.
     */
    public function getEstadoAceite($idInstitucion) {
        $query = "SELECT tf.id_flota,
            COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = tf.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) as kilometraje_actual,
            COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = tf.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as proximo_cambio_km
            FROM table_flota tf 
            WHERE tf.status_unidad = 1 
              AND tf.id_institucion = ?";
        $aceites = $this->select_all($query, [intval($idInstitucion)]);
        
        $requerido = 0; 
        $proximo = 0; 
        $ok = 0;
        
        foreach ($aceites as $row) {
            $kmActual = (int)$row['kilometraje_actual'];
            $kmProximo = (int)$row['proximo_cambio_km'];
            $diferencia = $kmProximo - $kmActual;
            if ($kmProximo > 0) {
                if ($diferencia <= 0) $requerido++;
                elseif ($diferencia <= 1000) $proximo++;
                else $ok++;
            }
        }
        
        return [
            'requerido' => $requerido, 
            'proximo' => $proximo, 
            'ok' => $ok
        ];
    }
    
    /**
     * Devuelve el resumen de flota por modelo para una institución.
     */
    public function getResumenFlota($idInstitucion) {
        $query = "SELECT fm.marca_unidad as marca, fmo.modelo_unidad as modelo,
            CONCAT(fm.marca_unidad, ' ', fmo.modelo_unidad) as marca_modelo,
            f.transmision, f.tipo_combustible as combustible, f.status_unidad, COUNT(*) as total
            FROM table_flota f
            JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
            JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
            WHERE f.status_unidad IN (1, 2, 3, 5)
              AND f.id_institucion = ?
            GROUP BY fm.marca_unidad, fmo.modelo_unidad, f.transmision, f.tipo_combustible, f.status_unidad
            ORDER BY fm.marca_unidad, fmo.modelo_unidad";
        $results = $this->select_all($query, [intval($idInstitucion)]);
        
        $grouped = [];
        foreach ($results as $row) {
            $key = $row['marca_modelo'] . '|' . $row['transmision'] . '|' . $row['combustible'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'marca_modelo' => $row['marca_modelo'], 
                    'transmision' => $row['transmision'],
                    'combustible' => $row['combustible'], 
                    'total' => 0, 
                    'operativas' => 0, 
                    'inoperativas' => 0
                ];
            }
            $grouped[$key]['total'] += (int)$row['total'];
            if ($row['status_unidad'] == 1) {
                $grouped[$key]['operativas'] += (int)$row['total'];
            } else {
                $grouped[$key]['inoperativas'] += (int)$row['total'];
            }
        }
        
        return array_values($grouped);
    }
    
    /**
     * Devuelve las unidades filtradas por estado e institución.
     */
    public function getUnidadesPorEstado($status, $idInstitucion) {
        $query = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.fecha_creacion,
            fm.marca_unidad AS marca_unidad, fmo.modelo_unidad AS modelo_unidad,
            f.transmision, f.tipo_combustible, f.status_unidad,
            COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) as km_actual,
            COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as ultimo_cambio_aceite,
            COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as proximo_cambio_aceite
            FROM table_flota f
            INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
            INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
            WHERE f.status_unidad = ? 
              AND f.id_institucion = ?
            ORDER BY f.id_unidad";
        return $this->select_all($query, [intval($status), intval($idInstitucion)]);
    }
    
    /**
     * Devuelve las unidades filtradas por estado de aceite e institución.
     */
    public function getUnidadesPorEstadoAceite($status, $idInstitucion) {
        $query = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.fecha_creacion,
            fm.marca_unidad AS marca_unidad, fmo.modelo_unidad AS modelo_unidad,
            f.transmision, f.tipo_combustible, f.status_unidad,
            COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) as km_actual,
            COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as ultimo_cambio_aceite,
            COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) as proximo_cambio_aceite
            FROM table_flota f
            INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
            INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
            WHERE f.status_unidad = 1 
              AND f.id_institucion = ?";
        
        $params = [intval($idInstitucion)];
        
        if ($status === 'requerido') {
            $query .= " AND (EXISTS (SELECT 1 FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota AND ah.kilometraje_cambio > 0)
                AND (COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) <= 0))";
        } elseif ($status === 'proximo') {
            $query .= " AND ((COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) > 0)
                AND (COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) <= 1000))";
        } elseif ($status === 'ok') {
            $query .= " AND ((COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END FROM table_flota_aceite_historial ah WHERE ah.id_flota = f.id_flota ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) - 
                COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) > 1000))";
        }
        
        $query .= " ORDER BY f.id_unidad";
        return $this->select_all($query, $params);
    }
    
    /**
     * =================================================================
     * MOVIMIENTOS (con filtro por institución)
     * =================================================================
     */
    
    /**
     * Get despachos de almacén filtrados por institución.
     * Solo despachos cuyas unidades pertenezcan a la institución indicada.
     * NOTA: Si el despacho no tiene flota vinculada, NO se filtra.
     * Las columnas operador, mecanico, despachador fueron eliminadas.
     * Se obtienen los nombres mediante JOINs con table_personal usando los IDs.
     * Usa CONCAT_WS para concatenar nombre y apellido saltando valores NULL.
     */
    public function getDespachosPublic($fechaInicio, $fechaFin, $idInstitucion) {
        $query = "
            SELECT 
                d.id_despacho,
                d.id_flota,
                f.id_unidad,
                d.operador_id,
                d.mecanico_id,
                d.despachador_id,
                CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido, '0')) AS operador_nombre,
                CONCAT_WS(' ', p_mec.personal_nombre, NULLIF(p_mec.personal_apellido, '0')) AS mecanico_nombre,
                CONCAT_WS(' ', p_desp.personal_nombre, NULLIF(p_desp.personal_apellido, '0')) AS despachador_nombre,
                d.fecha_despacho,
                d.observacion,
                d.estado_orden,
                d.status_despacho
            FROM table_alm_despacho d
            LEFT JOIN table_flota f ON d.id_flota = f.id_flota
            LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
            LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
            LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
            WHERE d.fecha_despacho BETWEEN ? AND ?
              AND (f.id_flota IS NULL OR f.id_institucion = ?)
            ORDER BY d.fecha_despacho DESC
        ";
        $despachos = $this->select_all($query, [$fechaInicio, $fechaFin, intval($idInstitucion)]);
        
        if (!empty($despachos)) {
            $ids = array_column($despachos, 'id_despacho');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $queryProductos = "
                SELECT 
                    rd.id_despacho,
                    p.producto,
                    p.present_producto,
                    rd.cant_despacho
                FROM table_alm_relacion_despacho rd
                JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                WHERE rd.id_despacho IN ($placeholders)
            ";
            $productos = $this->select_all($queryProductos, $ids);
            
            $productosByDespacho = [];
            foreach ($productos as $prod) {
                $productosByDespacho[$prod['id_despacho']][] = $prod;
            }
            
            foreach ($despachos as &$despacho) {
                $despacho['productos'] = $productosByDespacho[$despacho['id_despacho']] ?? [];
            }
        }
        
        return $despachos;
    }
    
    /**
     * Get mantenimientos de flota filtrados por institución.
     */
    public function getMantenimientosPublic($fechaInicio, $fechaFin, $idInstitucion) {
        $query = "
            SELECT 
                m.id_unidad_mantenimiento,
                m.id_flota,
                f.id_unidad,
                m.ruta_unidad,
                m.operardor_unidad,
                m.nomb_mecanico,
                m.km_unidad,
                m.tipo_mantenimiento,
                m.diagnostico,
                m.recomendacion,
                m.obsOperador,
                m.obsSupervisor,
                m.obsSalida,
                m.fecha_entrada,
                m.fecha_salida,
                m.status_mantenimiento,
                m.usuario_id,
                u.usuario_nick
            FROM table_flota_mantenimiento m
            LEFT JOIN table_flota f ON m.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON m.usuario_id = u.usuario_id
            WHERE m.fecha_entrada BETWEEN ? AND ?
              AND f.id_institucion = ?
            ORDER BY m.fecha_entrada DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin, intval($idInstitucion)]);
    }
    
    /**
     * Get cambios de aceite filtrados por institución.
     */
    public function getCambiosAceitePublic($fechaInicio, $fechaFin, $idInstitucion) {
        $query = "
            SELECT 
                ah.id_aceite_historial,
                ah.id_flota,
                f.id_unidad,
                ah.fecha_cambio,
                ah.kilometraje_cambio,
                ah.kilometraje_anterior,
                ah.kilometraje_proximo_cambio,
                ah.usuario_id,
                ah.observaciones,
                u.usuario_nick
            FROM table_flota_aceite_historial ah
            LEFT JOIN table_flota f ON ah.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON ah.usuario_id = u.usuario_id
            WHERE ah.fecha_cambio BETWEEN ? AND ?
              AND f.id_institucion = ?
            ORDER BY ah.fecha_cambio DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin, intval($idInstitucion)]);
    }
    
    /**
     * Get kilometraje filtrado por institución.
     */
    public function getKilometrajePublic($fechaInicio, $fechaFin, $idInstitucion) {
        $query = "
            SELECT 
                k.id_kilometraje,
                k.id_flota,
                f.id_unidad,
                k.kilometraje_actual,
                k.fecha_actualizacion,
                k.usuario_id,
                u.usuario_nick
            FROM table_flota_kilometraje k
            LEFT JOIN table_flota f ON k.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON k.usuario_id = u.usuario_id
            WHERE DATE(k.fecha_actualizacion) BETWEEN ? AND ?
              AND f.id_institucion = ?
            ORDER BY k.fecha_actualizacion DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin, intval($idInstitucion)]);
    }
    
    /**
     * =================================================================
     * DETALLES (con nombre de institución)
     * =================================================================
     */
    
    /**
     * Get detalle de un cambio de aceite específico.
     */
    public function getDetalleAceite($idAceite) {
        $query = "
            SELECT 
                ah.id_aceite_historial,
                ah.id_flota,
                f.id_unidad,
                f.vim_unidad,
                ah.fecha_cambio,
                ah.kilometraje_cambio,
                ah.kilometraje_anterior,
                ah.kilometraje_proximo_cambio,
                ah.usuario_id,
                ah.observaciones,
                u.usuario_nick,
                COALESCE(CONCAT(p.personal_nombre, ' ', p.personal_apellido), u.usuario_nick, 'Sistema') as responsable_nombre
            FROM table_flota_aceite_historial ah
            LEFT JOIN table_flota f ON ah.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON ah.usuario_id = u.usuario_id
            LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
            WHERE ah.id_aceite_historial = ?
        ";
        return $this->select($query, [$idAceite]);
    }
    
    /**
     * Get detalle de un mantenimiento específico.
     */
    public function getDetalleMantenimiento($idMantenimiento) {
        $query = "
            SELECT 
                m.id_unidad_mantenimiento,
                m.id_flota,
                f.id_unidad,
                f.vim_unidad,
                m.ruta_unidad,
                m.operardor_unidad,
                m.nomb_mecanico,
                m.km_unidad,
                m.tipo_mantenimiento,
                m.diagnostico,
                m.recomendacion,
                m.obsOperador,
                m.obsSupervisor,
                m.obsSalida,
                m.fecha_entrada,
                m.fecha_salida,
                m.status_mantenimiento,
                m.usuario_id,
                u.usuario_nick,
                COALESCE(CONCAT(p.personal_nombre, ' ', p.personal_apellido), u.usuario_nick, 'Sistema') as responsable_nombre
            FROM table_flota_mantenimiento m
            LEFT JOIN table_flota f ON m.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON m.usuario_id = u.usuario_id
            LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
            WHERE m.id_unidad_mantenimiento = ?
        ";
        return $this->select($query, [$idMantenimiento]);
    }
    
    /**
     * Get detalle de una actualización de kilometraje específica.
     */
    public function getDetalleKilometraje($idKilometraje) {
        $query = "
            SELECT 
                k.id_kilometraje,
                k.id_flota,
                f.id_unidad,
                f.vim_unidad,
                k.kilometraje_actual,
                k.fecha_actualizacion,
                k.usuario_id,
                u.usuario_nick,
                COALESCE(CONCAT(p.personal_nombre, ' ', p.personal_apellido), u.usuario_nick, 'Sistema') as responsable_nombre,
                (
                    SELECT k2.kilometraje_actual 
                    FROM table_flota_kilometraje k2 
                    WHERE k2.id_flota = k.id_flota 
                      AND (
                        k2.fecha_actualizacion < k.fecha_actualizacion 
                        OR (k2.fecha_actualizacion = k.fecha_actualizacion AND k2.id_kilometraje < k.id_kilometraje)
                      )
                    ORDER BY k2.fecha_actualizacion DESC, k2.id_kilometraje DESC 
                    LIMIT 1
                ) as kilometraje_anterior
            FROM table_flota_kilometraje k
            LEFT JOIN table_flota f ON k.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON k.usuario_id = u.usuario_id
            LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
            WHERE k.id_kilometraje = ?
        ";
        return $this->select($query, [$idKilometraje]);
    }
    
    /**
     * Get unit info for public view.
     */
    public function getUnidadInfo($idFlota) {
        $query = "
            SELECT 
                f.id_flota,
                f.id_unidad,
                fm.marca_unidad as marca,
                fmo.modelo_unidad as modelo,
                f.transmision,
                f.tipo_combustible as combustible,
                f.status_unidad,
                COALESCE((
                    SELECT km.kilometraje_actual 
                    FROM table_flota_kilometraje km 
                    WHERE km.id_flota = f.id_flota 
                    ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC 
                    LIMIT 1
                ), 0) as km_actual,
                COALESCE((
                    SELECT ah.kilometraje_cambio 
                    FROM table_flota_aceite_historial ah 
                    WHERE ah.id_flota = f.id_flota 
                    ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC 
                    LIMIT 1
                ), 0) as ultimo_cambio_aceite,
                COALESCE((
                    SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                    FROM table_flota_aceite_historial ah 
                    WHERE ah.id_flota = f.id_flota 
                    ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC 
                    LIMIT 1
                ), 0) as proximo_cambio_aceite
            FROM table_flota f
            JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
            JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
            WHERE f.id_flota = ?
        ";
        return $this->select($query, [$idFlota]);
    }
    
    /**
     * =================================================================
     * HISTORIAL DE UNIDAD
     * =================================================================
     */
    
    /**
     * Get complete unit history (Hoja de Vida) for public view
     * Includes: despacho, aceite, mantenimiento, status
     * Based on FlotaModel::selectHistorialUnidad
     */
    public function selectHistorialUnidad(int $idFlota, array $postData, int $perPage) {
        $fechaInicio = !empty($postData['fechaInicio']) ? $postData['fechaInicio'] : null;
        $fechaFin = !empty($postData['fechaFin']) ? $postData['fechaFin'] : null;
        $filtroTipo = !empty($postData['filtroTipo']) ? $postData['filtroTipo'] : null;
        $filtroTermino = !empty($postData['filtroTermino']) ? strClean($postData['filtroTermino']) : null;
        
        $page = isset($postData['page']) ? intval($postData['page']) : 1;
        $offset = ($page - 1) * $perPage;
    
        $whereClauses = ["h.id_flota = ?"];
        $params = [$idFlota];
    
        if ($fechaInicio && $fechaFin) {
            $whereClauses[] = "DATE(h.fecha) BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        } elseif ($fechaInicio) {
            $whereClauses[] = "DATE(h.fecha) >= ?";
            $params[] = $fechaInicio;
        } elseif ($fechaFin) {
            $whereClauses[] = "DATE(h.fecha) <= ?";
            $params[] = $fechaFin;
        }
        if ($filtroTipo) {
            $whereClauses[] = "h.tipo = ?";
            $params[] = $filtroTipo;
        }
        if ($filtroTermino) {
            $whereClauses[] = "(h.id_evento LIKE ? OR h.detalles LIKE ?)";
            $params[] = "%" . $filtroTermino . "%";
            $params[] = "%" . $filtroTermino . "%";
        }
    
        $whereSql = "WHERE " . implode(" AND ", $whereClauses);
    
        $unionQuery = "
            (SELECT
                'despacho' as tipo,
                d.id_despacho as id_evento,
                d.id_flota,
                d.fecha_despacho as fecha,
                JSON_OBJECT('observacion', d.observacion) as detalles,
                d.user_id as usuario_id
            FROM table_alm_despacho d
            WHERE d.status_despacho = 1)
            
            UNION ALL
            
            (SELECT
                'aceite' as tipo,
                ah.id_aceite_historial as id_evento,
                ah.id_flota,
                ah.fecha_cambio as fecha,
                JSON_OBJECT('kilometraje_cambio', ah.kilometraje_cambio, 'kilometraje_anterior', ah.kilometraje_anterior, 'kilometraje_proximo_cambio', ah.kilometraje_proximo_cambio) as detalles,
                ah.usuario_id
            FROM table_flota_aceite_historial ah)
            
            UNION ALL
            
            (SELECT
                'mantenimiento' as tipo,
                m.id_unidad_mantenimiento as id_evento,
                m.id_flota,
                m.fecha_entrada as fecha,
                JSON_OBJECT('tipo_mantenimiento', m.tipo_mantenimiento, 'diagnostico', m.diagnostico) as detalles,
                m.usuario_id
            FROM table_flota_mantenimiento m)
            
            UNION ALL
            
            (SELECT
                'status' as tipo,
                s.idCambioStatus as id_evento,
                s.id_flota,
                s.fechaCambio as fecha,
                JSON_OBJECT(
                    'status_id', s.idstatus, 
                    'motivo', s.textCambio,
                    'status_texto', CASE s.idstatus 
                                        WHEN 1 THEN 'Activo' 
                                        WHEN 0 THEN 'Inactivo' 
                                        WHEN 2 THEN 'En Mantenimiento' 
                                        ELSE 'Desconocido' 
                                    END
                ) as detalles,
                s.usuario_id
            FROM table_flota_status s)
        ";
    
        $countSql = "SELECT COUNT(*) as total FROM ($unionQuery) as h $whereSql";
        $totalItems = $this->select($countSql, $params)['total'];

        $countTypeSql = "SELECT tipo, COUNT(*) as total FROM ($unionQuery) as h $whereSql GROUP BY tipo";
        $typeCounts = $this->select_all($countTypeSql, $params);
        $counts = ['despacho' => 0, 'mantenimiento' => 0, 'aceite' => 0, 'status' => 0];
        foreach ($typeCounts as $row) {
            $counts[$row['tipo']] = $row['total'];
        }
    
        $itemsSql = "SELECT h.*, 
                        COALESCE(CONCAT(p.personal_nombre, ' ', p.personal_apellido), d.departamento_nombre, 'Sistema') as usuario
                     FROM ($unionQuery) as h 
                     LEFT JOIN table_usuarios u ON h.usuario_id = u.usuario_id
                     LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                     LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
                     $whereSql ORDER BY h.fecha DESC, h.id_evento DESC LIMIT $perPage OFFSET $offset";
        $items = $this->select_all($itemsSql, $params);
    
        $despacho_ids = [];
        foreach ($items as $item) {
            if ($item['tipo'] === 'despacho') {
                $despacho_ids[] = $item['id_evento'];
            }
        }

        $articulos_por_despacho = [];
        if (!empty($despacho_ids)) {
            $placeholders = implode(',', array_fill(0, count($despacho_ids), '?'));
            $sqlArticulos = "SELECT rd.id_despacho, rd.cant_despacho, p.producto 
                             FROM table_alm_relacion_despacho rd
                             JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                             WHERE rd.id_despacho IN ($placeholders)";
            
            $todos_los_articulos = $this->select_all($sqlArticulos, $despacho_ids);

            foreach ($todos_los_articulos as $articulo) {
                $articulos_por_despacho[$articulo['id_despacho']][] = $articulo;
            }
        }

        foreach ($items as &$item) {
            if ($item['tipo'] === 'despacho') {
                $detalles = json_decode($item['detalles'], true);
                $detalles['articulos'] = $articulos_por_despacho[$item['id_evento']] ?? [];
                $item['detalles'] = json_encode($detalles);
            }

            $item['tipo_evento'] = $item['tipo'];
            $item['fecha_evento'] = $item['fecha'];
            $item['titulo'] = ucwords(str_replace('_', ' ', $item['tipo'])) . " #" . $item['id_evento'];
            $item['descripcion'] = $this->formatDescription($item);
        }
    
        return [
            'total_items' => $totalItems,
            'items' => $items,
            'counts' => $counts
        ];
    }
    
    private function formatDescription($item) {
        $detalles = json_decode($item['detalles'], true);
        switch ($item['tipo']) {
            case 'despacho':
                $articulos = !empty($detalles['articulos']) ? array_map(fn($art) => "<li>{$art['cant_despacho']}x {$art['producto']}</li>", $detalles['articulos']) : [];
                return 'Se despacharon los siguientes artículos: <ul>' . implode('', $articulos) . '</ul>';
            case 'aceite':
                return "Cambio de aceite registrado a los " . number_format($detalles['kilometraje_cambio']) . " Km.";
            case 'mantenimiento':
                $tipoMantenimiento = $detalles['tipo_mantenimiento'] === 'c' ? 'Correctivo' : 'Preventivo';
                return "<strong>Tipo:</strong> {$tipoMantenimiento}<br><strong>Diagnóstico:</strong> {$detalles['diagnostico']}";
            case 'status':
                return "La unidad cambió su estado a <strong>{$detalles['status_texto']}</strong>.<br><strong>Motivo:</strong> {$detalles['motivo']}";
            default:
                return 'Detalles no disponibles.';
        }
    }
    
    /**
     * Get order details (orden de despacho) for public view.
     * NO se filtra por institución porque es un detalle específico.
     * Las columnas operador, mecanico, despachador fueron eliminadas.
     * Se obtienen los nombres mediante JOINs con table_personal usando los IDs.
     * Usa CONCAT_WS para concatenar nombre y apellido saltando valores NULL.
     */
    public function getDetalleOrden($idDespacho) {
        $query = "
            SELECT 
                d.id_despacho,
                d.id_flota,
                f.id_unidad,
                d.operador_id,
                d.mecanico_id,
                d.despachador_id,
                CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido, '0')) AS operador_nombre,
                CONCAT_WS(' ', p_mec.personal_nombre, NULLIF(p_mec.personal_apellido, '0')) AS mecanico_nombre,
                CONCAT_WS(' ', p_desp.personal_nombre, NULLIF(p_desp.personal_apellido, '0')) AS despachador_nombre,
                d.fecha_despacho,
                d.observacion,
                d.estado_orden,
                d.status_despacho
            FROM table_alm_despacho d
            LEFT JOIN table_flota f ON d.id_flota = f.id_flota
            LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
            LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
            LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
            WHERE d.id_despacho = ?
        ";
        $orden = $this->select($query, [$idDespacho]);
        
        if (!$orden) {
            return null;
        }
        
        $queryProductos = "
            SELECT 
                rd.id_despacho,
                p.producto,
                p.present_producto,
                rd.cant_despacho
            FROM table_alm_relacion_despacho rd
            JOIN table_alm_producto p ON rd.id_producto = p.id_producto
            WHERE rd.id_despacho = ?
        ";
        $productos = $this->select_all($queryProductos, [$idDespacho]);
        $orden['productos'] = $productos;
        
        return $orden;
    }
    
    /**
     * =================================================================
     * ESTACIÓN - SIN FILTRO POR INSTITUCIÓN (NO MODIFICADO)
     * =================================================================
     */
    
    public function getVentasEstacionPublic($fechaInicio, $fechaFin, $estacionId = null) {
        $query = "
            SELECT 
                v.id_venta,
                v.id_user,
                v.id_tipo_pago,
                v.id_tipo_vehiculo,
                v.litros,
                v.monto,
                v.id_cierre_diario,
                v.fecha_venta,
                v.hora_venta,
                v.tasa_dia,
                v.id_rol,
                v.status_ticket,
                u.usuario_nick,
                tv.nombre as tipo_vehiculo,
                tp.nombre as tipo_pago,
                e.estacion as estacion_nombre
            FROM table_es_venta v
            LEFT JOIN table_usuarios u ON v.id_user = u.usuario_id
            LEFT JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
            LEFT JOIN table_es_tipos_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
            LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
            WHERE v.fecha_venta BETWEEN ? AND ?
        ";
        
        $params = [$fechaInicio, $fechaFin];
        
        if ($estacionId && $estacionId !== 'todas') {
            $query .= " AND u.usuario_estacion_id = ?";
            $params[] = $estacionId;
        }
        
        $query .= " ORDER BY v.fecha_venta DESC, v.hora_venta DESC";
        
        return $this->select_all($query, $params);
    }
    
    public function getEstacionesPublic() {
        $query = "
            SELECT 
                id_estacion,
                estacion
            FROM table_es_estacion
            WHERE status_estacion = 1
            ORDER BY estacion
        ";
        return $this->select_all($query);
    }
}
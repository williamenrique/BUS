<?php
class PublicoModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get despachos from almacén for public view
     */
    public function getDespachosPublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                d.id_despacho,
                d.id_flota,
                f.id_unidad,
                d.operador,
                d.mecanico,
                d.despachador,
                d.fecha_despacho,
                d.observacion,
                d.estado_orden,
                d.status_despacho
            FROM table_alm_despacho d
            LEFT JOIN table_flota f ON d.id_flota = f.id_flota
            WHERE d.fecha_despacho BETWEEN ? AND ?
            ORDER BY d.fecha_despacho DESC
        ";
        $despachos = $this->select_all($query, [$fechaInicio, $fechaFin]);
        
        // Get products for each despacho
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
            
            // Group products by despacho
            $productosByDespacho = [];
            foreach ($productos as $prod) {
                $productosByDespacho[$prod['id_despacho']][] = $prod;
            }
            
            // Add products to despachos
            foreach ($despachos as &$despacho) {
                $despacho['productos'] = $productosByDespacho[$despacho['id_despacho']] ?? [];
            }
        }
        
        return $despachos;
    }
    
    /**
     * Get ventas from estación for public view
     */
    public function getVentasPublic($fechaInicio, $fechaFin) {
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
                tp.nombre as tipo_pago
            FROM table_es_venta v
            LEFT JOIN table_usuarios u ON v.id_user = u.usuario_id
            LEFT JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
            LEFT JOIN table_es_tipos_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
            WHERE v.fecha_venta BETWEEN ? AND ?
            ORDER BY v.fecha_venta DESC, v.hora_venta DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get ventas from estación for public view with station filter
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
    
    /**
     * Get all stations for public view
     */
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
    
    /**
     * Get mantenimientos from flota for public view
     */
    public function getMantenimientosPublic($fechaInicio, $fechaFin) {
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
            ORDER BY m.fecha_entrada DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get compras/requisiciones for public view
     */
    public function getComprasPublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                r.id_requisicion,
                r.id_despacho_fk,
                r.id_flota,
                f.id_unidad,
                r.mecanico_cedula,
                r.tipo_orden,
                r.observacion,
                r.user_id_creador,
                r.fecha_creacion,
                r.status_requisicion,
                d.operador,
                d.despachador,
                d.estado_orden,
                u.usuario_nick as creador_nick
            FROM table_alm_requisicion r
            LEFT JOIN table_flota f ON r.id_flota = f.id_flota
            LEFT JOIN table_alm_despacho d ON r.id_despacho_fk = d.id_despacho
            LEFT JOIN table_usuarios u ON r.user_id_creador = u.usuario_id
            WHERE r.fecha_creacion BETWEEN ? AND ?
            ORDER BY r.fecha_creacion DESC
        ";
        $requisiciones = $this->select_all($query, [$fechaInicio, $fechaFin]);
        
        // Get products for each requisicion
        if (!empty($requisiciones)) {
            $ids = array_column($requisiciones, 'id_requisicion');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $queryProductos = "
                SELECT 
                    rd.id_requisicion_fk,
                    p.producto,
                    p.present_producto,
                    rd.cantidad_solicitada
                FROM table_alm_requisicion_detalle rd
                JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                WHERE rd.id_requisicion_fk IN ($placeholders)
            ";
            $productos = $this->select_all($queryProductos, $ids);
            
            // Group products by requisicion
            $productosByReq = [];
            foreach ($productos as $prod) {
                $productosByReq[$prod['id_requisicion_fk']][] = $prod;
            }
            
            // Add products to requisiciones
            foreach ($requisiciones as &$req) {
                $req['productos'] = $productosByReq[$req['id_requisicion']] ?? [];
            }
        }
        
        return $requisiciones;
    }
    
    /**
     * Get cambios de aceite for public view
     */
    public function getCambiosAceitePublic($fechaInicio, $fechaFin) {
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
            ORDER BY ah.fecha_cambio DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get kilometraje updates for public view
     */
    public function getKilometrajePublic($fechaInicio, $fechaFin) {
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
            ORDER BY k.fecha_actualizacion DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get summary statistics for dashboard
     */
    public function getResumenPublic($fechaInicio, $fechaFin) {
        $resumen = [];
        
        // Total despachos
        $query = "SELECT COUNT(*) as total FROM table_alm_despacho WHERE fecha_despacho BETWEEN ? AND ?";
        $resumen['despachos'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total ventas
        $query = "SELECT COUNT(*) as total FROM table_es_venta WHERE fecha_venta BETWEEN ? AND ?";
        $resumen['ventas'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total mantenimientos
        $query = "SELECT COUNT(*) as total FROM table_flota_mantenimiento WHERE fecha_entrada BETWEEN ? AND ?";
        $resumen['mantenimientos'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total requisiciones
        $query = "SELECT COUNT(*) as total FROM table_alm_requisicion WHERE fecha_creacion BETWEEN ? AND ?";
        $resumen['compras'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total cambios aceite
        $query = "SELECT COUNT(*) as total FROM table_flota_aceite_historial WHERE fecha_cambio BETWEEN ? AND ?";
        $resumen['aceite'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total kilometraje
        $query = "SELECT COUNT(*) as total FROM table_flota_kilometraje WHERE DATE(fecha_actualizacion) BETWEEN ? AND ?";
        $resumen['kilometraje'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        $resumen['total'] = array_sum($resumen);
        
        return $resumen;
    }
    
    /**
     * Get unit history (hoja de vida) for public view
     * Returns all movements for a specific unit: despachos, mantenimientos, cambios aceite, kilometraje
     */
    public function getHistorialUnidad($idFlota) {
        $historial = [];
        
        // 1. Despachos de Almacén
        $query = "
            SELECT 
                d.id_despacho,
                d.fecha_despacho as fecha,
                'Despacho Almacén' as tipo,
                CONCAT('DESP-', LPAD(d.id_despacho, 6, '0')) as referencia,
                d.operador,
                d.mecanico,
                d.despachador,
                d.observacion,
                d.estado_orden as estado,
                NULL as kilometraje
            FROM table_alm_despacho d
            WHERE d.id_flota = ?
            ORDER BY d.fecha_despacho DESC
        ";
        $despachos = $this->select_all($query, [$idFlota]);
        foreach ($despachos as $d) {
            $historial[] = $d;
        }
        
        // 2. Mantenimientos de Flota
        $query = "
            SELECT 
                m.id_unidad_mantenimiento,
                m.fecha_entrada as fecha,
                'Mantenimiento Flota' as tipo,
                CONCAT('MANT-', LPAD(m.id_unidad_mantenimiento, 6, '0')) as referencia,
                m.operardor_unidad as operador,
                m.nomb_mecanico as mecanico,
                '-' as despachador,
                m.diagnostico as observacion,
                m.status_mantenimiento as estado,
                m.km_unidad as kilometraje
            FROM table_flota_mantenimiento m
            WHERE m.id_flota = ?
            ORDER BY m.fecha_entrada DESC
        ";
        $mantenimientos = $this->select_all($query, [$idFlota]);
        foreach ($mantenimientos as $m) {
            $historial[] = $m;
        }
        
        // 3. Cambios de Aceite
        $query = "
            SELECT 
                ah.id_aceite_historial,
                ah.fecha_cambio as fecha,
                'Cambio Aceite' as tipo,
                CONCAT('ACE-', LPAD(ah.id_aceite_historial, 6, '0')) as referencia,
                '-' as operador,
                u.usuario_nick as mecanico,
                '-' as despachador,
                CONCAT('KM: ', FORMAT(ah.kilometraje_cambio, 0), ' | Próx: ', FORMAT(ah.kilometraje_proximo_cambio, 0)) as observacion,
                'Completado' as estado,
                ah.kilometraje_cambio as kilometraje
            FROM table_flota_aceite_historial ah
            LEFT JOIN table_usuarios u ON ah.usuario_id = u.usuario_id
            WHERE ah.id_flota = ?
            ORDER BY ah.fecha_cambio DESC
        ";
        $aceites = $this->select_all($query, [$idFlota]);
        foreach ($aceites as $a) {
            $historial[] = $a;
        }
        
        // 4. Actualizaciones de Kilometraje
        $query = "
            SELECT 
                k.id_kilometraje,
                k.fecha_actualizacion as fecha,
                'Actualización KM' as tipo,
                CONCAT('KM-', LPAD(k.id_kilometraje, 6, '0')) as referencia,
                '-' as operador,
                u.usuario_nick as mecanico,
                '-' as despachador,
                CONCAT('Kilometraje: ', FORMAT(k.kilometraje_actual, 0)) as observacion,
                'Registrado' as estado,
                k.kilometraje_actual as kilometraje
            FROM table_flota_kilometraje k
            LEFT JOIN table_usuarios u ON k.usuario_id = u.usuario_id
            WHERE k.id_flota = ?
            ORDER BY k.fecha_actualizacion DESC
        ";
        $kilometrajes = $this->select_all($query, [$idFlota]);
        foreach ($kilometrajes as $k) {
            $historial[] = $k;
        }
        
        // Sort all by date descending
        usort($historial, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        return $historial;
    }
    
    /**
     * Get unit info for public view
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
     * Get complete unit history (Hoja de Vida) for public view
     * Includes: despacho, aceite, mantenimiento, status
     * Based on FlotaModel::selectHistorialUnidad
     */
    public function selectHistorialUnidad(int $idFlota, array $postData, int $perPage) {
        // --- PARÁMETROS DE FILTRADO Y PAGINACIÓN ---
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
            // Búsqueda en ID, diagnóstico, motivo, etc.
            $whereClauses[] = "(h.id_evento LIKE ? OR h.detalles LIKE ?)";
            $params[] = "%" . $filtroTermino . "%";
            $params[] = "%" . $filtroTermino . "%";
        }
    
        $whereSql = "WHERE " . implode(" AND ", $whereClauses);
    
        // --- CONSTRUCCIÓN DE LA CONSULTA UNIFICADA (VISTA TEMPORAL) ---
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
    
        // --- CONSULTA PARA CONTAR EL TOTAL DE ITEMS FILTRADOS ---
        $countSql = "SELECT COUNT(*) as total FROM ($unionQuery) as h $whereSql";
        $totalItems = $this->select($countSql, $params)['total'];

        // --- NUEVO: CONSULTA PARA CONTAR POR TIPO (DESGLOSE) ---
        $countTypeSql = "SELECT tipo, COUNT(*) as total FROM ($unionQuery) as h $whereSql GROUP BY tipo";
        $typeCounts = $this->select_all($countTypeSql, $params);
        $counts = ['despacho' => 0, 'mantenimiento' => 0, 'aceite' => 0, 'status' => 0];
        foreach ($typeCounts as $row) {
            $counts[$row['tipo']] = $row['total'];
        }
    
        // --- CONSULTA PARA OBTENER LOS ITEMS DE LA PÁGINA ACTUAL ---
        $itemsSql = "SELECT h.*, 
                        -- Se une directamente a usuarios y luego a personal para obtener el nombre correcto
                        -- COALESCE se usa como fallback por si un usuario no tiene personal asignado
                        COALESCE(CONCAT(p.personal_nombre, ' ', p.personal_apellido), d.departamento_nombre, 'Sistema') as usuario
                     FROM ($unionQuery) as h 
                     LEFT JOIN table_usuarios u ON h.usuario_id = u.usuario_id
                     LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                     LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
                     $whereSql ORDER BY h.fecha DESC, h.id_evento DESC LIMIT $perPage OFFSET $offset";
        $items = $this->select_all($itemsSql, $params);
    
        // --- ENRIQUECER LOS DETALLES DE DESPACHO CON SUS ARTÍCULOS ---
        // 1. Extraer los IDs de los eventos de tipo 'despacho'
        $despacho_ids = [];
        foreach ($items as $item) {
            if ($item['tipo'] === 'despacho') {
                $despacho_ids[] = $item['id_evento'];
            }
        }

        // 2. Si hay despachos, obtener todos sus artículos en una sola consulta eficiente
        $articulos_por_despacho = [];
        if (!empty($despacho_ids)) {
            $placeholders = implode(',', array_fill(0, count($despacho_ids), '?'));
            $sqlArticulos = "SELECT rd.id_despacho, rd.cant_despacho, p.producto 
                             FROM table_alm_relacion_despacho rd
                             JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                             WHERE rd.id_despacho IN ($placeholders)";
            
            $todos_los_articulos = $this->select_all($sqlArticulos, $despacho_ids);

            // 3. Agrupar los artículos por su id_despacho
            foreach ($todos_los_articulos as $articulo) {
                $articulos_por_despacho[$articulo['id_despacho']][] = $articulo;
            }
        }

        foreach ($items as &$item) {
            if ($item['tipo'] === 'despacho') {
                // Decodificar JSON, agregar artículos y volver a codificar
                $detalles = json_decode($item['detalles'], true);
                $detalles['articulos'] = $articulos_por_despacho[$item['id_evento']] ?? [];
                $item['detalles'] = json_encode($detalles);
            }

            // Renombrar campos para el frontend
            $item['tipo_evento'] = $item['tipo'];
            $item['fecha_evento'] = $item['fecha'];
            $item['titulo'] = ucwords(str_replace('_', ' ', $item['tipo'])) . " #" . $item['id_evento'];
            $item['descripcion'] = $this->formatDescription($item);
        }
    
        return [
            'total_items' => $totalItems,
            'items' => $items,
            'counts' => $counts // Retornamos el desglose
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
     * Get order details (orden de despacho) for public view
     */
    public function getDetalleOrden($idDespacho) {
        $query = "
            SELECT 
                d.id_despacho,
                d.id_flota,
                f.id_unidad,
                d.operador,
                d.mecanico,
                d.despachador,
                d.fecha_despacho,
                d.observacion,
                d.estado_orden,
                d.status_despacho
            FROM table_alm_despacho d
            LEFT JOIN table_flota f ON d.id_flota = f.id_flota
            WHERE d.id_despacho = ?
        ";
        $orden = $this->select($query, [$idDespacho]);
        
        if (!$orden) {
            return null;
        }
        
        // Get products for this despacho
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
     * Get detalle de un cambio de aceite específico
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
     * Get detalle de un mantenimiento específico
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
     * Get detalle de una actualización de kilometraje específica
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
}
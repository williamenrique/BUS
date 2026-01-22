<?php
class FlotaModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene los datos para los selects del formulario.
     * @return array
     */
    public function getSelectsData(): array {
        $sql_marcas = "SELECT id_marca, marca_unidad FROM table_flota_marca ORDER BY marca_unidad ASC";
        $sql_modelos = "SELECT id_modelo, modelo_unidad FROM table_flota_modelo ORDER BY modelo_unidad ASC";
        
        $data['marcas'] = $this->select_all($sql_marcas);
        $data['modelos'] = $this->select_all($sql_modelos);
        
        return $data;
    }

    /**
     * Obtiene todas las unidades de la flota para la DataTable.
     * @return array
     */
    public function selectFlota(): array {
        $sql = "SELECT 
                    f.id_flota,
                    f.id_unidad,
                    f.vim_unidad,
                    f.status_unidad,
                    f.tipo_combustible,
                    f.transmision,
                    m.marca_unidad,
                    mo.modelo_unidad
                FROM table_flota f
                INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                ORDER BY f.id_flota DESC";
        return $this->select_all($sql);
    }

    /**
     * Obtiene los datos de una unidad específica.
     * @param int $idFlota
     * @return array|false
     */
    public function selectUnidad(int $idFlota) {
        $sql = "SELECT 
                    f.*, 
                    m.marca_unidad, 
                    mo.modelo_unidad,
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km WHERE km.id_flota = f.id_flota ORDER BY km.fecha_actualizacion DESC LIMIT 1), 0) as kilometraje_actual
                FROM table_flota f
                INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE f.id_flota = ?";
        return $this->select($sql, [$idFlota]);
    }

    /**
     * Obtiene el historial de mantenimiento de una unidad.
     * @param int $idFlota
     * @return array
     */
    public function selectMantenimientoHistory(int $idFlota): array {
        $sql = "SELECT * FROM table_flota_mantenimiento WHERE id_flota = ? ORDER BY fecha_entrada DESC";
        return $this->select_all($sql, [$idFlota]);
    }

    /**
     * Inserta una nueva unidad en la base de datos.
     * @param array $data
     * @return int|string
     */
    public function insertFlota(array $data) {
        $sql_check = "SELECT id_flota FROM table_flota WHERE id_unidad = ? OR vim_unidad = ?";
        $request_check = $this->select($sql_check, [$data['id_unidad'], $data['vim_unidad']]);

        if (!empty($request_check)) {
            return "exist";
        }

        $sql = "INSERT INTO table_flota (id_unidad, id_marca, id_modelo, vim_unidad, fecha_creacion, cap_pasajero, tipo_combustible, transmision, status_unidad) VALUES (?,?,?,?,?,?,?,?,?)";
        $arrData = [
            $data['id_unidad'], $data['id_marca'], $data['id_modelo'], $data['vim_unidad'],
            $data['fecha_creacion'], $data['cap_pasajero'], $data['tipo_combustible'],
            $data['transmision'], $data['status_unidad']
        ];
        return $this->insert($sql, $arrData);
    }

    /**
     * Actualiza una unidad existente.
     * @param array $data
     * @return bool
     */
    public function updateFlota(array $data): bool {
        $sql = "UPDATE table_flota SET id_unidad = ?, id_marca = ?, id_modelo = ?, vim_unidad = ?, fecha_creacion = ?, cap_pasajero = ?, tipo_combustible = ?, transmision = ? WHERE id_flota = ?";
        $arrData = [
            $data['id_unidad'], $data['id_marca'], $data['id_modelo'], $data['vim_unidad'],
            $data['fecha_creacion'], $data['cap_pasajero'], $data['tipo_combustible'],
            $data['transmision'], $data['id_flota']
        ];
        return $this->update($sql, $arrData);
    }

    /**
     * Actualiza el estado de una unidad y registra el cambio en el historial.
     * @param int $idFlota
     * @param int $status
     * @param string $motivo
     * @param int $userId
     * @return bool
     */
    public function updateStatusUnidad(int $idFlota, int $status, string $motivo, int $userId): bool {
        $sql_update = "UPDATE table_flota SET status_unidad = ? WHERE id_flota = ?";
        $this->update($sql_update, [$status, $idFlota]);

        $sql_history = "INSERT INTO table_flota_status (id_flota, idstatus, textCambio, fechaCambio, usuario_id) VALUES (?, ?, ?, NOW(), ?)";
        return $this->insert($sql_history, [$idFlota, $status, $motivo, $userId]);
    }

    // MÉTODOS PARA EL MÓDULO DE CAMBIO DE ACEITE

    /**
     * Obtiene el estado del cambio de aceite de todas las unidades.
     * Une la información de la flota, el último kilometraje y el último cambio de aceite.
     * @return array
     */
    public function selectAceiteStatus(): array {
        $sql = "SELECT 
                    f.id_flota,
                    f.id_unidad,
                    m.marca_unidad,
                    mo.modelo_unidad,
                    (SELECT km.kilometraje_actual 
                     FROM table_flota_kilometraje km 
                     WHERE km.id_flota = f.id_flota 
                     ORDER BY km.fecha_actualizacion DESC 
                     LIMIT 1) as kilometraje_actual,
                    (SELECT ah.kilometraje_cambio 
                     FROM table_flota_aceite_historial ah 
                     WHERE ah.id_flota = f.id_flota 
                     ORDER BY ah.fecha_cambio DESC 
                     LIMIT 1) as ultimo_cambio_km,
                    (SELECT ah.fecha_cambio 
                     FROM table_flota_aceite_historial ah 
                     WHERE ah.id_flota = f.id_flota 
                     ORDER BY ah.fecha_cambio DESC 
                     LIMIT 1) as fecha_ultimo_cambio
                FROM table_flota f
                LEFT JOIN table_flota_marca m ON f.id_marca = m.id_marca
                LEFT JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE f.status_unidad = 1
                ORDER BY f.id_unidad ASC";
        return $this->select_all($sql);
    }

    /**
     * Inserta o actualiza el kilometraje de una unidad.
     * @param int $idFlota
     * @param int $kilometraje
     * @param int $userId
     * @return bool
     */
    public function updateKilometraje(int $idFlota, int $kilometraje, int $userId): bool {
        // Insertamos el nuevo valor de kilometraje.
        $sql = "INSERT INTO table_flota_kilometraje (id_flota, kilometraje_actual, usuario_id, fecha_actualizacion) VALUES (?, ?, ?, NOW())";
        $request = $this->insert($sql, [$idFlota, $kilometraje, $userId]);
        return $request > 0;
    }

    /**
     * Registra un nuevo cambio de aceite en el historial.
     * @param int $idFlota
     * @param int $kilometrajeCambio
     * @param int $kilometrajeAnterior
     * @param int $userId
     * @param string $fechaCambio
     * @return bool
     */
    public function insertCambioAceite(int $idFlota, int $kilometrajeCambio, int $kilometrajeAnterior, int $userId, string $fechaCambio): bool {
        $sql = "INSERT INTO table_flota_aceite_historial 
                    (id_flota, fecha_cambio, kilometraje_cambio, kilometraje_anterior, usuario_id) 
                VALUES (?, ?, ?, ?, ?)";
        
        $request = $this->insert($sql, [$idFlota, $fechaCambio, $kilometrajeCambio, $kilometrajeAnterior, $userId]);
        
        // También actualizamos el kilometraje actual de la unidad para que coincida
        if ($request > 0) {
            $this->updateKilometraje($idFlota, $kilometrajeCambio, $userId);
        }

        return $request > 0;
    }

    /**
     * Elimina el último registro de kilometraje para una unidad.
     * @param int $idFlota
     * @return bool
     */
    public function deleteLatestKilometraje(int $idFlota): bool {
        // Seleccionar el ID del registro más reciente
        $sql_select_id = "SELECT id_kilometraje FROM table_flota_kilometraje WHERE id_flota = ? ORDER BY fecha_actualizacion DESC LIMIT 1";
        $latest_id = $this->select($sql_select_id, [$idFlota]);
        if (empty($latest_id)) return false; // No hay registros para eliminar

        $sql_delete = "DELETE FROM table_flota_kilometraje WHERE id_kilometraje = ?";
        return $this->delete($sql_delete, [$latest_id['id_kilometraje']]);
    }

    /**
     * Elimina el último registro de cambio de aceite para una unidad.
     * @param int $idFlota
     * @return bool
     */
    public function deleteLatestAceite(int $idFlota): bool {
        $sql_delete = "DELETE FROM table_flota_aceite_historial WHERE id_flota = ? ORDER BY fecha_cambio DESC LIMIT 1";
        return $this->delete($sql_delete, [$idFlota]);
    }
    // =================================================================================
    // MÉTODOS PARA EL REPORTE DE OPERATIVIDAD
    // =================================================================================

    /**
     * Obtiene los datos detallados de una lista de unidades por sus IDs para el reporte.
     * @param array $ids Array de IDs de las unidades de la flota.
     * @return array Datos detallados de las unidades.
     */
    public function selectUnidadesParaReporte(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $sql = "SELECT 
                    f.id_flota,
                    f.id_unidad,
                    f.vim_unidad,
                    f.fecha_creacion,
                    m.marca_unidad AS nombre_marca,
                    mo.modelo_unidad AS nombre_modelo,
                    (SELECT s.nombre_status 
                     FROM table_flota_status_list s
                     JOIN table_flota_status hs ON s.id_status = hs.idstatus
                     WHERE hs.id_flota = f.id_flota 
                     ORDER BY hs.fechaCambio DESC 
                     LIMIT 1) AS status_actual,
                    (SELECT hs.textCambio 
                     FROM table_flota_status hs 
                     WHERE hs.id_flota = f.id_flota 
                     ORDER BY hs.fechaCambio DESC 
                     LIMIT 1) AS ultimo_motivo
                FROM table_flota f
                INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE f.id_flota IN ($placeholders)";

        return $this->select_all($sql, $types, $ids);
    }

    // aqui consultas historial unidad
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
    
}
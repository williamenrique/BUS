<?php

/*
 * --------------------------------------------------------------------------------
 * ESTRUCTURA DE BASE DE DATOS PARA EL MÓDULO DE COMPRAS
 * --------------------------------------------------------------------------------
 * 
 * -- Tabla para registrar los despachos que están pendientes de costeo.
 * -- Se llena automáticamente cuando Almacén realiza un despacho.
 * 
 * CREATE TABLE `table_compras_pendientes` (
 *   `id_compra_pendiente` int(11) NOT NULL AUTO_INCREMENT,
 *   `id_despacho` int(11) NOT NULL,
 *   `id_producto` int(11) NOT NULL,
 *   `id_flota` int(11) NOT NULL,
 *   `cant_despacho` float NOT NULL,
 *   `fecha_despacho` date NOT NULL,
 *   `status_costeo` enum('Pendiente','Costeado','Anulado') COLLATE utf8mb4_spanish_ci NOT NULL DEFAULT 'Pendiente',
 *   PRIMARY KEY (`id_compra_pendiente`),
 *   KEY `fk_pendiente_despacho` (`id_despacho`),
 *   KEY `fk_pendiente_producto` (`id_producto`),
 *   KEY `fk_pendiente_flota` (`id_flota`),
 *   CONSTRAINT `fk_pendiente_despacho` FOREIGN KEY (`id_despacho`) REFERENCES `table_alm_despacho` (`id_despacho`) ON DELETE CASCADE,
 *   CONSTRAINT `fk_pendiente_producto` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto` (`id_producto`),
 *   CONSTRAINT `fk_pendiente_flota` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci; 
 * 
 * 
 * -- Tabla para almacenar los costos asociados a cada artículo despachado.
 * -- Se llena cuando el personal de Compras asigna un costo a un ítem pendiente.
 * 
 * CREATE TABLE `table_compras_costos` (
 *   `id_costo` int(11) NOT NULL AUTO_INCREMENT,
 *   `id_compra_pendiente` int(11) NOT NULL,
 *   `tasa_dia` decimal(10,2) NOT NULL,
 *   `monto_divisa` decimal(10,2) NOT NULL,
 *   `monto_bs` decimal(10,2) NOT NULL,
 *   `fecha_costeo` date NOT NULL,
 *   `id_usuario_costeo` int(11) NOT NULL,
 *   PRIMARY KEY (`id_costo`),
 *   KEY `fk_costo_pendiente` (`id_compra_pendiente`),
 *   KEY `fk_costo_usuario` (`id_usuario_costeo`),
 *   CONSTRAINT `fk_costo_pendiente` FOREIGN KEY (`id_compra_pendiente`) REFERENCES `table_compras_pendientes` (`id_compra_pendiente`) ON DELETE CASCADE,
 *   CONSTRAINT `fk_costo_usuario` FOREIGN KEY (`id_usuario_costeo`) REFERENCES `table_usuarios` (`usuario_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
 * 
 * --------------------------------------------------------------------------------
 * DESCRIPCIÓN DE LOS MÉTODOS DEL MODELO
 * --------------------------------------------------------------------------------
 * 
 */

class ComprasModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }

    public function selectComprasPendientes() {
        /**
         * Selecciona los despachos pendientes de costeo.
         * @return array - Lista de despachos pendientes.
         */
        $sql = "SELECT
                    cp.id_despacho,
                    DATE_FORMAT(STR_TO_DATE(d.fecha_despacho, '%Y-%m-%d'), '%d-%m-%Y') as fecha_despacho,
                    f.id_unidad,
                    fm.modelo_unidad,
                    COUNT(cp.id_compra_pendiente) as articulos_pendientes
                FROM table_compras_pendientes cp
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON cp.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE cp.status_costeo = 'Pendiente'
                GROUP BY cp.id_despacho, d.fecha_despacho, f.id_unidad, fm.modelo_unidad
                ORDER BY d.fecha_despacho DESC, cp.id_despacho DESC";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectArticulosPorDespacho(int $idDespacho) {
        /**
         * Selecciona los artículos de un despacho específico que están pendientes de costeo.
         * @param int $idDespacho - El ID del despacho.
         * @return array - Lista de artículos pendientes para ese despacho.
         */
        $sql = "SELECT 
                    cp.id_compra_pendiente,
                    p.producto,
                    cp.cant_despacho
                FROM table_compras_pendientes cp
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                WHERE cp.id_despacho = ? AND cp.status_costeo = 'Pendiente'";
        
        $request = $this->select_all($sql, [$idDespacho]);
        return $request;
    }

    public function insertCostosPorDespacho(array $articulos, float $tasa, int $idUsuario) {
        /**
         * Inserta los costos asociados a los artículos de un despacho y actualiza el estado de los artículos a 'Costeado'.
         * @param array $articulos - Un array de artículos con sus respectivos montos.
         * @param float $tasa - La tasa de cambio utilizada para el costeo.
         * @param int $idUsuario - El ID del usuario que realiza el costeo.
         * @return int - El número de registros procesados.
         */
        try {
            $registros_procesados = 0;
            foreach ($articulos as $articulo) {
                $idCompraPendiente = intval($articulo['id']);
                $montoDivisa = floatval($articulo['monto']);
                $montoBs = $montoDivisa * $tasa;

                // 1. Insertar el costo en la tabla de costos
                $sql_costo = "INSERT INTO table_compras_costos (id_compra_pendiente, tasa_dia, monto_divisa, monto_bs, fecha_costeo, id_usuario_costeo) VALUES (?, ?, ?, ?, CURDATE(), ?)";
                $this->insert($sql_costo, [$idCompraPendiente, $tasa, $montoDivisa, $montoBs, $idUsuario]);

                // 2. Actualizar el estado en la tabla de pendientes
                $sql_update = "UPDATE table_compras_pendientes SET status_costeo = 'Costeado' WHERE id_compra_pendiente = ?";
                $this->update($sql_update, [$idCompraPendiente]);
                $registros_procesados++;
            }
            return $registros_procesados;
        } catch (Exception $e) {
            // Opcional: registrar el error $e->getMessage()
            return 0;
        }
    }

    /**
     * Migra las órdenes de despacho antiguas que no tienen un registro en la tabla de compras pendientes.
     * Este método es para ser ejecutado una sola vez.
     */
    public function migrarOrdenesAntiguas() {
        /**
         * Migra las órdenes de despacho antiguas a la tabla de compras pendientes.
         * @return int - El número de registros migrados.
         */
        // 1. Seleccionar todos los artículos de despachos que NO están en la tabla de pendientes.
        // Usamos un LEFT JOIN para encontrar los que no tienen correspondencia.
        $sql_select = "SELECT 
                            rd.id_despacho,
                            rd.id_producto,
                            rd.cant_despacho,
                            d.id_flota,
                            d.fecha_despacho as fecha_despacho_original
                       FROM table_alm_relacion_despacho rd
                       INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                       LEFT JOIN table_compras_pendientes cp ON rd.id_despacho = cp.id_despacho AND rd.id_producto = cp.id_producto
                       WHERE cp.id_compra_pendiente IS NULL AND d.status_despacho = 1";
        
        $ordenes_a_migrar = $this->select_all($sql_select);

        if (empty($ordenes_a_migrar)) {
            return 0; // No hay nada que migrar
        }

        $registros_insertados = 0;
        try {
            $sql_insert = "INSERT INTO table_compras_pendientes (id_despacho, id_producto, id_flota, cant_despacho, fecha_despacho, status_costeo) VALUES (?, ?, ?, ?, ?, 'Pendiente')";
            
            foreach ($ordenes_a_migrar as $orden) {
                // Asegurarse de que los datos no son nulos antes de insertar
                if (!empty($orden['id_despacho']) && !empty($orden['id_producto']) && !empty($orden['id_flota']) && !empty($orden['fecha_despacho_original'])) {
                    $this->insert($sql_insert, [$orden['id_despacho'], $orden['id_producto'], $orden['id_flota'], (float)$orden['cant_despacho'], $orden['fecha_despacho_original']]);
                    $registros_insertados++;
                }
            }
            return $registros_insertados;
        } catch (Exception $e) {
            throw $e; // Propagar la excepción para que el controlador la maneje
        }
    }

    /**
     * MÉTODO DE DEPURACIÓN TEMPORAL
     * Ejecuta solo la consulta SELECT de la migración para ver qué encuentra.
     */
    public function debugMigracionSelect() {
        $sql_select = "SELECT 
        /**
         * Depura la migración de órdenes antiguas (solo para desarrollo).
         * @return array - Resultados de la consulta de selección.
         */
                            rd.id_despacho,
                            rd.id_producto,
                            d.id_flota,
                            d.status_despacho,
                            cp.id_compra_pendiente
                       FROM table_alm_relacion_despacho rd
                       INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                       LEFT JOIN table_compras_pendientes cp ON rd.id_despacho = cp.id_despacho AND rd.id_producto = cp.id_producto";
        
        $resultados = $this->select_all($sql_select);
        return $resultados;
    }

    public function getInfoDespacho(int $idDespacho) {
        /**
         * Obtiene la información básica de un despacho.
         * @param int $idDespacho - El ID del despacho.
         * @return array - Información del despacho.
         */
        $sql = "SELECT 
                    d.id_despacho,
                    DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                    f.id_unidad,
                    fm.modelo_unidad
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE d.id_despacho = ?";
        
        $request = $this->select($sql, [$idDespacho]);
        return $request;
    }

    public function selectComprasCosteadas(int $start, int $length, string $searchValue, ?string $fechaInicio, ?string $fechaFin) {
        /**
         * Selecciona las compras costeadas con paginación y filtros.
         * @param int $start - Inicio del rango de registros.
         * @param int $length - Cantidad de registros por página.
         * @param string $searchValue - Término de búsqueda.
         * @param string|null $fechaInicio - Fecha de inicio para el filtro.
         * @param string|null $fechaFin - Fecha de fin para el filtro.
         */
        $sqlBase = "FROM table_compras_costos cc
                    INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                    INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                    INNER JOIN table_flota f ON d.id_flota = f.id_flota
                    INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo";

        $params = [];
        $sqlWhere = " WHERE cp.status_costeo = 'Costeado'";

        if ($fechaInicio && $fechaFin) {
            $sqlWhere .= " AND d.fecha_despacho BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        if (!empty($searchValue)) {
            $sqlWhere .= " AND (d.id_despacho LIKE ? OR f.id_unidad LIKE ? OR fm.modelo_unidad LIKE ?)";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
        }

        // Conteo total de registros filtrados
        $sqlFiltered = "SELECT COUNT(DISTINCT d.id_despacho) as total " . $sqlBase . $sqlWhere;
        $totalFiltered = $this->select($sqlFiltered, $params)['total'];

        // Conteo total de registros sin filtrar (solo con el estado)
        // Se unifica la consulta de conteo total para que sea consistente con la consulta de datos.
        // Esto asegura que el "recordsTotal" de DataTables sea correcto.
        $sqlTotal = "SELECT COUNT(DISTINCT d.id_despacho) as total " . $sqlBase . " WHERE cp.status_costeo = 'Costeado'";
        $totalRecords = $this->select($sqlTotal)['total'] ?? 0;


        // Consulta principal con ordenamiento y paginación
        $sqlData = "SELECT 
                    d.id_despacho,
                    DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                    f.id_unidad,
                    fm.modelo_unidad,
                    COUNT(cc.id_costo) as articulos_costeados,
                    SUM(cc.monto_divisa) as total_divisa,
                    SUM(cc.monto_bs) as total_bs "
                    . $sqlBase . $sqlWhere .
                    " GROUP BY d.id_despacho, d.fecha_despacho, f.id_unidad, fm.modelo_unidad
                      ORDER BY d.fecha_despacho DESC, d.id_despacho DESC";
        $data = $this->select_all($sqlData, $params);

        return ["data" => $data, "total" => $totalRecords, "total_filtered" => $totalFiltered];
    }

    public function selectReporteCosteadasFiltrado(string $searchValue, ?string $fechaInicio, ?string $fechaFin) {
        /**
         * Selecciona las compras costeadas filtradas para generar un reporte.
         * @param string $searchValue - Término de búsqueda.
         * @param string|null $fechaInicio - Fecha de inicio para el filtro.
         * @param string|null $fechaFin - Fecha de fin para el filtro.
         * @return array - Lista de compras costeadas filtradas.
         */
        $sqlBase = "FROM table_compras_costos cc
                    INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                    INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                    INNER JOIN table_flota f ON d.id_flota = f.id_flota
                    INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                    INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto";

        $params = [];
        $sqlWhere = " WHERE cp.status_costeo = 'Costeado'";

        if ($fechaInicio && $fechaFin) {
            $sqlWhere .= " AND d.fecha_despacho BETWEEN ? AND ?"; // Se mantiene, ya estaba correcto.
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        if (!empty($searchValue)) {
            $sqlWhere .= " AND (cp.id_despacho LIKE ? OR f.id_unidad LIKE ? OR fm.modelo_unidad LIKE ? OR p.producto LIKE ?)";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
        }

        $sqlData = "SELECT 
                    cp.id_despacho,
                    DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                    f.id_flota,
                    f.id_unidad,
                    fm.modelo_unidad,
                    p.producto,
                    cp.cant_despacho,
                    cc.tasa_dia,
                    cc.monto_divisa,
                    cc.monto_bs "
                    . $sqlBase . $sqlWhere .
                    " ORDER BY f.id_unidad ASC, d.fecha_despacho ASC, cp.id_despacho ASC, p.producto ASC";
        return $this->select_all($sqlData, $params);
    }

    public function selectDetalleCosteado(int $idDespacho) {
        /**
         * Selecciona el detalle de un despacho costeado.
         * @param int $idDespacho - El ID del despacho.
         * @return array - Detalle del despacho costeado.
         */
        $sql = "SELECT 
                    p.producto,
                    cp.cant_despacho,
                    cc.tasa_dia,
                    cc.monto_divisa,
                    cc.monto_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                WHERE cp.id_despacho = ? AND cp.status_costeo = 'Costeado'";
        
        $request = $this->select_all($sql, [$idDespacho]);
        return $request;
    }

    public function selectFlotaActiva() {
        /**
         * Selecciona las unidades de flota activas.
         * @return array - Lista de unidades de flota activas.
         */
        $sql = "SELECT id_flota, id_unidad, modelo_unidad 
                FROM table_flota f
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE f.status_unidad = 1 
                ORDER BY f.id_unidad ASC";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectReporteCosteado(int $idFlota, string $fechaInicio, string $fechaFin) {
        /**
         * Selecciona los datos para generar el reporte costeado.
         * @param int $idFlota - El ID de la flota.
         * @param string $fechaInicio - Fecha de inicio para el filtro.
         * @param string $fechaFin - Fecha de fin para el filtro.
         * @return array - Lista de datos para el reporte costeado.
         */
        $sql = "SELECT 
                    cp.id_despacho,
                    DATE_FORMAT(STR_TO_DATE(cp.fecha_despacho, '%Y-%m-%d'), '%d-%m-%Y') as fecha_despacho,
                    f.id_unidad,
                    fm.modelo_unidad,
                    p.producto,
                    cp.cant_despacho,
                    cc.tasa_dia,
                    cc.monto_divisa,
                    cc.monto_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                WHERE cp.id_flota = ?
                  AND STR_TO_DATE(cp.fecha_despacho, '%Y-%m-%d') BETWEEN ? AND ?
                  AND cp.status_costeo = 'Costeado'
                ORDER BY cp.fecha_despacho ASC, cp.id_despacho ASC";
        
        $request = $this->select_all($sql, [$idFlota, $fechaInicio, $fechaFin]);
        return $request;
    }

    public function countOrdenesCosteadas(int $idFlota, ?string $fechaInicio = null, ?string $fechaFin = null) {
        /**
         * Cuenta las órdenes costeadas.
         * @param int $idFlota - El ID de la flota.
         * @param string|null $fechaInicio - Fecha de inicio para el filtro.
         * @param string|null $fechaFin - Fecha de fin para el filtro.
         * @return int - Cantidad de órdenes costeadas.
         */
        $sql = "SELECT COUNT(DISTINCT d.id_despacho) as total
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                WHERE cp.id_flota = ? AND cp.status_costeo = 'Costeado'";
        
        $params = [$idFlota];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND STR_TO_DATE(cp.fecha_despacho, '%Y-%m-%d') BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        $request = $this->select($sql, $params);
        return $request['total'] ?? 0;
    }

    public function selectComprasDiarias(string $fechaInicio, string $fechaFin) {
        /**
         * Selecciona las compras diarias.
         * @param string $fechaInicio - Fecha de inicio para el filtro.
         * @param string $fechaFin - Fecha de fin para el filtro.
         * @return array - Lista de compras diarias.
         */
        $sql = "SELECT 
                    cp.id_despacho,
                    DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                    f.id_flota,
                    f.id_unidad,
                    fm.modelo_unidad,
                    p.producto,
                    cp.cant_despacho,
                    cc.tasa_dia,
                    cc.monto_divisa,
                    cc.monto_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                WHERE d.fecha_despacho BETWEEN ? AND ?
                  AND cp.status_costeo = 'Costeado'
                ORDER BY f.id_unidad ASC, d.fecha_despacho ASC, cp.id_despacho ASC";
        $request = $this->select_all($sql, [$fechaInicio, $fechaFin]);
        return $request;
    }

    /**
     * Anula el costeo de un despacho completo.
     * Elimina los registros de la tabla de costos y revierte el estado en la tabla de pendientes.
     * @param int $idDespacho El ID del despacho a anular.
     * @return bool True si la operación fue exitosa, false en caso contrario.
     */
    public function anularCostoPorDespacho(int $idDespacho): bool
    {
        // Iniciar una transacción para asegurar la integridad de los datos
        $this->beginTransaction();
        try {
            // 1. Obtener los IDs de los registros pendientes asociados al despacho
            $sql_select_pendientes = "SELECT id_compra_pendiente FROM table_compras_pendientes WHERE id_despacho = ?";
            $pendientes = $this->select_all($sql_select_pendientes, [$idDespacho]);

            if (empty($pendientes)) {
                $this->rollBack();
                return false; // No hay nada que anular
            }

            $ids_pendientes = array_column($pendientes, 'id_compra_pendiente');
            $placeholders = implode(',', array_fill(0, count($ids_pendientes), '?'));

            // 2. Eliminar los registros de la tabla de costos
            $sql_delete_costos = "DELETE FROM table_compras_costos WHERE id_compra_pendiente IN ($placeholders)";
            $this->delete($sql_delete_costos, $ids_pendientes);

            // 3. Actualizar el estado en la tabla de pendientes a 'Pendiente' nuevamente
            $sql_update_pendientes = "UPDATE table_compras_pendientes SET status_costeo = 'Pendiente' WHERE id_despacho = ?";
            $this->update($sql_update_pendientes, [$idDespacho]);

            // Si todo fue bien, confirmar los cambios
            $this->commit();
            return true;
        } catch (Exception $e) {
            // Si algo falla, revertir todos los cambios
            $this->rollBack();
            error_log("Error en anularCostoPorDespacho: " . $e->getMessage());
            return false;
        }
    }


}
<?php
/*
 * Modelo para el módulo de Compras
 * Multi-institución: filtra por id_institucion
 */

class ComprasModel extends Mysql {

    private $id_institucion = 1;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Establece la institución activa.
     */
    public function setInstitucion(int $id): void {
        $this->id_institucion = $id;
    }

    /**
     * Devuelve el id_institucion actual.
     */
    public function getInstitucion(): int {
        return $this->id_institucion;
    }

    /**
     * Obtiene el nombre de una institución.
     */
    public function getNombreInstitucion(int $id): string {
        $sql = "SELECT nombre FROM table_instituciones WHERE id_institucion = ?";
        $result = $this->select($sql, [$id]);
        return $result['nombre'] ?? 'Institución Desconocida';
    }

    /**************************************************/
    /********* COMPRAS PENDIENTES *********************/
    /**************************************************/

    /**
     * Selecciona los despachos pendientes de costeo de la institución activa.
     */
    public function selectComprasPendientes() {
        $sql = "SELECT
                    cp.id_despacho,
                    DATE_FORMAT(STR_TO_DATE(d.fecha_despacho, '%Y-%m-%d'), '%d-%m-%Y') as fecha_despacho,
                    d.numero_orden,
                    f.id_unidad,
                    fm.modelo_unidad,
                    COUNT(cp.id_compra_pendiente) as articulos_pendientes
                FROM table_compras_pendientes cp
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON cp.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE cp.status_costeo = 'Pendiente'
                  AND cp.id_institucion = ?
                GROUP BY cp.id_despacho, d.fecha_despacho, d.numero_orden, f.id_unidad, fm.modelo_unidad
                ORDER BY d.fecha_despacho DESC, cp.id_despacho DESC";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    /**
     * Artículos de un despacho pendientes de costeo.
     */
    public function selectArticulosPorDespacho(int $idDespacho) {
        $sql = "SELECT 
                    cp.id_compra_pendiente,
                    p.producto,
                    cp.cant_despacho
                FROM table_compras_pendientes cp
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                WHERE cp.id_despacho = ? 
                  AND cp.status_costeo = 'Pendiente'
                  AND cp.id_institucion = ?";
        return $this->select_all($sql, [$idDespacho, $this->id_institucion]);
    }

    /**
     * Guarda los costos de los artículos de un despacho.
     */
    public function insertCostosPorDespacho(array $articulos, float $tasa, int $idUsuario) {
        try {
            $registros_procesados = 0;
            foreach ($articulos as $articulo) {
                $idCompraPendiente = intval($articulo['id']);
                $montoDivisa = floatval($articulo['monto']);
                $montoBs = $montoDivisa * $tasa;

                // Insertar costo
                $sql_costo = "INSERT INTO table_compras_costos 
                                (id_compra_pendiente, tasa_dia, monto_divisa, monto_bs, fecha_costeo, id_usuario_costeo, id_institucion) 
                              VALUES (?, ?, ?, ?, CURDATE(), ?, ?)";
                $this->insert($sql_costo, [$idCompraPendiente, $tasa, $montoDivisa, $montoBs, $idUsuario, $this->id_institucion]);

                // Actualizar estado del pendiente
                $sql_update = "UPDATE table_compras_pendientes 
                               SET status_costeo = 'Costeado' 
                               WHERE id_compra_pendiente = ? 
                                 AND id_institucion = ?";
                $this->update($sql_update, [$idCompraPendiente, $this->id_institucion]);
                $registros_procesados++;
            }
            return $registros_procesados;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene la información básica de un despacho.
     */
    public function getInfoDespacho(int $idDespacho) {
        $sql = "SELECT 
                    d.id_despacho,
                    d.numero_orden,
                    DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                    f.id_unidad,
                    fm.modelo_unidad
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE d.id_despacho = ?
                  AND d.id_institucion = ?";
        return $this->select($sql, [$idDespacho, $this->id_institucion]);
    }

    /**************************************************/
    /********* COMPRAS COSTEADAS **********************/
    /**************************************************/

    /**
     * Lista de compras costeadas con paginación y filtros.
     */
    public function selectComprasCosteadas(int $start, int $length, string $searchValue, ?string $fechaInicio, ?string $fechaFin) {
        $sqlBase = "FROM table_compras_costos cc
                    INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                    INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                    INNER JOIN table_flota f ON d.id_flota = f.id_flota
                    INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                    WHERE cp.status_costeo = 'Costeado'
                      AND cc.id_institucion = ?";

        $params = [$this->id_institucion];

        if ($fechaInicio && $fechaFin) {
            $sqlBase .= " AND d.fecha_despacho BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        if (!empty($searchValue)) {
            $sqlBase .= " AND (d.id_despacho LIKE ? OR d.numero_orden LIKE ? OR f.id_unidad LIKE ? OR fm.modelo_unidad LIKE ?)";
            $searchTerm = "%{$searchValue}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Conteo filtrado
        $sqlFiltered = "SELECT COUNT(DISTINCT d.id_despacho) as total " . $sqlBase;
        $totalFiltered = $this->select($sqlFiltered, $params)['total'] ?? 0;

        // Conteo total de la institución
        $sqlTotal = "SELECT COUNT(DISTINCT d.id_despacho) as total 
                     FROM table_compras_costos cc
                     INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                     INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                     WHERE cp.status_costeo = 'Costeado'
                       AND cc.id_institucion = ?";
        $totalRecords = $this->select($sqlTotal, [$this->id_institucion])['total'] ?? 0;

        // Consulta principal
        $sqlData = "SELECT 
                        d.id_despacho,
                        d.numero_orden,
                        DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                        f.id_unidad,
                        fm.modelo_unidad,
                        COUNT(cc.id_costo) as articulos_costeados,
                        SUM(cc.monto_divisa) as total_divisa,
                        SUM(cc.monto_bs) as total_bs "
                    . $sqlBase .
                    " GROUP BY d.id_despacho, d.numero_orden, d.fecha_despacho, f.id_unidad, fm.modelo_unidad
                      ORDER BY d.fecha_despacho DESC, d.id_despacho DESC
                      LIMIT $start, $length";
        $data = $this->select_all($sqlData, $params);

        return ["data" => $data, "total" => $totalRecords, "total_filtered" => $totalFiltered];
    }

    /**
     * Detalle de un despacho costeado.
     */
    public function selectDetalleCosteado(int $idDespacho) {
        $sql = "SELECT 
                    p.producto,
                    cp.cant_despacho,
                    cc.tasa_dia,
                    cc.monto_divisa,
                    cc.monto_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                WHERE cp.id_despacho = ? 
                  AND cp.status_costeo = 'Costeado'
                  AND cc.id_institucion = ?";
        return $this->select_all($sql, [$idDespacho, $this->id_institucion]);
    }

    /**
     * Anula el costeo de un despacho.
     */
    public function anularCostoPorDespacho(int $idDespacho): bool
    {
        $this->beginTransaction();
        try {
            // Obtener pendientes asociados al despacho de ESTA institución
            $sql_select_pendientes = "SELECT id_compra_pendiente 
                                      FROM table_compras_pendientes 
                                      WHERE id_despacho = ? 
                                        AND id_institucion = ?";
            $pendientes = $this->select_all($sql_select_pendientes, [$idDespacho, $this->id_institucion]);

            if (empty($pendientes)) {
                $this->rollBack();
                return false;
            }

            $ids_pendientes = array_column($pendientes, 'id_compra_pendiente');
            $placeholders = implode(',', array_fill(0, count($ids_pendientes), '?'));

            // Eliminar costos
            $sql_delete_costos = "DELETE FROM table_compras_costos 
                                  WHERE id_compra_pendiente IN ($placeholders)
                                    AND id_institucion = ?";
            $paramsDelete = $ids_pendientes;
            $paramsDelete[] = $this->id_institucion;
            $this->delete($sql_delete_costos, $paramsDelete);

            // Revertir estado
            $sql_update_pendientes = "UPDATE table_compras_pendientes 
                                      SET status_costeo = 'Pendiente' 
                                      WHERE id_despacho = ? 
                                        AND id_institucion = ?";
            $this->update($sql_update_pendientes, [$idDespacho, $this->id_institucion]);

            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            error_log("Error en anularCostoPorDespacho: " . $e->getMessage());
            return false;
        }
    }

    /**************************************************/
    /********* FLOTA ACTIVA (para reportes) ***********/
    /**************************************************/

    /**
     * Unidades activas de la institución activa.
     */
    public function selectFlotaActiva() {
        $sql = "SELECT f.id_flota, f.id_unidad, fm.modelo_unidad 
                FROM table_flota f
                INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                WHERE f.status_unidad = 1 
                  AND f.id_institucion = ?
                ORDER BY f.id_unidad ASC";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    /**************************************************/
    /********* REPORTES *******************************/
    /**************************************************/

    /**
     * Reporte de costos por unidad y rango de fechas.
     */
    public function selectReporteCosteado(int $idFlota, string $fechaInicio, string $fechaFin) {
        $sql = "SELECT 
                    cp.id_despacho,
                    d.numero_orden,
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
                  AND cc.id_institucion = ?
                ORDER BY cp.fecha_despacho ASC, cp.id_despacho ASC";
        return $this->select_all($sql, [$idFlota, $fechaInicio, $fechaFin, $this->id_institucion]);
    }

    /**
     * Reporte de compras costeadas filtrado.
     */
    public function selectReporteCosteadasFiltrado(string $searchValue, ?string $fechaInicio, ?string $fechaFin) {
        $sqlBase = "FROM table_compras_costos cc
                    INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                    INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                    INNER JOIN table_flota f ON d.id_flota = f.id_flota
                    INNER JOIN table_flota_modelo fm ON f.id_modelo = fm.id_modelo
                    INNER JOIN table_alm_producto p ON cp.id_producto = p.id_producto
                    WHERE cp.status_costeo = 'Costeado'
                      AND cc.id_institucion = ?";

        $params = [$this->id_institucion];

        if ($fechaInicio && $fechaFin) {
            $sqlBase .= " AND d.fecha_despacho BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }

        if (!empty($searchValue)) {
            $sqlBase .= " AND (d.id_despacho LIKE ? OR d.numero_orden LIKE ? OR f.id_unidad LIKE ? OR fm.modelo_unidad LIKE ? OR p.producto LIKE ?)";
            $searchTerm = "%{$searchValue}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sqlData = "SELECT 
                        d.id_despacho,
                        d.numero_orden,
                        DATE_FORMAT(d.fecha_despacho, '%d-%m-%Y') as fecha_despacho,
                        f.id_flota,
                        f.id_unidad,
                        fm.modelo_unidad,
                        p.producto,
                        cp.cant_despacho,
                        cc.tasa_dia,
                        cc.monto_divisa,
                        cc.monto_bs "
                    . $sqlBase .
                    " ORDER BY f.id_unidad ASC, d.fecha_despacho ASC, d.id_despacho ASC, p.producto ASC";
        return $this->select_all($sqlData, $params);
    }

    /**
     * Conteo de órdenes costeadas para una unidad y rango.
     */
    public function countOrdenesCosteadas(int $idFlota, ?string $fechaInicio = null, ?string $fechaFin = null) {
        $sql = "SELECT COUNT(DISTINCT d.id_despacho) as total
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_despacho d ON cp.id_despacho = d.id_despacho
                WHERE cp.id_flota = ? 
                  AND cp.status_costeo = 'Costeado'
                  AND cc.id_institucion = ?";
        
        $params = [$idFlota, $this->id_institucion];

        if ($fechaInicio && $fechaFin) {
            $sql .= " AND STR_TO_DATE(cp.fecha_despacho, '%Y-%m-%d') BETWEEN ? AND ?";
            $params[] = $fechaInicio;
            $params[] = $fechaFin;
        }
        
        $request = $this->select($sql, $params);
        return $request['total'] ?? 0;
    }

    /**
     * Compras diarias de la institución activa.
     */
    public function selectComprasDiarias(string $fechaInicio, string $fechaFin) {
        $sql = "SELECT 
                    cp.id_despacho,
                    d.numero_orden,
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
                  AND cc.id_institucion = ?
                ORDER BY f.id_unidad ASC, d.fecha_despacho ASC, d.id_despacho ASC";
        return $this->select_all($sql, [$fechaInicio, $fechaFin, $this->id_institucion]);
    }
}
<?php
class ProductoModel extends Mysql {

    private $id_institucion = 1;

    // Whitelist de columnas permitidas para ORDER BY
    private const COLUMNAS_ORDENABLES = [
        'id_producto', 'producto', 'enlace_producto', 'empresa_proveedor',
        'ubicacion', 'cant_producto', 'present_producto'
    ];

    public function __construct(){
        parent::__construct();
    }

    public function setInstitucion(int $id): void {
        $this->id_institucion = $id;
    }

    public function getInstitucion(): int {
        return $this->id_institucion;
    }

    public function getNombreInstitucion(int $id): string {
        $sql = "SELECT nombre FROM table_instituciones WHERE id_institucion = ?";
        $result = $this->select($sql, [$id]);
        return $result['nombre'] ?? 'Institución Desconocida';
    }

    /**************************************************/
    /********* SELECTS PARA FORMULARIOS ***************/
    /**************************************************/

    public function selectEnlace(){
        $sql = "SELECT * FROM table_alm_enlace_producto ORDER BY enlace_producto ASC";
        return $this->select_all($sql);
    }

    public function selectProvee(){
        $sql = "SELECT * FROM table_proveedor WHERE status_proveedor = 1 ORDER BY empresa_proveedor ASC";
        return $this->select_all($sql);
    }

    public function selectUbic(){
        $sql = "SELECT * FROM table_alm_ubicacion ORDER BY ubicacion ASC";
        return $this->select_all($sql);
    }

    public function selectListProductos(){
        $sql = "SELECT p.id_producto, p.producto, e.enlace_producto 
                FROM table_alm_producto p
                INNER JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto 
                WHERE p.status_producto = 1
                  AND p.id_institucion = ?
                ORDER BY p.producto ASC";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    /**************************************************/
    /********* CREACIÓN DE PRODUCTOS ******************/
    /**************************************************/

    public function insertProducto(string $srtArticlo, int $intModelo, int $intProveedor, int $intUbicacion, float $srtCant, int $intOptionArticulo, string $strPresentArticulo){
        $sql = "SELECT * FROM table_alm_producto 
                WHERE producto = ? 
                  AND id_enlace_producto = ? 
                  AND id_institucion = ?";
        $request = $this->select_all($sql, [strtoupper($srtArticlo), $intModelo, $this->id_institucion]);

        if(empty($request)){
            $sql_insert_prod = "INSERT INTO table_alm_producto(id_enlace_producto, id_ubicacion, producto, tag_producto, present_producto, status_producto, id_institucion) VALUES (?,?,?,?,?,1,?)";
            $arrData_prod = [$intModelo, $intUbicacion, strtoupper($srtArticlo), $intOptionArticulo, strtoupper($strPresentArticulo), $this->id_institucion];
            $id_producto = $this->insert($sql_insert_prod, $arrData_prod);

            if($id_producto > 0){
                $sql1 = "INSERT INTO table_alm_relacion_producto(id_producto, id_proveedor, cant_producto, id_institucion) VALUES(?,?,?,?)";
                $arrData1 = [$id_producto, $intProveedor, $srtCant, $this->id_institucion];
                $this->insert($sql1, $arrData1);
            }
            return $id_producto;
        } else {
            return 0;
        }
    }

    /**************************************************/
    /********* HELPERS PRIVADOS ***********************/
    /**************************************************/

    /**
     * Sanea el nombre de la columna para ORDER BY contra una whitelist.
     */
    private function sanitizarColumnaOrden(string $columna): string {
        if (in_array($columna, self::COLUMNAS_ORDENABLES, true)) {
            return $columna;
        }
        return 'id_producto';
    }

    /**
     * Sanea la dirección del ORDER BY.
     */
    private function sanitizarDireccionOrden(string $dir): string {
        return (strtoupper($dir) === 'ASC') ? 'ASC' : 'DESC';
    }

    /**************************************************/
    /********* LISTADO DE PRODUCTOS *******************/
    /**************************************************/

    /**
     * Obtiene los productos para DataTables (server-side).
     * 
     * IMPORTANTE: Se usa una subconsulta para evitar duplicados por JOIN
     * y para que el ORDER BY funcione correctamente con alias.
     */
    public function getProductosServerSide(int $start, int $length, string $searchValue, string $orderColumn, string $orderDir): array
    {
        // Saneamiento de parámetros
        $start  = max(0, (int)$start);
        $length = ($length > 0 && $length <= 1000) ? (int)$length : 25;
        $orderColumn = $this->sanitizarColumnaOrden($orderColumn);
        $orderDir    = $this->sanitizarDireccionOrden($orderDir);

        // --- Construcción de la cláusula WHERE común ---
        $where = " WHERE p.status_producto = 1 AND p.id_institucion = ? ";
        $params = [$this->id_institucion];

        if ($searchValue !== '') {
            $where .= " AND (p.producto LIKE ? 
                              OR p.present_producto LIKE ?
                              OR e.enlace_producto LIKE ? 
                              OR prov.empresa_proveedor LIKE ? 
                              OR u.ubicacion LIKE ?)";
            $searchTerm = "%" . $searchValue . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // --- Conteo total sin filtro ---
        $sqlTotal = "SELECT COUNT(p.id_producto) as total 
                     FROM table_alm_producto p 
                     WHERE p.status_producto = 1 
                       AND p.id_institucion = ?";
        $totalRecords = $this->select($sqlTotal, [$this->id_institucion])['total'] ?? 0;

        // --- Conteo filtrado (usando la misma lógica de joins que la consulta principal) ---
        $sqlFilteredCount = "SELECT COUNT(DISTINCT p.id_producto) as total 
                             FROM table_alm_producto p 
                             LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                             LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                             LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                             LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                             " . $where;
        $totalFiltered = $this->select($sqlFilteredCount, $params)['total'] ?? 0;

        // --- Consulta principal usando subconsulta para evitar duplicados y permitir ORDER BY por alias ---
        $sql = "SELECT * FROM (
                    SELECT 
                        p.id_producto, 
                        p.producto, 
                        p.present_producto, 
                        MAX(prov.empresa_proveedor) AS empresa_proveedor, 
                        COALESCE(SUM(rel.cant_producto), 0) AS cant_producto, 
                        MAX(u.ubicacion) AS ubicacion, 
                        MAX(e.enlace_producto) AS enlace_producto 
                    FROM table_alm_producto p 
                    LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                    LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                    LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                    LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                    " . $where . "
                    GROUP BY p.id_producto, p.producto, p.present_producto
                ) AS sub
                ORDER BY $orderColumn $orderDir
                LIMIT $start, $length";

        $data = $this->select_all($sql, $params);

        return [
            "data" => $data,
            "total" => $totalRecords,
            "total_filtered" => $totalFiltered
        ];
    }

    public function selectProducto(int $idProducto){
        $sql = "SELECT 
                    p.id_producto,
                    p.producto,
                    p.present_producto,
                    p.id_enlace_producto,
                    p.tag_producto,
                    p.id_ubicacion,
                    rel.cant_producto,
                    rel.id_proveedor
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                WHERE p.id_producto = ? 
                  AND p.status_producto = 1
                  AND p.id_institucion = ?";
        return $this->select($sql, [$idProducto, $this->id_institucion]);
    }

    public function upCantProducto(int $intIdProducto, float $srtCantNew){
        $sql = "UPDATE table_alm_relacion_producto 
                SET cant_producto = ? 
                WHERE id_producto = ? 
                  AND id_institucion = ?";
        $arrData = [$srtCantNew, $intIdProducto, $this->id_institucion];
        return $this->update($sql, $arrData);
    }

    public function delProducto(int $intIdProducto){
        $sql = "UPDATE table_alm_producto 
                SET status_producto = 0 
                WHERE id_producto = ? 
                  AND id_institucion = ?";
        return $this->update($sql, [$intIdProducto, $this->id_institucion]);
    }

    /**************************************************/
    /********* HISTORIAL DE PRODUCTOS *****************/
    /**************************************************/

    public function getHistorySummary() {
        $sql = "SELECT 
                    p.id_producto, 
                    p.producto,
                    p.present_producto,
                    rp.cant_producto AS stock_actual, 
                    COALESCE(SUM(rd.cant_despacho), 0) AS total_despachado,  
                    MIN(d.fecha_despacho) AS primer_despacho, 
                    MAX(d.fecha_despacho) AS ultimo_despacho
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                LEFT JOIN table_alm_relacion_despacho rd ON p.id_producto = rd.id_producto
                LEFT JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho AND d.status_despacho = 1
                WHERE p.status_producto = 1
                  AND p.id_institucion = ?
                GROUP BY p.id_producto, p.producto, rp.cant_producto
                ORDER BY p.producto ASC";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    public function getHistorySummaryByDateRange($fechaInicio, $fechaFin) {
        $sql = "SELECT 
                    p.id_producto, 
                    p.producto,
                    p.present_producto,
                    rp.cant_producto AS stock_actual, 
                    COALESCE(SUM(rd.cant_despacho), 0) AS total_despachado,  
                    MIN(d.fecha_despacho) AS primer_despacho, 
                    MAX(d.fecha_despacho) AS ultimo_despacho
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                INNER JOIN table_alm_relacion_despacho rd ON p.id_producto = rd.id_producto
                INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho AND d.status_despacho = 1
                WHERE p.status_producto = 1
                  AND p.id_institucion = ?
                  AND d.fecha_despacho BETWEEN ? AND ?
                GROUP BY p.id_producto, p.producto, rp.cant_producto
                ORDER BY MIN(d.fecha_despacho) ASC";
        return $this->select_all($sql, [$this->id_institucion, $fechaInicio, $fechaFin]);
    }

    public function getDetailHistoryPaginated(int $idProducto, int $page, int $perPage): array
    {
        $sqlCount = "SELECT COUNT(rd.id_despacho) as total 
                     FROM table_alm_relacion_despacho rd
                     JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                     WHERE rd.id_producto = ? 
                       AND d.status_despacho = 1
                       AND d.id_institucion = ?";
        $totalItems = $this->select($sqlCount, [$idProducto, $this->id_institucion])['total'] ?? 0;
        $totalPages = ceil($totalItems / $perPage);

        $offset = ($page - 1) * $perPage;
        $perPage = intval($perPage);
        $offset = intval($offset);

        $sql = "SELECT 
                     d.id_despacho, d.fecha_despacho, rd.cant_despacho,
                    f.id_unidad, m.modelo_unidad,
                    CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido, '0')) AS operador_nombre
                FROM table_alm_relacion_despacho rd
                INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo m ON f.id_modelo = m.id_modelo
                LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
                WHERE rd.id_producto = ? 
                  AND d.status_despacho = 1
                  AND d.id_institucion = ?
                ORDER BY d.fecha_despacho DESC, d.id_despacho DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->select_all($sql, [$idProducto, $this->id_institucion]);

        return [
            'items' => $items,
            'pagination' => [
                'total_pages' => $totalPages,
                'current_page' => $page
            ]
        ];
    }

    public function selectProductosReporte()
    {
        $sql = "SELECT 
                    p.id_producto,
                    p.producto,
                    p.present_producto,
                    rp.cant_producto,
                    u.ubicacion
                FROM table_alm_producto p
                INNER JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                INNER JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                WHERE p.status_producto = 1
                  AND p.id_institucion = ?
                ORDER BY u.ubicacion, p.producto ASC";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    public function updateFullProducto(int $idProducto, array $data)
    {
        $sql_check = "SELECT id_producto FROM table_alm_producto 
                      WHERE producto = ? 
                        AND id_enlace_producto = ? 
                        AND id_producto != ? 
                        AND id_institucion = ?";
        $request_check = $this->select($sql_check, [strtoupper($data['producto']), $data['id_enlace_producto'], $idProducto, $this->id_institucion]);

        if (!empty($request_check)) {
            return 'exist';
        }

        $sql_producto = "UPDATE table_alm_producto 
                         SET producto = ?, id_enlace_producto = ?, id_ubicacion = ?, present_producto = ?, tag_producto = ?
                         WHERE id_producto = ? 
                           AND id_institucion = ?";
        $arrData_producto = [strtoupper($data['producto']), $data['id_enlace_producto'], $data['id_ubicacion'], strtoupper($data['present_producto']), $data['tag_producto'], $idProducto, $this->id_institucion];
        $request_producto = $this->update($sql_producto, $arrData_producto);

        $sql_relacion = "UPDATE table_alm_relacion_producto 
                         SET id_proveedor = ?, cant_producto = ? 
                         WHERE id_producto = ? 
                           AND id_institucion = ?";
        $arrData_relacion = [$data['id_proveedor'], $data['cant_producto'], $idProducto, $this->id_institucion];
        $request_relacion = $this->update($sql_relacion, $arrData_relacion);

        return $request_producto || $request_relacion;
    }

    /**************************************************/
    /********* INVENTARIO *****************************/
    /**************************************************/

    /**
     * Obtiene productos para la vista de inventario.
     * Misma lógica corregida que getProductosServerSide.
     */
    public function getInventario(int $start, int $length, string $searchValue, string $orderColumn, string $orderDir): array
    {
        $start  = max(0, (int)$start);
        $length = ($length > 0 && $length <= 1000) ? (int)$length : 15;
        $orderColumn = $this->sanitizarColumnaOrden($orderColumn);
        $orderDir    = $this->sanitizarDireccionOrden($orderDir);

        $where = " WHERE p.status_producto = 1 AND p.id_institucion = ? ";
        $params = [$this->id_institucion];

        if ($searchValue !== '') {
            $where .= " AND (p.producto LIKE ? 
                              OR p.present_producto LIKE ?
                              OR e.enlace_producto LIKE ? 
                              OR prov.empresa_proveedor LIKE ? 
                              OR u.ubicacion LIKE ?)";
            $searchTerm = "%" . $searchValue . "%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sqlTotal = "SELECT COUNT(p.id_producto) as total 
                     FROM table_alm_producto p 
                     WHERE p.status_producto = 1 
                       AND p.id_institucion = ?";
        $totalRecords = $this->select($sqlTotal, [$this->id_institucion])['total'] ?? 0;

        $sqlFilteredCount = "SELECT COUNT(DISTINCT p.id_producto) as total 
                             FROM table_alm_producto p 
                             LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                             LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                             LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                             LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                             " . $where;
        $totalFiltered = $this->select($sqlFilteredCount, $params)['total'] ?? 0;

        $sql = "SELECT * FROM (
                    SELECT 
                        p.id_producto, 
                        p.producto, 
                        p.present_producto, 
                        MAX(prov.empresa_proveedor) AS empresa_proveedor, 
                        COALESCE(SUM(rel.cant_producto), 0) AS cant_producto, 
                        MAX(u.ubicacion) AS ubicacion, 
                        MAX(e.enlace_producto) AS enlace_producto 
                    FROM table_alm_producto p 
                    LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                    LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                    LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                    LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                    " . $where . "
                    GROUP BY p.id_producto, p.producto, p.present_producto
                ) AS sub
                ORDER BY $orderColumn $orderDir
                LIMIT $start, $length";

        $data = $this->select_all($sql, $params);

        return [
            "data" => $data,
            "total" => $totalRecords,
            "total_filtered" => $totalFiltered
        ];
    }
}
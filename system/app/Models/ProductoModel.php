<?php
class ProductoModel extends Mysql {

    public function __construct(){
        parent::__construct();
    }

    /**************************************************/
    /********* TODO: SELECTS PARA FORMULARIOS *********/
    /**************************************************/

    /**
     * Obtiene los tipos de enlace para los productos.
     * @return array
     */
    public function selectEnlace(){
        $sql = "SELECT * FROM table_alm_enlace_producto ORDER BY enlace_producto ASC";
        return $this->select_all($sql);
    }

    /**
     * Obtiene la lista de proveedores activos.
     * @return array
     */
    public function selectProvee(){
        $sql = "SELECT * FROM table_proveedor WHERE status_proveedor = 1 ORDER BY empresa_proveedor ASC";
        return $this->select_all($sql);
    }

    /**
     * Obtiene las ubicaciones de almacenamiento.
     * @return array
     */
    public function selectUbic(){
        $sql = "SELECT * FROM table_alm_ubicacion ORDER BY ubicacion ASC";
        return $this->select_all($sql);
    }

    /**
     * Obtiene una lista simplificada de productos para los selects.
     * @return array
     */
    public function selectListProductos(){
        $sql = "SELECT p.id_producto, p.producto, e.enlace_producto 
                FROM table_alm_producto p
                INNER JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto 
                WHERE p.status_producto = 1
                ORDER BY p.producto ASC";
        return $this->select_all($sql);
    }

    /**
     * Inserta un nuevo producto en la base de datos si no existe.
     * @param string $srtArticlo Nombre del artículo.
     * @param int $intModelo ID del tipo/enlace.
     * @param int $intProveedor ID del proveedor.
     * @param int $intUbicacion ID de la ubicación.
     * @param float $srtCant Cantidad inicial.
     * @param int $intOptionArticulo Tag del tipo de artículo (consumible/repuesto).
     * @param string $strPresentArticulo Unidad de medida (unidad, litro, etc.).
     * @return int ID del producto insertado o 0 si ya existe.
     */
    public function insertProducto(string $srtArticlo, int $intModelo, int $intProveedor, int $intUbicacion, float $srtCant, int $intOptionArticulo, string $strPresentArticulo){
        $sql = "SELECT * FROM table_alm_producto WHERE producto = ? AND id_enlace_producto = ?";
        $request = $this->select_all($sql, [$srtArticlo, $intModelo]);

        if(empty($request)){
            $sql_insert_prod = "INSERT INTO table_alm_producto(id_enlace_producto, id_ubicacion, producto, tag_producto, present_producto, status_producto) VALUES (?,?,?,?,?,1)";
            $arrData_prod = [$intModelo, $intUbicacion, $srtArticlo, $intOptionArticulo, $strPresentArticulo];
            $id_producto = $this->insert($sql_insert_prod, $arrData_prod);

            if($id_producto > 0){
                $sql1 = "INSERT INTO table_alm_relacion_producto(id_producto,id_proveedor,cant_producto) VALUES(?,?,?)";
                $arrData1 = [$id_producto, $intProveedor, $srtCant];
                $this->insert($sql1, $arrData1);
            }
            return $id_producto;
        } else {
            return 0;
        }
    }

    /**
     * Obtiene los productos para DataTables con procesamiento del lado del servidor.
     * @param int $start Inicio del límite de registros.
     * @param int $length Número de registros a obtener.
     * @param string $searchValue Valor de búsqueda.
     * @param string $orderColumn Columna por la cual ordenar.
     * @param string $orderDir Dirección del ordenamiento (asc/desc).
     * @return array Datos para DataTables.
     */
    public function getProductosServerSide(int $start, int $length, string $searchValue, string $orderColumn, string $orderDir): array
    {
        $sqlBase = "FROM table_alm_producto p 
                    LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                    LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                    LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                    LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                    WHERE p.status_producto = 1";

        // Búsqueda
        $sqlSearch = "";
        if (!empty($searchValue)) {
            $sqlSearch = " AND (p.producto LIKE '%$searchValue%' OR 
                               e.enlace_producto LIKE '%$searchValue%' OR 
                               prov.empresa_proveedor LIKE '%$searchValue%' OR 
                               u.ubicacion LIKE '%$searchValue%')";
        }

        // Conteo total de registros filtrados
        $sqlFiltered = "SELECT COUNT(p.id_producto) as total " . $sqlBase . $sqlSearch;
        $totalFiltered = $this->select($sqlFiltered)['total'];

        // Conteo total de registros sin filtrar
        $sqlTotal = "SELECT COUNT(p.id_producto) as total " . $sqlBase;
        $totalRecords = $this->select($sqlTotal)['total'];

        // Consulta principal con ordenamiento y paginación
        $sql = "SELECT p.id_producto, p.producto, p.present_producto, prov.empresa_proveedor, rel.cant_producto, u.ubicacion, e.enlace_producto " . $sqlBase . $sqlSearch . "
                ORDER BY $orderColumn $orderDir";
       // $sql = "SELECT p.id_producto, p.producto, p.present_producto, prov.empresa_proveedor, rel.cant_producto, u.ubicacion, e.enlace_producto " . $sqlBase . $sqlSearch.";
        $data = $this->select_all($sql);

        return [ "data" => $data, "total" => $totalRecords, "total_filtered" => $totalFiltered ];
    }

    /**
     * Obtiene los datos de un producto específico.
     * @param int $idProducto ID del producto.
     * @return array|null
     */
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
                WHERE p.id_producto = ? AND p.status_producto = 1";
        return $this->select($sql, [$idProducto]);
    }

    /**
     * Actualiza la cantidad de stock de un producto.
     * @param int $intIdProducto ID del producto.
     * @param float $srtCantNew Nueva cantidad total.
     * @return bool
     */
    public function upCantProducto(int $intIdProducto, float $srtCantNew){
        $sql = "UPDATE table_alm_relacion_producto SET cant_producto = ? WHERE id_producto = ?";
        $arrData = [$srtCantNew, $intIdProducto];
        return $this->update($sql, $arrData);
    }

    /**
     * Realiza una eliminación lógica de un producto.
     * @param int $intIdProducto ID del producto.
     * @return bool
     */
    public function delProducto(int $intIdProducto){
        $sql = "UPDATE table_alm_producto SET status_producto = 0 WHERE id_producto = ?";
        return $this->update($sql, [$intIdProducto]);
    }

    /**
     * Establece una cantidad de stock específica para un producto.
     * @param int $idProducto ID del producto.
     * @param float $newQuantity Nueva cantidad de stock.
     * @return bool
     */
    public function setStock(int $idProducto, float $newQuantity) {
        $sql = "UPDATE table_alm_relacion_producto SET cant_producto = ? WHERE id_producto = ?";
        return $this->update($sql, [$newQuantity, $idProducto]);
    }

    /**************************************************/
    /*********** TODO: HISTORIAL DE PRODUCTOS *********/
    /**************************************************/

    /**
     * Obtiene un resumen del historial de despachos para todos los productos.
     * @return array
     */
    public function getHistorySummary() {
        // --- INICIO DE LA CORRECCIÓN ---
        // Consulta reestructurada para asegurar la correcta agregación de datos.
        $sql = "SELECT
                    p.id_producto,
                    p.producto,
                    p.present_producto,
                    rp.cant_producto AS stock_actual,
                    COALESCE(despachos.total_despachado, 0) AS total_despachado,
                    despachos.primer_despacho,
                    despachos.ultimo_despacho
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rp ON p.id_producto = rp.id_producto
                LEFT JOIN (
                    SELECT rd.id_producto, SUM(rd.cant_despacho) as total_despachado, MIN(d.fecha_despacho) as primer_despacho, MAX(d.fecha_despacho) as ultimo_despacho
                    FROM table_alm_relacion_despacho rd
                    JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho AND d.status_despacho = 1
                    GROUP BY rd.id_producto
                ) AS despachos ON p.id_producto = despachos.id_producto
                WHERE p.status_producto = 1 ORDER BY p.producto ASC";
        return $this->select_all($sql);
    }

    /**
     * Obtiene el historial detallado de despachos para un producto específico.
     * @param int $idProducto ID del producto.
     * @return array
     */
    public function getDetailHistory(int $idProducto) {
        $sql = "SELECT 
                    d.id_despacho,
                    d.fecha_despacho,
                    d.operador AS operador_nombre,
                    f.id_unidad,
                    mo.modelo_unidad,
                    rd.cant_despacho
                FROM table_alm_relacion_despacho rd
                INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE rd.id_producto = ? AND d.status_despacho = 1
                ORDER BY d.fecha_despacho DESC, d.id_despacho DESC";
        return $this->select_all($sql, [$idProducto]);
    }

    /**
     * Obtiene el historial de despachos de un producto de forma paginada.
     * @param int $idProducto - ID del producto.
     * @param int $page - Número de página actual.
     * @param int $perPage - Items por página.
     * @return array - Un array con los items y la información de paginación.
     */
    public function getDetailHistoryPaginated(int $idProducto, int $page, int $perPage): array
    {
        // 1. Contar el total de registros para este producto
        $sqlCount = "SELECT COUNT(rd.id_despacho) as total 
                     FROM table_alm_relacion_despacho rd
                     JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                     WHERE rd.id_producto = ? AND d.status_despacho = 1";
        $totalItems = $this->select($sqlCount, [$idProducto])['total'];
        $totalPages = ceil($totalItems / $perPage);

        // 2. Calcular el offset para la consulta
        $offset = ($page - 1) * $perPage;

        // Asegurarse de que los valores de paginación sean enteros
        $perPage = intval($perPage);
        $offset = intval($offset);

        // 3. Obtener los registros para la página actual
        $sql = "SELECT 
                    d.id_despacho, d.fecha_despacho, rd.cant_despacho,
                    f.id_unidad, m.modelo_unidad, d.operador AS operador_nombre -- El nombre del operador ya está en este campo
                FROM table_alm_relacion_despacho rd
                INNER JOIN table_alm_despacho d ON rd.id_despacho = d.id_despacho
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_modelo m ON f.id_modelo = m.id_modelo
                LEFT JOIN table_personal p ON d.user_id = p.id_personal
                WHERE rd.id_producto = ? AND d.status_despacho = 1
                ORDER BY d.fecha_despacho DESC, d.id_despacho DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $this->select_all($sql, [$idProducto]);

        return [
            'items' => $items,
            'pagination' => [
                'total_pages' => $totalPages,
                'current_page' => $page
            ]
        ];
    }
    /**
     * Obtiene todos los productos con su ubicación para el reporte de inventario.
     * @return array
     */
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
                ORDER BY u.ubicacion, p.producto ASC";
        $request = $this->select_all($sql);
        return $request;
    }

    /**
     * Actualiza los datos completos de un producto en la base de datos.
     * @param int $idProducto ID del producto a actualizar.
     * @param array $data Datos del producto.
     * @return bool|string Retorna true si se actualizó, 'exist' si el nombre ya existe, o false si hay un error.
     */
    public function updateFullProducto(int $idProducto, array $data)
    {
        // 1. Verificar si el nuevo nombre de producto ya existe en otro producto
        $sql_check = "SELECT id_producto FROM table_alm_producto WHERE producto = ? AND id_enlace_producto = ? AND id_producto != ?";
        $request_check = $this->select($sql_check, [$data['producto'], $data['id_enlace_producto'], $idProducto]);

        if (!empty($request_check)) {
            return 'exist'; // El nombre ya está en uso por otro producto
        }

        // 2. Actualizar la tabla principal de productos
        $sql_producto = "UPDATE table_alm_producto 
                         SET producto = ?, id_enlace_producto = ?, id_ubicacion = ?, present_producto = ?, tag_producto = ?
                         WHERE id_producto = ?";
        $arrData_producto = [$data['producto'], $data['id_enlace_producto'], $data['id_ubicacion'], $data['present_producto'], $data['tag_producto'], $idProducto];
        $request_producto = $this->update($sql_producto, $arrData_producto);

        // 3. Actualizar la tabla de relación (stock y proveedor)
        $sql_relacion = "UPDATE table_alm_relacion_producto 
                         SET id_proveedor = ?, cant_producto = ? 
                         WHERE id_producto = ?";
        $arrData_relacion = [$data['id_proveedor'], $data['cant_producto'], $idProducto];
        $request_relacion = $this->update($sql_relacion, $arrData_relacion);

        // Retorna true si al menos una de las actualizaciones fue exitosa
        return $request_producto || $request_relacion;
    }

    /**
     * Obtiene los productos para la vista de inventario con procesamiento del lado del servidor.
     * @param int $start Inicio del límite de registros.
     * @param int $length Número de registros a obtener.
     * @param string $searchValue Valor de búsqueda.
     * @param string $orderColumn Columna por la cual ordenar.
     * @param string $orderDir Dirección del ordenamiento (asc/desc).
     * @return array Datos para DataTables.
     */
    public function getInventario(int $start, int $length, string $searchValue, string $orderColumn, string $orderDir): array
    {
        $sqlBase = "FROM table_alm_producto p 
                    LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                    LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                    LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                    LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                    WHERE p.status_producto = 1";

        // Búsqueda
        $sqlSearch = "";
        if (!empty($searchValue)) {
            $sqlSearch = " AND (p.producto LIKE ? OR e.enlace_producto LIKE ? OR prov.empresa_proveedor LIKE ? OR u.ubicacion LIKE ?)";
            $searchParams = ["%$searchValue%", "%$searchValue%", "%$searchValue%", "%$searchValue%"];
        } else {
            $searchParams = [];
        }

        // Conteo total de registros filtrados
        $sqlFiltered = "SELECT COUNT(p.id_producto) as total " . $sqlBase . $sqlSearch;
        $totalFiltered = $this->select($sqlFiltered, $searchParams)['total'];

        // Conteo total de registros sin filtrar
        $sqlTotal = "SELECT COUNT(p.id_producto) as total " . $sqlBase;
        $totalRecords = $this->select($sqlTotal)['total'];

        // Consulta principal con ordenamiento y paginación
        $sql = "SELECT p.id_producto, p.producto, p.present_producto, prov.empresa_proveedor, rel.cant_producto, u.ubicacion, e.enlace_producto " . $sqlBase . $sqlSearch . "
                ORDER BY $orderColumn $orderDir";
        $data = $this->select_all($sql, $searchParams);

        return [ "data" => $data, "total" => $totalRecords, "total_filtered" => $totalFiltered ];
    }

    /**
     * Obtiene todos los productos de inventario para DataTableRefactor
     */
    public function selectInventarioAll() {
        $sql = "SELECT p.id_producto, p.producto, p.present_producto, prov.empresa_proveedor, rel.cant_producto, u.ubicacion, e.enlace_producto 
                FROM table_alm_producto p 
                LEFT JOIN table_alm_relacion_producto rel ON p.id_producto = rel.id_producto
                LEFT JOIN table_proveedor prov ON rel.id_proveedor = prov.id_proveedor
                LEFT JOIN table_alm_ubicacion u ON p.id_ubicacion = u.id_ubicacion
                LEFT JOIN table_alm_enlace_producto e ON p.id_enlace_producto = e.id_enlace_producto
                WHERE p.status_producto = 1
                ORDER BY p.id_producto DESC";
        return $this->select_all($sql);
    }
}
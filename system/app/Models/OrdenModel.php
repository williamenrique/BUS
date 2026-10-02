<?php
class OrdenModel extends Mysql {

    private $id_institucion = 1;

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

    public function selectListFlota(){
        $sql = "SELECT flota.*, modelo.modelo_unidad 
                FROM table_flota flota
                INNER JOIN table_flota_modelo modelo ON flota.id_modelo = modelo.id_modelo
                WHERE flota.id_institucion = ?
                ORDER BY flota.id_unidad ASC";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    public function selectUnidad(int $intIdUnidad){
        $sql = "SELECT f.*, mo.*, ma.* FROM table_flota f
                INNER JOIN table_flota_marca ma ON f.id_marca = ma.id_marca
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                WHERE f.id_flota = ?
                  AND f.id_institucion = ?";
        return $this->select($sql, [$intIdUnidad, $this->id_institucion]);
    }

    public function selectPersonal(int $intDesp){
        $sql = "SELECT * FROM table_personal WHERE id_personal = ?";
        return $this->select($sql, [$intDesp]);
    }

    public function selectListOper(){
        $sql = "SELECT p.*, c.* FROM table_personal p
                INNER JOIN table_per_cargo c ON p.personal_cargo = c.id_cargo 
                AND p.personal_status <> 0 
                WHERE p.personal_cargo = 23 
                ORDER BY p.personal_cedula DESC";
        return $this->select_all($sql);
    }

    public function selectListMec(){
        $sql = "SELECT p.*, c.* FROM table_personal p
                INNER JOIN table_per_cargo c ON p.personal_cargo = c.id_cargo 
                AND p.personal_status <> 0 
                WHERE (p.personal_cargo BETWEEN 26 AND 29) 
                   OR p.personal_cargo = 32 
                   OR p.personal_cargo = 43 
                ORDER BY p.personal_cedula DESC";
        return $this->select_all($sql);
    }

    public function selectListDesp(){
        $sql = "SELECT p.*, c.* FROM table_personal p
                INNER JOIN table_per_cargo c ON p.personal_cargo = c.id_cargo 
                AND p.personal_status <> 0 
                WHERE p.personal_departamento = 4 
                ORDER BY p.personal_cedula DESC";
        return $this->select_all($sql);
    }

    public function selectArt(int $intIdArt){
        $sql = "SELECT rp.cant_producto 
                FROM table_alm_relacion_producto rp
                INNER JOIN table_alm_producto p ON rp.id_producto = p.id_producto
                WHERE p.id_producto = ?
                  AND p.id_institucion = ?";
        return $this->select($sql, [$intIdArt, $this->id_institucion]);
    }

    public function selectListArt(){
        $sql = "SELECT producto.*, relacionP.*, enlace.enlace_producto 
                FROM table_alm_producto producto
                INNER JOIN table_alm_enlace_producto enlace ON enlace.id_enlace_producto = producto.id_enlace_producto
                INNER JOIN table_alm_relacion_producto relacionP ON producto.id_producto = relacionP.id_producto
                WHERE relacionP.cant_producto > 0 
                  AND producto.status_producto > 0
                  AND producto.id_institucion = ?";
        return $this->select_all($sql, [$this->id_institucion]);
    }

    /**************************************************/
    /********* CREACIÓN DE ÓRDENES ********************/
    /**************************************************/

    private function obtenerSiguienteNumeroOrden(): int {
        $sql = "SELECT COALESCE(MAX(numero_orden), 0) + 1 as siguiente 
                FROM table_alm_despacho 
                WHERE id_institucion = ?";
        $result = $this->select($sql, [$this->id_institucion]);
        return intval($result['siguiente'] ?? 1);
    }

    /**
     * Inserta un nuevo despacho con IDs de personal.
     * Las columnas operador, mecanico, despachador (varchar) fueron eliminadas.
     * Solo se guardan los IDs que referencian a table_personal.
     */
    public function insertDespacho(int $intUnidad, int $idOper, int $idMec, int $idDesp,
                                    int $intIdUser, string $srtObs, string $strDate){
        $numeroOrden = $this->obtenerSiguienteNumeroOrden();
        
        $queryInsert = "INSERT INTO table_alm_despacho
                            (id_flota, 
                             operador_id,
                             mecanico_id,
                             despachador_id,
                             fecha_despacho, user_id, observacion, status_despacho, 
                             id_institucion, numero_orden) 
                        VALUES(?,?,?,?,?,?,?,?,?,?)";
        $requestInsert = $this->insert($queryInsert, [
            $intUnidad,
            $idOper,
            $idMec,
            $idDesp,
            $strDate, $intIdUser, strtoupper($srtObs), 1, 
            $this->id_institucion, $numeroOrden
        ]);
        return $requestInsert;
    }

    public function updateDespacho(int $idDespacho, int $intUnidad, int $idOper, int $idMec, int $idDesp,
                                    string $srtObs, string $strDate){
        $sql = "UPDATE table_alm_despacho SET 
                    id_flota = ?, 
                    operador_id = ?,
                    mecanico_id = ?,
                    despachador_id = ?,
                    fecha_despacho = ?, 
                    observacion = ? 
                WHERE id_despacho = ? AND id_institucion = ?";
        $arrData = [
            $intUnidad, 
            $idOper,
            $idMec,
            $idDesp,
            $strDate, 
            strtoupper($srtObs), 
            $idDespacho, 
            $this->id_institucion
        ];
        return $this->update($sql, $arrData);
    }

    /**
     * Obtiene una orden para editar (ahora con IDs directos).
     */
    public function selectOrdenForEdit(int $idDespacho){
        $sql = "SELECT d.* 
                FROM table_alm_despacho d 
                WHERE d.id_despacho = ? 
                  AND d.id_institucion = ?";
        return $this->select($sql, [$idDespacho, $this->id_institucion]);
    }

    public function revertirYLimpiar(int $idDespacho){
        $articulos = $this->artDespacho($idDespacho);
        
        foreach ($articulos as $art) {
            $this->updateCantN($art['id_producto'], $art['cant_producto'] + $art['cant_despacho']);
        }

        $sqlDelRel = "DELETE FROM table_alm_relacion_despacho WHERE id_despacho = ? AND id_institucion = ?";
        $this->delete($sqlDelRel, [$idDespacho, $this->id_institucion]);

        $sqlDelPend = "DELETE FROM table_compras_pendientes WHERE id_despacho = ? AND id_institucion = ?";
        $this->delete($sqlDelPend, [$idDespacho, $this->id_institucion]);
        
        return true;
    }

    public function insertRDespacho(int $idDespacho, int $idArticulo, float $cantidad, int $idFlota, string $fechaDespacho){
        $sqlRelacion = "INSERT INTO table_alm_relacion_despacho(id_despacho, id_producto, cant_despacho, id_institucion) VALUES(?,?,?,?)";
        $requestRelacion = $this->insert($sqlRelacion, [$idDespacho, $idArticulo, $cantidad, $this->id_institucion]);

        $sqlPendiente = "INSERT INTO table_compras_pendientes 
                            (id_despacho, id_producto, id_flota, cant_despacho, fecha_despacho, status_costeo, id_institucion)
                         VALUES (?, ?, ?, ?, ?, 'Pendiente', ?)";
        
        $paramsPendiente = [
            $idDespacho, $idArticulo, $idFlota, $cantidad, $fechaDespacho, $this->id_institucion
        ];
        $this->insert($sqlPendiente, $paramsPendiente);
        return $requestRelacion;
    }

    public function updateCant(int $intIdArticulo, float $intCant){
        $sqlSelect = "SELECT cant_producto FROM table_alm_relacion_producto WHERE id_producto = ? AND id_institucion = ?";
        $request = $this->select($sqlSelect, [$intIdArticulo, $this->id_institucion]);
        $nuevaCant = $request['cant_producto'] - $intCant;
        $sql = "UPDATE table_alm_relacion_producto SET cant_producto = ? WHERE id_producto = ? AND id_institucion = ?";
        $arrData = [$nuevaCant, $intIdArticulo, $this->id_institucion];
        return $this->update($sql, $arrData);
    }

    /**************************************************/
    /********* CONSULTA Y BÚSQUEDA ********************/
    /**************************************************/

    /**
     * Obtiene todas las órdenes. Usa CONCAT_WS para concatenar nombre y apellido
     * saltando valores NULL, con fallback 'SIN OPERADOR'.
     */
    public function selectOrdenes(): array
    {
        $sql = "SELECT 
                    d.id_despacho,
                    d.numero_orden,
                    d.fecha_despacho,
                    f.id_unidad, 
                    m.marca_unidad, 
                    mo.modelo_unidad,
                    COALESCE(
                        CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido, '0')),
                        'SIN OPERADOR'
                    ) AS operador_nombre,
                    d.operador_id,
                    (SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
                WHERE d.status_despacho = 1
                  AND d.id_institucion = ?
                ORDER BY d.numero_orden DESC";
        
        return $this->select_all($sql, [$this->id_institucion]);
    }

    public function getListArtDesp(int $intDesp){
        $sql = "SELECT producto.*, relacionP.cant_despacho, enlaceP.*, u.ubicacion, rp.cant_producto
                FROM table_alm_producto producto 
                JOIN table_alm_relacion_despacho relacionP ON producto.id_producto = relacionP.id_producto 
                JOIN table_alm_enlace_producto enlaceP ON enlaceP.id_enlace_producto = producto.id_enlace_producto
                JOIN table_alm_ubicacion u ON u.id_ubicacion = producto.id_ubicacion
                LEFT JOIN table_alm_relacion_producto rp ON producto.id_producto = rp.id_producto
                WHERE relacionP.id_despacho = ?";
        return $this->select_all($sql, [$intDesp]);
    }

    public function getListBuscarOrdenes(string $strCod, string $strFecha, string $strUnidad, string $strArt){
        $sql = "SELECT 
                    d.id_despacho,
                    d.numero_orden,
                    d.fecha_despacho,
                    f.id_unidad, 
                    m.marca_unidad, 
                    mo.modelo_unidad,
                    COALESCE(
                        CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido, '0')),
                        'SIN OPERADOR'
                    ) AS operador_nombre,
                    COALESCE(
                        CONCAT_WS(' ', p_mec.personal_nombre, NULLIF(p_mec.personal_apellido, '0')),
                        'SIN MECÁNICO'
                    ) AS mecanico_nombre,
                    COALESCE(
                        CONCAT_WS(' ', p_desp.personal_nombre, NULLIF(p_desp.personal_apellido, '0')),
                        'SIN DESPACHADOR'
                    ) AS despachador_nombre,
                    (SELECT COUNT(*) FROM table_alm_relacion_despacho rd WHERE rd.id_despacho = d.id_despacho) AS total_articulos,
                    u.usuario_nick as usuario_registro
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON d.id_flota = f.id_flota
                INNER JOIN table_flota_marca m ON f.id_marca = m.id_marca
                INNER JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
                INNER JOIN table_usuarios u ON d.user_id = u.usuario_id
                LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
                LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
                LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
                WHERE d.status_despacho = 1
                  AND d.id_institucion = ?";

        $params = [$this->id_institucion];
        $conditions = [];

        if (!empty($strCod)) {
            $conditions[] = "d.numero_orden = ?";
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

        $sql .= " ORDER BY d.numero_orden DESC";

        return $this->select_all($sql, $params);
    }

    /**
     * Obtiene un despacho. Usa CONCAT_WS para concatenar nombre y apellido
     * saltando valores NULL, con fallback por defecto.
     * Las columnas operador, mecanico, despachador fueron eliminadas.
     */
    public function selectDepacho(int $strCod){
        $sql = "SELECT 
                    desp.id_despacho, 
                    desp.numero_orden,
                    desp.fecha_despacho, 
                    desp.observacion,
                    COALESCE(
                        CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido, '0')),
                        'SIN OPERADOR'
                    ) AS operador_nombre,
                    COALESCE(
                        CONCAT_WS(' ', p_mec.personal_nombre, NULLIF(p_mec.personal_apellido, '0')),
                        'SIN MECÁNICO'
                    ) AS mecanico_nombre,
                    COALESCE(
                        CONCAT_WS(' ', p_desp.personal_nombre, NULLIF(p_desp.personal_apellido, '0')),
                        'SIN DESPACHADOR'
                    ) AS despachador_nombre,
                    flota.id_unidad, flota.vim_unidad,
                    modelo.modelo_unidad, 
                    marca.marca_unidad,
                    CONCAT_WS(' ', p.personal_nombre, NULLIF(p.personal_apellido, '0')) as usuario_registro
                FROM table_alm_despacho desp
                INNER JOIN table_flota flota ON desp.id_flota = flota.id_flota
                INNER JOIN table_usuarios usuario ON desp.user_id = usuario.usuario_id
                INNER JOIN table_personal p ON usuario.usuario_id_personal = p.id_personal
                INNER JOIN table_flota_modelo modelo ON modelo.id_modelo = flota.id_modelo
                INNER JOIN table_flota_marca marca ON marca.id_marca = flota.id_marca 
                LEFT JOIN table_personal p_op ON desp.operador_id = p_op.id_personal
                LEFT JOIN table_personal p_mec ON desp.mecanico_id = p_mec.id_personal
                LEFT JOIN table_personal p_desp ON desp.despachador_id = p_desp.id_personal
                WHERE desp.id_despacho = ?
                  AND desp.id_institucion = ?";
        return $this->select($sql, [$strCod, $this->id_institucion]);
    }

    /**************************************************/
    /********* ELIMINACIÓN Y REVERSIÓN ****************/
    /**************************************************/

    public function delDesp(int $idDesp, string $srtText, int $intUserId){
        $srtInfo = strtoupper('USUARIO '.$_SESSION['userData']['usuario_nick'].' ELIMINO ORDEN DE DESPACHO #' . $idDesp);
        $intStatus = 0;
        $sql = "UPDATE table_alm_despacho SET status_despacho = ? WHERE id_despacho = ? AND id_institucion = ?";
        $request = $this->update($sql, [$intStatus, $idDesp, $this->id_institucion]);
        if($request){
            $insertH = "INSERT INTO table_alm_historial_cambio(obs, info, id_user) VALUES(?,?,?)";
            $this->insert($insertH, [$srtText, $srtInfo, $intUserId]);
        }
        return $request;
    }

    public function artDespacho(int $idDesp){
        $sql = "SELECT tProducto.producto, tRelacionP.cant_producto, tRelacionD.* 
                FROM table_alm_producto tProducto
                INNER JOIN table_alm_relacion_producto tRelacionP ON tProducto.id_producto = tRelacionP.id_producto
                INNER JOIN table_alm_relacion_despacho tRelacionD ON tRelacionD.id_producto = tRelacionP.id_producto 
                WHERE tRelacionD.id_despacho = ?
                  AND tRelacionD.id_institucion = ?";
        return $this->select_all($sql, [$idDesp, $this->id_institucion]);
    }

    public function updateCantN(int $intIdArticulo, float $intCant){
        $queryUpdate = "UPDATE table_alm_relacion_producto SET cant_producto = ? WHERE id_producto = ? AND id_institucion = ?";
        $arrData = [$intCant, $intIdArticulo, $this->id_institucion];
        return $this->update($queryUpdate, $arrData);
    }

    /**************************************************/
    /********* ESTADÍSTICAS ***************************/
    /**************************************************/

    public function getMonthlyOrderCount() {
        $currentMonth = date('Y-m');
        $sql = "SELECT COUNT(id_despacho) as total_ordenes 
                FROM table_alm_despacho 
                WHERE DATE_FORMAT(fecha_despacho, '%Y-%m') = ? 
                  AND status_despacho = 1
                  AND id_institucion = ?";
        return $this->select($sql, [$currentMonth, $this->id_institucion]);
    }
}
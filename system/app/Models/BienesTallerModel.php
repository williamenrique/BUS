<?php
/**
 * Modelo para la gestión de Bienes de Taller (Activos).
 * Se encarga de todas las interacciones con la base de datos relacionadas con el inventario de bienes de taller.
 */
class BienesTallerModel extends Mysql {
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Selecciona todos los bienes de taller activos del inventario con sus datos relacionados.
     * @return array - Lista de todos los bienes de taller.
     */
    public function selectBienesTaller(): array {
        $sql = "SELECT 
                    bt.id_bien_taller,
                    bt.descripcion_bien,
                    d.departamento_bien,
                    g.grupo,
                    sg.subgrupo,
                    s.seccion,
                    bt.fecha_adquisicion,
                    bt.status_bien
                FROM table_bienes_taller_inventario bt
                INNER JOIN table_bienes_departamentos d ON bt.bien_depatamento_id = d.depatamento_bien_id
                INNER JOIN table_bienes_grupo g ON bt.grupo_id = g.id_grupo
                INNER JOIN table_bienes_subgrupo sg ON bt.subgrupo_id = sg.subgrupo_id
                INNER JOIN table_bienes_seccion s ON bt.seccion_id = s.seccion_id
                WHERE bt.status = 1 GROUP BY bt.id_bien_taller";
        $request = $this->select_all($sql);
        return $request;
    }

    /**
     * Selecciona un bien de taller específico por su ID.
     * @param int $id_bien_taller - El ID del bien de taller a seleccionar.
     * @return array|null - Los datos del bien de taller o null si no se encuentra.
     */
    public function selectBienTaller(int $id_bien_taller) {
        $sql = "SELECT * FROM table_bienes_taller_inventario WHERE id_bien_taller = ? AND status = 1";
        return $this->select($sql, [$id_bien_taller]);
    }

    /**
     * Inserta un nuevo bien de taller en la base de datos.
     * @param array $data - Datos del bien de taller a insertar.
     * @return int - El ID del bien de taller insertado.
     */
    public function insertBienTaller(array $data): int {
        $query_insert = "INSERT INTO table_bienes_taller_inventario(bien_depatamento_id, grupo_id, subgrupo_id, seccion_id, descripcion_bien, fecha_adquisicion, status_bien, user_id, status) VALUES(?,?,?,?,?,?,?,?,1)";
        $arrData = array(
            $data['bien_depatamento_id'],
            $data['grupo_id'],
            $data['subgrupo_id'],
            $data['seccion_id'],
            strtoupper($data['descripcion_bien']),
            $data['fecha_adquisicion'],
            strtoupper($data['status_bien']),
            $data['user_id']
        );
        $request_insert = $this->insert($query_insert, $arrData);
        return $request_insert;
    }

    /**
     * Actualiza un bien de taller existente en la base de datos.
     * @param array $data - Datos del bien de taller a actualizar, incluyendo su ID.
     * @return bool - True si la actualización fue exitosa.
     */
    public function updateBienTaller(array $data): bool {
        $sql = "UPDATE table_bienes_taller_inventario 
                SET bien_depatamento_id = ?, 
                    grupo_id = ?, 
                    subgrupo_id = ?, 
                    seccion_id = ?, 
                    descripcion_bien = ?, 
                    fecha_adquisicion = ?, 
                    status_bien = ? 
                WHERE id_bien_taller = ?";
        $arrData = array(
            $data['bien_depatamento_id'],
            $data['grupo_id'],
            $data['subgrupo_id'],
            $data['seccion_id'],
            strtoupper($data['descripcion_bien']),
            $data['fecha_adquisicion'],
            strtoupper($data['status_bien']),
            $data['id_bien_taller']
        );
        $request = $this->update($sql, $arrData);
        return $request;
    }

    /**
     * Realiza una eliminación lógica de un bien de taller (cambia status a 0).
     * @param int $id_bien_taller - El ID del bien de taller a eliminar.
     * @return bool - True si la eliminación fue exitosa.
     */
    public function deleteBienTaller(int $id_bien_taller): bool {
        $sql = "UPDATE table_bienes_taller_inventario SET status = 0 WHERE id_bien_taller = ?";
        return $this->update($sql, [$id_bien_taller]);
    }

    /**
     * Obtiene la lista de departamentos de bienes activos.
     * @return array
     */
    public function getDepartamentos(): array {
        $sql = "SELECT depatamento_bien_id, departamento_bien FROM table_bienes_departamentos WHERE departamento_status = 1";
        return $this->select_all($sql);
    }

    /**
     * Obtiene la lista de grupos de bienes activos.
     * @return array
     */
    public function getGrupos(): array {
        $sql = "SELECT id_grupo, grupo FROM table_bienes_grupo WHERE grupo_status = 1";
        return $this->select_all($sql);
    }

    /**
     * Obtiene la lista de subgrupos de bienes activos.
     * @return array
     */
    public function getSubgrupos(): array {
        $sql = "SELECT subgrupo_id, subgrupo FROM table_bienes_subgrupo WHERE subgrupo_status = 1";
        return $this->select_all($sql);
    }

    /**
     * Obtiene la lista de secciones de bienes activos.
     * @return array
     */
    public function getSecciones(): array {
        $sql = "SELECT seccion_id, seccion FROM table_bienes_seccion WHERE seccion_status = 1";
        return $this->select_all($sql);
    }

    /**
     * Obtiene los datos de un departamento específico por su ID.
     * @param string $idDepartamento - El ID del departamento.
     * @return array|null
     */
    public function getDepartamento(string $idDepartamento) {
        $sql = "SELECT * FROM table_bienes_departamentos WHERE depatamento_bien_id = ?";
        return $this->select($sql, [$idDepartamento]);
    }

    /**
     * Obtiene todos los bienes de taller ordenados por departamento para el reporte general.
     */
    public function getAllBienesTallerOrdenados() {
        $sql = "SELECT 
                    bt.id_bien_taller AS id_bien,
                    bt.descripcion_bien,
                    d.departamento_bien,
                    g.grupo,
                    s.subgrupo,
                    sec.seccion,
                    bt.status_bien
                FROM table_bienes_taller_inventario bt
                LEFT JOIN table_bienes_departamentos d ON bt.bien_depatamento_id = d.depatamento_bien_id
                LEFT JOIN table_bienes_grupo g ON bt.grupo_id = g.id_grupo
                LEFT JOIN table_bienes_subgrupo s ON bt.subgrupo_id = s.subgrupo_id
                LEFT JOIN table_bienes_seccion sec ON bt.seccion_id = sec.seccion_id
                ORDER BY d.departamento_bien, bt.id_bien_taller ASC";
        return $this->select_all($sql);
    }

    /**
     * Selecciona todos los bienes de taller de un departamento específico.
     */
    public function selectBienesTallerPorDepartamento(string $idDepartamento) {
        $sql = "SELECT 
                    bt.id_bien_taller AS id_bien,
                    bt.descripcion_bien,
                    d.departamento_bien,
                    g.grupo,
                    s.subgrupo,
                    sec.seccion,
                    bt.status_bien
                FROM table_bienes_taller_inventario bt
                LEFT JOIN table_bienes_departamentos d ON bt.bien_depatamento_id = d.depatamento_bien_id
                LEFT JOIN table_bienes_grupo g ON bt.grupo_id = g.id_grupo
                LEFT JOIN table_bienes_subgrupo s ON bt.subgrupo_id = s.subgrupo_id
                LEFT JOIN table_bienes_seccion sec ON bt.seccion_id = sec.seccion_id
                WHERE bt.bien_depatamento_id = ?";
        return $this->select_all($sql, [$idDepartamento]);
    }

    /**
     * Selecciona todos los bienes de taller que coincidan con un término de búsqueda.
     * @param string $termino - El término a buscar en la descripción, grupo, etc.
     * @return array
     */
    public function selectBienesTallerPorBusqueda(string $termino): array {
        $terminoLike = "%" . $termino . "%";
        $sql = "SELECT 
                    bt.id_bien_taller AS id_bien,
                    bt.descripcion_bien,
                    d.departamento_bien,
                    g.grupo,
                    s.subgrupo,
                    sec.seccion,
                    bt.status_bien
                FROM table_bienes_taller_inventario bt
                LEFT JOIN table_bienes_departamentos d ON bt.bien_depatamento_id = d.depatamento_bien_id
                LEFT JOIN table_bienes_grupo g ON bt.grupo_id = g.id_grupo
                LEFT JOIN table_bienes_subgrupo s ON bt.subgrupo_id = s.subgrupo_id
                LEFT JOIN table_bienes_seccion sec ON bt.seccion_id = sec.seccion_id
                WHERE (bt.descripcion_bien LIKE ? OR g.grupo LIKE ? OR s.subgrupo LIKE ? OR sec.seccion LIKE ?) AND bt.status = 1
                ORDER BY bt.id_bien_taller ASC";
        return $this->select_all($sql, [$terminoLike, $terminoLike, $terminoLike, $terminoLike]);
    }
}
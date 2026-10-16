<?php
namespace App\Models;

use App\Core\Model;

class ModelFlota extends Model{

    /* ============================================================
       LISTADO PAGINADO
       ============================================================ */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = [], int $inst = 0): array{
        $where  = "1=1";
        $params = [];

        if ($inst > 0){
            $where .= " AND f.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            f.id_unidad      LIKE :q1
                         OR f.vim_unidad     LIKE :q2
                         OR m.marca_unidad   LIKE :q3
                         OR mo.modelo_unidad LIKE :q4
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }
        if (isset($filtros['status']) && $filtros['status'] !== ''){
            $where .= " AND f.status_unidad = :status";
            $params['status'] = (int)$filtros['status'];
        }
        if (!empty($filtros['marca'])){
            $where .= " AND f.id_marca = :marca";
            $params['marca'] = (int)$filtros['marca'];
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_flota WHERE id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_flota f
             LEFT JOIN table_flota_marca m  ON m.id_marca  = f.id_marca
             LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.status_unidad,
                       f.tipo_combustible, f.transmision,
                       f.cap_pasajero, f.fecha_creacion,
                       f.id_marca, f.id_modelo, f.id_institucion,
                       m.marca_unidad, mo.modelo_unidad
                FROM table_flota f
                LEFT JOIN table_flota_marca m  ON m.id_marca  = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                WHERE {$where}
                ORDER BY f.id_flota DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       TODAS (para reporte de operatividad)
       ============================================================ */
    public function todas(int $inst = 0): array{
        $where  = "f.status_unidad <> 0";
        $params = [];
        if ($inst > 0){
            $where .= " AND f.id_institucion = :inst";
            $params['inst'] = $inst;
        }
        $sql = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.status_unidad,
                       f.tipo_combustible, f.transmision,
                       f.cap_pasajero, f.fecha_creacion,
                       m.marca_unidad, mo.modelo_unidad
                FROM table_flota f
                LEFT JOIN table_flota_marca m  ON m.id_marca  = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                WHERE {$where}
                ORDER BY f.id_unidad ASC";
        return $this->fetchAll($sql, $params);
    }

    /* ============================================================
       FIND
       ============================================================ */
    public function find(int $id): ?array{
        $sql = "SELECT f.*, m.marca_unidad, mo.modelo_unidad
                FROM table_flota f
                LEFT JOIN table_flota_marca m  ON m.id_marca  = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                WHERE f.id_flota = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /* ============================================================
       CREATE (con institución)
       ============================================================ */
    public function create(array $d, int $inst = 0): int{
        $sql = "INSERT INTO table_flota
                    (id_unidad, id_marca, id_modelo, vim_unidad, fecha_creacion,
                     cap_pasajero, tipo_combustible, transmision, status_unidad, id_institucion)
                VALUES
                    (:id_unidad, :id_marca, :id_modelo, :vim, :fecha,
                     :cap, :comb, :trans, 1, :inst)";
        $this->query($sql, [
            'id_unidad' => $d['id_unidad'],
            'id_marca'  => (int)$d['id_marca'],
            'id_modelo' => (int)$d['id_modelo'],
            'vim'       => $d['vim_unidad'],
            'fecha'     => $d['fecha_creacion'] ?? '',
            'cap'       => (int)($d['cap_pasajero'] ?? 0),
            'comb'      => $d['tipo_combustible'] ?? 'N/A',
            'trans'     => $d['transmision'] ?? 'N/A',
            'inst'      => $inst > 0 ? $inst : DEFAULT_INSTITUCION,
        ]);
        return (int)$this->lastInsertId();
    }

    /* ============================================================
       UPDATE
       ============================================================ */
    public function update(int $id, array $d): bool{
        $sql = "UPDATE table_flota SET
                    id_unidad        = :id_unidad,
                    id_marca         = :id_marca,
                    id_modelo        = :id_modelo,
                    vim_unidad       = :vim,
                    fecha_creacion   = :fecha,
                    cap_pasajero     = :cap,
                    tipo_combustible = :comb,
                    transmision      = :trans
                WHERE id_flota = :id";
        return $this->execute($sql, [
            'id_unidad' => $d['id_unidad'],
            'id_marca'  => (int)$d['id_marca'],
            'id_modelo' => (int)$d['id_modelo'],
            'vim'       => $d['vim_unidad'],
            'fecha'     => $d['fecha_creacion'] ?? '',
            'cap'       => (int)($d['cap_pasajero'] ?? 0),
            'comb'      => $d['tipo_combustible'] ?? 'N/A',
            'trans'     => $d['transmision'] ?? 'N/A',
            'id'        => $id,
        ]);
    }

    /* ============================================================
       CAMBIAR STATUS
       ============================================================ */
    public function cambiarStatus(int $id, int $status, string $motivo, int $userId): bool{
        $this->execute(
            "UPDATE table_flota SET status_unidad = :s WHERE id_flota = :id",
            ['s' => $status, 'id' => $id]
        );

        return $this->execute(
            "INSERT INTO table_flota_status (id_flota, idstatus, textCambio, fechaCambio, usuario_id)
             VALUES (:id, :s, :motivo, NOW(), :uid)",
            ['id' => $id, 's' => $status, 'motivo' => $motivo, 'uid' => $userId]
        );
    }

    /* ============================================================
       VALIDACIONES
       ============================================================ */
    public function existeUnidad(string $idUnidad, ?int $excludeId = null): bool{
        $sql = "SELECT 1 FROM table_flota WHERE id_unidad = :u";
        $params = ['u' => $idUnidad];
        if ($excludeId){ $sql .= " AND id_flota <> :id"; $params['id'] = $excludeId; }
        $sql .= " LIMIT 1";
        return (bool)$this->fetchOne($sql, $params);
    }

    public function existeVin(string $vim, ?int $excludeId = null): bool{
        if ($vim === '') return false;
        $sql = "SELECT 1 FROM table_flota WHERE vim_unidad = :v";
        $params = ['v' => $vim];
        if ($excludeId){ $sql .= " AND id_flota <> :id"; $params['id'] = $excludeId; }
        $sql .= " LIMIT 1";
        return (bool)$this->fetchOne($sql, $params);
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function marcas(): array{
        return $this->fetchAll(
            "SELECT id_marca, marca_unidad FROM table_flota_marca ORDER BY marca_unidad ASC"
        );
    }

    public function modelos(): array{
        return $this->fetchAll(
            "SELECT id_modelo, modelo_unidad FROM table_flota_modelo ORDER BY modelo_unidad ASC"
        );
    }

    /* ============================================================
       HISTORIAL DE UNA UNIDAD
       ============================================================ */
    public function historialUnidad(int $idFlota, array $filtros = [], int $limit = 10, int $offset = 0): array{
        $where  = "h.id_flota = :idflota";
        $params = ['idflota' => $idFlota];

        if (!empty($filtros['desde'])){
            $where .= " AND h.fecha >= :desde";
            $params['desde'] = $filtros['desde'] . ' 00:00:00';
        }
        if (!empty($filtros['hasta'])){
            $where .= " AND h.fecha <= :hasta";
            $params['hasta'] = $filtros['hasta'] . ' 23:59:59';
        }
        if (!empty($filtros['tipo'])){
            $where .= " AND h.tipo = :tipo";
            $params['tipo'] = $filtros['tipo'];
        }
        if (!empty($filtros['q'])){
            $where .= " AND h.texto_busqueda LIKE :q";
            $params['q'] = '%' . $filtros['q'] . '%';
        }

        $union = "
            SELECT
                CONVERT('despacho' USING utf8mb4) AS tipo,
                d.id_despacho AS id_evento,
                d.id_flota,
                d.fecha_despacho AS fecha,
                CONVERT(COALESCE(d.observacion, '') USING utf8mb4) AS detalle1,
                CONVERT(CONCAT('Despacho #', d.id_despacho, ' ', COALESCE(d.observacion, '')) USING utf8mb4) AS texto_busqueda,
                d.user_id AS usuario_id
            FROM table_alm_despacho d
            WHERE d.status_despacho = 1

            UNION ALL

            SELECT
                CONVERT('aceite' USING utf8mb4) AS tipo,
                ah.id_aceite_historial AS id_evento,
                ah.id_flota,
                ah.fecha_cambio AS fecha,
                CONVERT(CONCAT_WS('|',
                    ah.kilometraje_cambio,
                    ah.kilometraje_anterior,
                    COALESCE(ah.observaciones, '')
                ) USING utf8mb4) AS detalle1,
                CONVERT(CONCAT('Cambio aceite ', ah.kilometraje_cambio, ' km ', COALESCE(ah.observaciones, '')) USING utf8mb4) AS texto_busqueda,
                ah.usuario_id
            FROM table_flota_aceite_historial ah

            UNION ALL

            SELECT
                CONVERT('mantenimiento' USING utf8mb4) AS tipo,
                m.id_unidad_mantenimiento AS id_evento,
                m.id_flota,
                m.fecha_entrada AS fecha,
                CONVERT(CONCAT_WS('|',
                    COALESCE(m.tipo_mantenimiento, ''),
                    COALESCE(m.diagnostico, ''),
                    COALESCE(m.nomb_mecanico, ''),
                    COALESCE(m.recomendacion, '')
                ) USING utf8mb4) AS detalle1,
                CONVERT(CONCAT('Mantenimiento ', COALESCE(m.diagnostico, ''), ' ', COALESCE(m.nomb_mecanico, '')) USING utf8mb4) AS texto_busqueda,
                m.usuario_id
            FROM table_flota_mantenimiento m

            UNION ALL

            SELECT
                CONVERT('status' USING utf8mb4) AS tipo,
                s.idCambioStatus AS id_evento,
                s.id_flota,
                s.fechaCambio AS fecha,
                CONVERT(CONCAT_WS('|',
                    s.idstatus,
                    COALESCE(s.textCambio, '')
                ) USING utf8mb4) AS detalle1,
                CONVERT(CONCAT('Cambio estado ', COALESCE(s.textCambio, '')) USING utf8mb4) AS texto_busqueda,
                s.usuario_id
            FROM table_flota_status s
        ";

        $sqlCount = "SELECT COUNT(*) AS total FROM ({$union}) AS h WHERE {$where}";
        $total = (int)($this->fetchOne($sqlCount, $params)['total'] ?? 0);

        $sqlCounts = "SELECT h.tipo, COUNT(*) AS cant FROM ({$union}) AS h WHERE {$where} GROUP BY h.tipo";
        $rawCounts = $this->fetchAll($sqlCounts, $params);
        $counts = ['despacho' => 0, 'aceite' => 0, 'mantenimiento' => 0, 'status' => 0];
        foreach ($rawCounts as $r){
            $counts[$r['tipo']] = (int)$r['cant'];
        }

        $sqlItems = "SELECT h.*,
                            CONVERT(u.usuario_nick USING utf8mb4) AS usuario_nick,
                            CONVERT(p.personal_nombre USING utf8mb4) AS personal_nombre,
                            CONVERT(p.personal_apellido USING utf8mb4) AS personal_apellido
                     FROM ({$union}) AS h
                     LEFT JOIN table_usuarios u ON u.usuario_id = h.usuario_id
                     LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                     WHERE {$where}
                     ORDER BY h.fecha DESC, h.id_evento DESC
                     LIMIT {$limit} OFFSET {$offset}";
        $items = $this->fetchAll($sqlItems, $params);

        foreach ($items as &$item){
            $item['usuario_nombre'] = trim(($item['personal_nombre'] ?? '') . ' ' . ($item['personal_apellido'] ?? '')) ?: ($item['usuario_nick'] ?? 'Sistema');
            $item = $this->procesarDetalleHistorial($item);
        }
        unset($item);

        return [
            'items'  => $items,
            'total'  => $total,
            'counts' => $counts,
        ];
    }

    private function procesarDetalleHistorial(array $item): array{
        $partes = explode('|', (string)($item['detalle1'] ?? ''));

        switch ($item['tipo']){
            case 'despacho':
                $sql = "SELECT rd.cant_despacho,
                               CONVERT(p.producto USING utf8mb4) AS producto
                        FROM table_alm_relacion_despacho rd
                        LEFT JOIN table_alm_producto p ON p.id_producto = rd.id_producto
                        WHERE rd.id_despacho = :id";
                $articulos = $this->fetchAll($sql, ['id' => $item['id_evento']]);
                $item['articulos'] = $articulos;
                $item['titulo'] = "Despacho #" . $item['id_evento'];
                $item['descripcion'] = $item['detalle1'] ?? '';
                break;

            case 'aceite':
                $item['km_cambio']     = (int)($partes[0] ?? 0);
                $item['km_anterior']   = (int)($partes[1] ?? 0);
                $item['observaciones'] = $partes[2] ?? '';
                $item['titulo'] = "Cambio de Aceite";
                break;

            case 'mantenimiento':
                $item['tipo_mantenimiento'] = $partes[0] ?? '';
                $item['diagnostico']        = $partes[1] ?? '';
                $item['mecanico']           = $partes[2] ?? '';
                $item['recomendacion']      = $partes[3] ?? '';
                $item['titulo'] = "Mantenimiento #" . $item['id_evento'];
                break;

            case 'status':
                $item['status_codigo'] = (int)($partes[0] ?? 0);
                $item['motivo']        = $partes[1] ?? '';
                $item['titulo'] = "Cambio de Estado";
                break;
        }

        return $item;
    }

    /* ============================================================
       CAMBIO DE ACEITE
       ============================================================ */
    public function aceiteStatus(string $q = '', int $limit = 10, int $offset = 0, string $estado = '', int $inst = 0): array{
        $instWhere = "";
        $instParams = [];
        if ($inst > 0){
            $instWhere = " AND f.id_institucion = :inst";
            $instParams['inst'] = $inst;
        }

        $base = "
            SELECT
                f.id_flota,
                f.id_unidad,
                CONVERT(COALESCE(m.marca_unidad, '') USING utf8mb4) AS marca_unidad,
                CONVERT(COALESCE(mo.modelo_unidad, '') USING utf8mb4) AS modelo_unidad,
                COALESCE((
                    SELECT km.kilometraje_actual
                    FROM table_flota_kilometraje km
                    WHERE km.id_flota = f.id_flota
                    ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC
                    LIMIT 1
                ), 0) AS kilometraje_actual,
                COALESCE((
                    SELECT ah.kilometraje_cambio
                    FROM table_flota_aceite_historial ah
                    WHERE ah.id_flota = f.id_flota
                    ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC
                    LIMIT 1
                ), 0) AS ultimo_cambio_km,
                (
                    SELECT ah.fecha_cambio
                    FROM table_flota_aceite_historial ah
                    WHERE ah.id_flota = f.id_flota
                    ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC
                    LIMIT 1
                ) AS fecha_ultimo_cambio
            FROM table_flota f
            LEFT JOIN table_flota_marca m  ON m.id_marca  = f.id_marca
            LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
            WHERE f.status_unidad = 1 {$instWhere}
        ";

        $withEstado = "
            SELECT
                b.*,
                CASE
                    WHEN b.ultimo_cambio_km = 0 THEN 'Sin Registro'
                    WHEN (b.ultimo_cambio_km + 5000 - b.kilometraje_actual) <= 0 THEN 'Requerido'
                    WHEN (b.ultimo_cambio_km + 5000 - b.kilometraje_actual) <= 1000 THEN 'Próximo'
                    ELSE 'Bien'
                END AS estado,
                CASE WHEN b.ultimo_cambio_km = 0 THEN 0 ELSE (b.ultimo_cambio_km + 5000) END AS proximo_cambio_km,
                CASE WHEN b.ultimo_cambio_km = 0 THEN 0 ELSE (b.ultimo_cambio_km + 5000 - b.kilometraje_actual) END AS km_restantes
            FROM ({$base}) AS b
        ";

        $where  = "1=1";
        $params = [];

        if ($q !== ''){
            $where .= " AND (
                            u.id_unidad      LIKE :q1
                         OR u.marca_unidad   LIKE :q2
                         OR u.modelo_unidad  LIKE :q3
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        if ($estado !== ''){
            $where .= " AND u.estado = :estado";
            $params['estado'] = $estado;
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM ({$base}) AS x WHERE 1=1",
            $instParams
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM ({$withEstado}) AS u WHERE {$where}",
            array_merge($params, $instParams)
        )['t'] ?? 0);

        $sqlCounts = "
            SELECT u.estado, COUNT(*) AS cant
            FROM ({$withEstado}) AS u
            GROUP BY u.estado
        ";
        $rawCounts = $this->fetchAll($sqlCounts, $instParams);
        $counts = ['Requerido' => 0, 'Próximo' => 0, 'Bien' => 0, 'Sin Registro' => 0];
        foreach ($rawCounts as $r){
            if (isset($counts[$r['estado']])) $counts[$r['estado']] = (int)$r['cant'];
        }

        $sqlItems = "
            SELECT u.*
            FROM ({$withEstado}) AS u
            WHERE {$where}
            ORDER BY u.km_restantes ASC, u.id_unidad ASC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $data = $this->fetchAll($sqlItems, array_merge($params, $instParams));

        return [
            'data'           => $data,
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
            'counts'         => $counts,
        ];
    }

    public function actualizarKilometraje(int $idFlota, int $kilometraje, int $userId): bool{
        $sql = "INSERT INTO table_flota_kilometraje
                    (id_flota, kilometraje_actual, usuario_id, fecha_actualizacion)
                VALUES (:id, :km, :uid, NOW())";
        $stmt = $this->query($sql, ['id' => $idFlota, 'km' => $kilometraje, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public function registrarCambioAceite(int $idFlota, int $kmCambio, int $kmAnterior, string $fecha, int $userId): bool{
        $proximo = $kmCambio + 5000;

        $sql = "INSERT INTO table_flota_aceite_historial
                    (id_flota, fecha_cambio, kilometraje_cambio,
                     kilometraje_anterior, kilometraje_proximo_cambio, usuario_id)
                VALUES (:id, :fecha, :kmc, :kma, :kmp, :uid)";
        $stmt = $this->query($sql, [
            'id'    => $idFlota,
            'fecha' => $fecha,
            'kmc'   => $kmCambio,
            'kma'   => $kmAnterior,
            'kmp'   => $proximo,
            'uid'   => $userId,
        ]);

        if ($stmt->rowCount() > 0){
            $this->actualizarKilometraje($idFlota, $kmCambio, $userId);
            return true;
        }
        return false;
    }

    public function eliminarUltimoKilometraje(int $idFlota): bool{
        $row = $this->fetchOne(
            "SELECT id_kilometraje FROM table_flota_kilometraje
             WHERE id_flota = :id
             ORDER BY fecha_actualizacion DESC, id_kilometraje DESC
             LIMIT 1",
            ['id' => $idFlota]
        );
        if (!$row) return false;

        return $this->execute(
            "DELETE FROM table_flota_kilometraje WHERE id_kilometraje = :id",
            ['id' => $row['id_kilometraje']]
        );
    }

    public function eliminarUltimoCambioAceite(int $idFlota): bool{
        $row = $this->fetchOne(
            "SELECT id_aceite_historial FROM table_flota_aceite_historial
             WHERE id_flota = :id
             ORDER BY fecha_cambio DESC, id_aceite_historial DESC
             LIMIT 1",
            ['id' => $idFlota]
        );
        if (!$row) return false;

        return $this->execute(
            "DELETE FROM table_flota_aceite_historial WHERE id_aceite_historial = :id",
            ['id' => $row['id_aceite_historial']]
        );
    }
}
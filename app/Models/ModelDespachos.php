<?php
namespace App\Models;

use App\Core\Model;

class ModelDespachos extends Model{

    /* ============================================================
       LISTADO PAGINADO
       ============================================================ */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = [], int $inst = 0): array{
        $where  = "d.status_despacho = 1";
        $params = [];

        if ($inst > 0){
            $where .= " AND d.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            d.numero_orden LIKE :q1
                         OR f.id_unidad    LIKE :q2
                         OR p1.personal_nombre LIKE :q3
                         OR p1.personal_apellido LIKE :q4
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        if (!empty($filtros['fecha'])){
            $where .= " AND d.fecha_despacho = :fecha";
            $params['fecha'] = $filtros['fecha'];
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_alm_despacho WHERE status_despacho = 1 AND id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_alm_despacho d
             LEFT JOIN table_flota f ON f.id_flota = d.id_flota
             LEFT JOIN table_personal p1 ON p1.id_personal = d.operador_id
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT d.id_despacho, d.numero_orden, d.fecha_despacho,
                       d.operador_id, d.mecanico_id, d.despachador_id,
                       d.observacion, d.status_despacho, d.estado_orden,
                       d.id_institucion,
                       f.id_unidad, f.vim_unidad,
                       m.marca_unidad, mo.modelo_unidad,
                       (SELECT COUNT(*) FROM table_alm_relacion_despacho rd
                        WHERE rd.id_despacho = d.id_despacho) AS total_articulos,
                       CONCAT(p1.personal_nombre, ' ', p1.personal_apellido) AS operador_nombre,
                       CONCAT(p2.personal_nombre, ' ', p2.personal_apellido) AS mecanico_nombre,
                       CONCAT(p3.personal_nombre, ' ', p3.personal_apellido) AS despachador_nombre,
                       CONCAT(pu.personal_nombre, ' ', pu.personal_apellido) AS usuario_registro
                FROM table_alm_despacho d
                LEFT JOIN table_flota f ON f.id_flota = d.id_flota
                LEFT JOIN table_flota_marca m  ON m.id_marca = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                LEFT JOIN table_personal p1 ON p1.id_personal = d.operador_id
                LEFT JOIN table_personal p2 ON p2.id_personal = d.mecanico_id
                LEFT JOIN table_personal p3 ON p3.id_personal = d.despachador_id
                LEFT JOIN table_usuarios u  ON u.usuario_id = d.user_id
                LEFT JOIN table_personal pu ON pu.id_personal = u.usuario_id_personal
                WHERE {$where}
                ORDER BY d.id_despacho DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       FIND  (AGREGADO: usuario_registro / creado_por)
       ============================================================ */
    public function find(int $id): ?array{
        $sql = "SELECT d.*,
                       f.id_unidad, f.vim_unidad,
                       m.marca_unidad, mo.modelo_unidad,
                       CONCAT(p1.personal_nombre, ' ', p1.personal_apellido) AS operador_nombre,
                       CONCAT(p2.personal_nombre, ' ', p2.personal_apellido) AS mecanico_nombre,
                       CONCAT(p3.personal_nombre, ' ', p3.personal_apellido) AS despachador_nombre,
                       CONCAT(pu.personal_nombre, ' ', pu.personal_apellido) AS usuario_registro
                FROM table_alm_despacho d
                LEFT JOIN table_flota f ON f.id_flota = d.id_flota
                LEFT JOIN table_flota_marca m  ON m.id_marca = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                LEFT JOIN table_personal p1 ON p1.id_personal = d.operador_id
                LEFT JOIN table_personal p2 ON p2.id_personal = d.mecanico_id
                LEFT JOIN table_personal p3 ON p3.id_personal = d.despachador_id
                LEFT JOIN table_usuarios u  ON u.usuario_id = d.user_id
                LEFT JOIN table_personal pu ON pu.id_personal = u.usuario_id_personal
                WHERE d.id_despacho = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /* ============================================================
       ARTÍCULOS DEL DESPACHO
       ============================================================ */
    public function articulos(int $idDespacho): array{
        $sql = "SELECT rd.id_producto, rd.cant_despacho,
                       p.producto, p.present_producto,
                       u.ubicacion,
                       COALESCE(rp.cant_producto, 0) AS stock_actual
                FROM table_alm_relacion_despacho rd
                LEFT JOIN table_alm_producto p ON p.id_producto = rd.id_producto
                LEFT JOIN table_alm_ubicacion u ON u.id_ubicacion = p.id_ubicacion
                LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = rd.id_producto
                WHERE rd.id_despacho = :id";
        return $this->fetchAll($sql, ['id' => $idDespacho]);
    }

    /* ============================================================
       CREATE
       ============================================================ */
    public function create(array $d, int $userId, int $inst = 0): int{
        $inst = $inst > 0 ? $inst : DEFAULT_INSTITUCION;

        // Generar número de orden consecutivo por institución
        $last = $this->fetchOne(
            "SELECT MAX(numero_orden) AS ult FROM table_alm_despacho WHERE id_institucion = :inst",
            ['inst' => $inst]
        );
        $numeroOrden = (int)($last['ult'] ?? 0) + 1;

        $this->db->beginTransaction();
        try{
            // 1. Cabecera
            $sql = "INSERT INTO table_alm_despacho
                        (numero_orden, id_flota, operador_id, mecanico_id, despachador_id,
                         fecha_despacho, user_id, observacion, status_despacho, estado_orden, id_institucion)
                    VALUES
                        (:num, :flota, :op, :mec, :desp,
                         :fecha, :uid, :obs, 1, 3, :inst)";
            $this->query($sql, [
                'num'    => $numeroOrden,
                'flota'  => (int)$d['id_flota'],
                'op'     => (int)$d['operador_id'],
                'mec'    => (int)$d['mecanico_id'],
                'desp'   => (int)$d['despachador_id'],
                'fecha'  => $d['fecha_despacho'],
                'uid'    => $userId,
                'obs'    => $d['observacion'] ?? '',
                'inst'   => $inst,
            ]);
            $idDespacho = (int)$this->lastInsertId();

            // 2. Artículos
            if (!empty($d['articulos'])){
                foreach ($d['articulos'] as $art){
                    $idProd = (int)$art['id_producto'];
                    $cant   = (float)$art['cantidad'];

                    $this->query(
                        "INSERT INTO table_alm_relacion_despacho
                            (id_despacho, id_producto, cant_despacho, id_institucion)
                         VALUES (:desp, :prod, :cant, :inst)",
                        ['desp' => $idDespacho, 'prod' => $idProd, 'cant' => $cant, 'inst' => $inst]
                    );

                    $this->query(
                        "UPDATE table_alm_relacion_producto
                         SET cant_producto = cant_producto - :cant
                         WHERE id_producto = :prod",
                        ['cant' => $cant, 'prod' => $idProd]
                    );

                    $this->query(
                        "INSERT INTO table_compras_pendientes
                            (id_despacho, id_producto, id_flota, cant_despacho, fecha_despacho, status_costeo, id_institucion)
                         VALUES (:desp, :prod, :flota, :cant, :fecha, 'Pendiente', :inst)",
                        [
                            'desp'  => $idDespacho,
                            'prod'  => $idProd,
                            'flota' => (int)$d['id_flota'],
                            'cant'  => $cant,
                            'fecha' => $d['fecha_despacho'],
                            'inst'  => $inst,
                        ]
                    );
                }
            }

            $this->db->commit();
            return $idDespacho;
        } catch (\Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       UPDATE (revertir + reinsertar)
       ============================================================ */
    public function update(int $id, array $d): bool{
        $actual = $this->find($id);
        if (!$actual) return false;

        $this->db->beginTransaction();
        try{
            $artsActuales = $this->articulos($id);
            foreach ($artsActuales as $art){
                $this->query(
                    "UPDATE table_alm_relacion_producto
                     SET cant_producto = cant_producto + :cant
                     WHERE id_producto = :prod",
                    ['cant' => (float)$art['cant_despacho'], 'prod' => (int)$art['id_producto']]
                );
            }

            $this->query("DELETE FROM table_alm_relacion_despacho WHERE id_despacho = :id", ['id' => $id]);
            $this->query("DELETE FROM table_compras_pendientes WHERE id_despacho = :id", ['id' => $id]);

            $sql = "UPDATE table_alm_despacho SET
                        id_flota        = :flota,
                        operador_id     = :op,
                        mecanico_id     = :mec,
                        despachador_id  = :desp,
                        fecha_despacho  = :fecha,
                        observacion     = :obs
                    WHERE id_despacho = :id";
            $this->query($sql, [
                'flota'  => (int)$d['id_flota'],
                'op'     => (int)$d['operador_id'],
                'mec'    => (int)$d['mecanico_id'],
                'desp'   => (int)$d['despachador_id'],
                'fecha'  => $d['fecha_despacho'],
                'obs'    => $d['observacion'] ?? '',
                'id'     => $id,
            ]);

            if (!empty($d['articulos'])){
                foreach ($d['articulos'] as $art){
                    $idProd = (int)$art['id_producto'];
                    $cant   = (float)$art['cantidad'];

                    $this->query(
                        "INSERT INTO table_alm_relacion_despacho
                            (id_despacho, id_producto, cant_despacho, id_institucion)
                         VALUES (:desp, :prod, :cant, :inst)",
                        [
                            'desp' => $id,
                            'prod' => $idProd,
                            'cant' => $cant,
                            'inst' => (int)$actual['id_institucion'],
                        ]
                    );

                    $this->query(
                        "UPDATE table_alm_relacion_producto
                         SET cant_producto = cant_producto - :cant
                         WHERE id_producto = :prod",
                        ['cant' => $cant, 'prod' => $idProd]
                    );

                    $this->query(
                        "INSERT INTO table_compras_pendientes
                            (id_despacho, id_producto, id_flota, cant_despacho, fecha_despacho, status_costeo, id_institucion)
                         VALUES (:desp, :prod, :flota, :cant, :fecha, 'Pendiente', :inst)",
                        [
                            'desp'  => $id,
                            'prod'  => $idProd,
                            'flota' => (int)$d['id_flota'],
                            'cant'  => $cant,
                            'fecha' => $d['fecha_despacho'],
                            'inst'  => (int)$actual['id_institucion'],
                        ]
                    );
                }
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       DELETE (revertir stock + soft delete)
       ============================================================ */
    public function delete(int $id, string $motivo, int $userId): bool{
        $actual = $this->find($id);
        if (!$actual) return false;

        $this->db->beginTransaction();
        try{
            $arts = $this->articulos($id);
            foreach ($arts as $art){
                $this->query(
                    "UPDATE table_alm_relacion_producto
                     SET cant_producto = cant_producto + :cant
                     WHERE id_producto = :prod",
                    ['cant' => (float)$art['cant_despacho'], 'prod' => (int)$art['id_producto']]
                );
            }

            $this->query(
                "UPDATE table_alm_despacho SET status_despacho = 0 WHERE id_despacho = :id",
                ['id' => $id]
            );

            $this->query(
                "INSERT INTO table_alm_historial_cambio (obs, info, id_user)
                 VALUES (:obs, :info, :uid)",
                [
                    'obs'  => $motivo,
                    'info' => "ELIMINÓ DESPACHO #{$actual['numero_orden']}",
                    'uid'  => $userId,
                ]
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       STATS del mes
       ============================================================ */
    public function statsMes(int $inst = 0): array{
        $where  = "status_despacho = 1 AND DATE_FORMAT(fecha_despacho, '%Y-%m') = :mes";
        $params = ['mes' => date('Y-m')];

        if ($inst > 0){
            $where .= " AND id_institucion = :inst";
            $params['inst'] = $inst;
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_alm_despacho WHERE {$where}",
            $params
        )['t'] ?? 0);

        return [
            'actual'     => $total,
            'meta'       => 120,
            'porcentaje' => min(100, round(($total / 120) * 100)),
        ];
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function flota(int $inst = 0): array{
        $sql = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.status_unidad,
                    m.marca_unidad, mo.modelo_unidad
                FROM table_flota f
                LEFT JOIN table_flota_marca m  ON m.id_marca = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                WHERE f.status_unidad <> 0";
        $params = [];
        if ($inst > 0){
            $sql .= " AND f.id_institucion = :inst";
            $params['inst'] = $inst;
        }
        $sql .= " ORDER BY f.id_unidad ASC";
        return $this->fetchAll($sql, $params);
    }

    public function operadores(): array{
        return $this->fetchAll(
            "SELECT p.id_personal, p.personal_cedula, p.personal_nombre, p.personal_apellido
             FROM table_personal p
             WHERE p.personal_cargo = 23 AND p.personal_status <> 0
             ORDER BY p.personal_cedula DESC"
        );
    }

    public function mecanicos(): array{
        return $this->fetchAll(
            "SELECT p.id_personal, p.personal_cedula, p.personal_nombre, p.personal_apellido
             FROM table_personal p
             WHERE (p.personal_cargo BETWEEN 26 AND 29 OR p.personal_cargo = 32 OR p.personal_cargo = 43)
               AND p.personal_status <> 0
             ORDER BY p.personal_cedula DESC"
        );
    }

    public function despachadores(): array{
        return $this->fetchAll(
            "SELECT p.id_personal, p.personal_cedula, p.personal_nombre, p.personal_apellido
             FROM table_personal p
             WHERE p.personal_tag = 2 AND p.personal_status <> 0
             ORDER BY p.personal_cedula DESC"
        );
    }

    public function articulosDisponibles(int $inst = 0): array{
        $sql = "SELECT p.id_producto, p.producto, p.present_producto,
                       rp.cant_producto,
                       u.ubicacion
                FROM table_alm_producto p
                INNER JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                LEFT JOIN table_alm_ubicacion u ON u.id_ubicacion = p.id_ubicacion
                WHERE p.status_producto = 1 AND rp.cant_producto > 0";
        $params = [];
        if ($inst > 0){
            $sql .= " AND p.id_institucion = :inst";
            $params['inst'] = $inst;
        }
        $sql .= " ORDER BY p.producto ASC";
        return $this->fetchAll($sql, $params);
    }

    /* ============================================================
       HISTORIAL DE PRODUCTOS (resumen)
       ============================================================ */
    public function historialProductos(string $q = '', int $limit = 10, int $offset = 0, int $inst = 0): array{
        $where  = "p.status_producto = 1";
        $params = [];

        if ($inst > 0){
            $where .= " AND p.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            p.producto        LIKE :q1
                         OR e.enlace_producto LIKE :q2
                         OR u.ubicacion       LIKE :q3
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_alm_producto WHERE status_producto = 1 AND id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_alm_producto p
             LEFT JOIN table_alm_enlace_producto e ON e.id_enlace_producto = p.id_enlace_producto
             LEFT JOIN table_alm_ubicacion u       ON u.id_ubicacion = p.id_ubicacion
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT p.id_producto, p.producto, p.present_producto,
                       COALESCE(rp.cant_producto, 0) AS stock_actual,
                       e.enlace_producto,
                       u.ubicacion,
                       COALESCE((
                           SELECT SUM(rd.cant_despacho)
                           FROM table_alm_relacion_despacho rd
                           INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                           WHERE rd.id_producto = p.id_producto
                             AND d.status_despacho = 1
                       ), 0) AS total_despachado,
                       (
                           SELECT MIN(d.fecha_despacho)
                           FROM table_alm_relacion_despacho rd
                           INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                           WHERE rd.id_producto = p.id_producto
                             AND d.status_despacho = 1
                       ) AS primer_despacho,
                       (
                           SELECT MAX(d.fecha_despacho)
                           FROM table_alm_relacion_despacho rd
                           INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                           WHERE rd.id_producto = p.id_producto
                             AND d.status_despacho = 1
                       ) AS ultimo_despacho
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                LEFT JOIN table_alm_enlace_producto e    ON e.id_enlace_producto = p.id_enlace_producto
                LEFT JOIN table_alm_ubicacion u          ON u.id_ubicacion = p.id_ubicacion
                WHERE {$where}
                ORDER BY p.producto ASC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       HISTORIAL DETALLE de un producto (timeline paginado)
       ============================================================ */
    public function historialDetalle(int $idProducto, int $limit = 10, int $offset = 0): array{
        $producto = $this->fetchOne(
            "SELECT p.id_producto, p.producto, p.present_producto,
                    COALESCE(rp.cant_producto, 0) AS stock_actual,
                    COALESCE((SELECT SUM(rd2.cant_despacho) FROM table_alm_relacion_despacho rd2
                              INNER JOIN table_alm_despacho d2 ON d2.id_despacho = rd2.id_despacho
                              WHERE rd2.id_producto = p.id_producto AND d2.status_despacho = 1), 0) AS total_despachado
             FROM table_alm_producto p
             LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
             WHERE p.id_producto = :id
             LIMIT 1",
            ['id' => $idProducto]
        );

        if (!$producto) return ['producto' => null, 'data' => [], 'total' => 0];

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_alm_relacion_despacho rd
             INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
             WHERE rd.id_producto = :id AND d.status_despacho = 1",
            ['id' => $idProducto]
        )['t'] ?? 0);

        $sql = "SELECT d.id_despacho, d.numero_orden, d.fecha_despacho,
                       d.observacion,
                       rd.cant_despacho,
                       f.id_unidad, f.vim_unidad,
                       m.marca_unidad, mo.modelo_unidad,
                       CONCAT(p1.personal_nombre, ' ', p1.personal_apellido) AS operador_nombre,
                       CONCAT(p2.personal_nombre, ' ', p2.personal_apellido) AS mecanico_nombre,
                       CONCAT(p3.personal_nombre, ' ', p3.personal_apellido) AS despachador_nombre
                FROM table_alm_relacion_despacho rd
                INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                LEFT JOIN table_flota f ON f.id_flota = d.id_flota
                LEFT JOIN table_flota_marca m  ON m.id_marca = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                LEFT JOIN table_personal p1 ON p1.id_personal = d.operador_id
                LEFT JOIN table_personal p2 ON p2.id_personal = d.mecanico_id
                LEFT JOIN table_personal p3 ON p3.id_personal = d.despachador_id
                WHERE rd.id_producto = :id AND d.status_despacho = 1
                ORDER BY d.fecha_despacho DESC, d.id_despacho DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'producto' => $producto,
            'data'     => $this->fetchAll($sql, ['id' => $idProducto]),
            'total'    => $total,
        ];
    }
}
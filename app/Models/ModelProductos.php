<?php
namespace App\Models;

use App\Core\Model;

class ModelProductos extends Model{

    /* ============================================================
       LISTADO PAGINADO
       ============================================================ */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = [], int $inst = 0): array{
        $where  = "p.status_producto = 1";
        $params = [];

        if ($inst > 0){
            $where .= " AND p.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            p.producto           LIKE :q1
                         OR e.enlace_producto    LIKE :q2
                         OR prov.empresa_proveedor LIKE :q3
                         OR u.ubicacion          LIKE :q4
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        if (!empty($filtros['enlace'])){
            $where .= " AND p.id_enlace_producto = :enlace";
            $params['enlace'] = (int)$filtros['enlace'];
        }
        if (!empty($filtros['ubicacion'])){
            $where .= " AND p.id_ubicacion = :ubicacion";
            $params['ubicacion'] = (int)$filtros['ubicacion'];
        }
        if (isset($filtros['stock']) && $filtros['stock'] !== ''){
            if ($filtros['stock'] === 'sin'){
                $where .= " AND COALESCE(rel.cant_producto, 0) <= 0";
            } elseif ($filtros['stock'] === 'bajo'){
                $where .= " AND COALESCE(rel.cant_producto, 0) > 0 AND COALESCE(rel.cant_producto, 0) < 10";
            } elseif ($filtros['stock'] === 'ok'){
                $where .= " AND COALESCE(rel.cant_producto, 0) >= 10";
            }
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_alm_producto
             WHERE status_producto = 1 AND id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_alm_producto p
             LEFT JOIN table_alm_relacion_producto rel ON rel.id_producto = p.id_producto
             LEFT JOIN table_proveedor prov            ON prov.id_proveedor = rel.id_proveedor
             LEFT JOIN table_alm_ubicacion u           ON u.id_ubicacion   = p.id_ubicacion
             LEFT JOIN table_alm_enlace_producto e     ON e.id_enlace_producto = p.id_enlace_producto
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT p.id_producto, p.producto, p.present_producto, p.tag_producto,
                       p.id_ubicacion, p.id_enlace_producto, p.id_institucion,
                       e.enlace_producto,
                       u.ubicacion,
                       prov.id_proveedor, prov.empresa_proveedor,
                       COALESCE(rel.cant_producto, 0) AS cant_producto
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rel ON rel.id_producto = p.id_producto
                LEFT JOIN table_proveedor prov            ON prov.id_proveedor = rel.id_proveedor
                LEFT JOIN table_alm_ubicacion u           ON u.id_ubicacion   = p.id_ubicacion
                LEFT JOIN table_alm_enlace_producto e     ON e.id_enlace_producto = p.id_enlace_producto
                WHERE {$where}
                ORDER BY p.id_producto DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       FIND
       ============================================================ */
    public function find(int $id): ?array{
        $sql = "SELECT p.*,
                       e.enlace_producto,
                       u.ubicacion,
                       prov.id_proveedor, prov.empresa_proveedor,
                       COALESCE(rel.cant_producto, 0) AS cant_producto
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rel ON rel.id_producto = p.id_producto
                LEFT JOIN table_proveedor prov            ON prov.id_proveedor = rel.id_proveedor
                LEFT JOIN table_alm_ubicacion u           ON u.id_ubicacion   = p.id_ubicacion
                LEFT JOIN table_alm_enlace_producto e     ON e.id_enlace_producto = p.id_enlace_producto
                WHERE p.id_producto = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /* ============================================================
       CREATE
       ============================================================ */
    public function create(array $d, int $inst = 0): int{
        $inst = $inst > 0 ? $inst : DEFAULT_INSTITUCION;

        // Verificar duplicado en la misma institución
        $existe = $this->fetchOne(
            "SELECT id_producto FROM table_alm_producto
             WHERE producto = :p AND id_enlace_producto = :e AND id_institucion = :i
             LIMIT 1",
            ['p' => $d['producto'], 'e' => (int)$d['id_enlace_producto'], 'i' => $inst]
        );
        if ($existe) return 0;

        // Insertar producto
        $sql = "INSERT INTO table_alm_producto
                    (id_enlace_producto, id_ubicacion, producto,
                     tag_producto, present_producto, status_producto, id_institucion)
                VALUES
                    (:enlace, :ubicacion, :producto,
                     :tag, :present, 1, :inst)";
        $this->query($sql, [
            'enlace'    => (int)$d['id_enlace_producto'],
            'ubicacion' => (int)$d['id_ubicacion'],
            'producto'  => $d['producto'],
            'tag'       => (int)($d['tag_producto'] ?? 1),
            'present'   => $d['present_producto'] ?? 'UNIDAD',
            'inst'      => $inst,
        ]);
        $id = (int)$this->lastInsertId();

        // Insertar relación (stock inicial + proveedor)
        $sqlRel = "INSERT INTO table_alm_relacion_producto
                      (id_producto, id_proveedor, cant_producto, id_institucion)
                   VALUES
                      (:prod, :prov, :cant, :inst)";
        $this->query($sqlRel, [
            'prod' => $id,
            'prov' => (int)$d['id_proveedor'],
            'cant' => (float)($d['cant_producto'] ?? 0),
            'inst' => $inst,
        ]);

        return $id;
    }

    /* ============================================================
       UPDATE
       ============================================================ */
    public function update(int $id, array $d): bool{
        $actual = $this->find($id);
        if (!$actual) return false;

        // Verificar duplicado (excluyendo el actual)
        $existe = $this->fetchOne(
            "SELECT id_producto FROM table_alm_producto
             WHERE producto = :p AND id_enlace_producto = :e
               AND id_institucion = :i AND id_producto <> :id
             LIMIT 1",
            ['p' => $d['producto'], 'e' => (int)$d['id_enlace_producto'],
             'i' => (int)$actual['id_institucion'], 'id' => $id]
        );
        if ($existe) return false;

        $sql = "UPDATE table_alm_producto SET
                    id_enlace_producto = :enlace,
                    id_ubicacion       = :ubicacion,
                    producto           = :producto,
                    tag_producto       = :tag,
                    present_producto   = :present
                WHERE id_producto = :id";
        $this->query($sql, [
            'enlace'    => (int)$d['id_enlace_producto'],
            'ubicacion' => (int)$d['id_ubicacion'],
            'producto'  => $d['producto'],
            'tag'       => (int)($d['tag_producto'] ?? 1),
            'present'   => $d['present_producto'] ?? 'UNIDAD',
            'id'        => $id,
        ]);

        // Actualizar proveedor en la relación (si cambió)
        if (!empty($d['id_proveedor'])){
            $this->query(
                "UPDATE table_alm_relacion_producto
                 SET id_proveedor = :prov
                 WHERE id_producto = :id",
                ['prov' => (int)$d['id_proveedor'], 'id' => $id]
            );
        }

        return true;
    }

    /* ============================================================
       AGREGAR STOCK
       ============================================================ */
    public function agregarStock(int $id, float $cantidad): bool{
        $rel = $this->fetchOne(
            "SELECT id_producto, cant_producto FROM table_alm_relacion_producto
             WHERE id_producto = :id LIMIT 1",
            ['id' => $id]
        );

        if (!$rel){
            return $this->execute(
                "INSERT INTO table_alm_relacion_producto (id_producto, id_proveedor, cant_producto, id_institucion)
                 VALUES (:id, 1, :c, 1)",
                ['id' => $id, 'c' => $cantidad]
            );
        }

        return $this->execute(
            "UPDATE table_alm_relacion_producto
             SET cant_producto = cant_producto + :c
             WHERE id_producto = :id",
            ['c' => $cantidad, 'id' => $id]
        );
    }

    /* ============================================================
       DELETE (lógico)
       ============================================================ */
    public function delete(int $id): bool{
        return $this->execute(
            "UPDATE table_alm_producto SET status_producto = 0 WHERE id_producto = :id",
            ['id' => $id]
        );
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function enlaces(): array{
        return $this->fetchAll(
            "SELECT id_enlace_producto AS id, enlace_producto AS nombre
             FROM table_alm_enlace_producto
             ORDER BY enlace_producto ASC"
        );
    }

    public function proveedores(): array{
        return $this->fetchAll(
            "SELECT id_proveedor AS id, empresa_proveedor AS nombre
             FROM table_proveedor
             WHERE status_proveedor = 1
             ORDER BY empresa_proveedor ASC"
        );
    }

    public function ubicaciones(): array{
        return $this->fetchAll(
            "SELECT id_ubicacion AS id, ubicacion AS nombre
             FROM table_alm_ubicacion
             ORDER BY ubicacion ASC"
        );
    }
        /* ============================================================
       PRODUCTOS AGRUPADOS POR ESTADO DE STOCK
       ============================================================ */
    public function productosPorEstadoStock(int $inst = 0): array{
        $where  = "p.status_producto = 1";
        $params = [];

        if ($inst > 0){
            $where .= " AND p.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        $sql = "SELECT p.id_producto, p.producto, p.present_producto, p.tag_producto,
                       e.enlace_producto,
                       u.ubicacion,
                       prov.empresa_proveedor,
                       COALESCE(rel.cant_producto, 0) AS cant_producto
                FROM table_alm_producto p
                LEFT JOIN table_alm_relacion_producto rel ON rel.id_producto = p.id_producto
                LEFT JOIN table_proveedor prov            ON prov.id_proveedor = rel.id_proveedor
                LEFT JOIN table_alm_ubicacion u           ON u.id_ubicacion   = p.id_ubicacion
                LEFT JOIN table_alm_enlace_producto e     ON e.id_enlace_producto = p.id_enlace_producto
                WHERE {$where}
                ORDER BY p.producto ASC";

        $rows = $this->fetchAll($sql, $params);

        $grupos = [
            'Sin stock'  => [],
            'Stock bajo' => [],
            'Con stock'  => [],
        ];

        foreach ($rows as $r){
            $cant = (float)($r['cant_producto'] ?? 0);
            if ($cant <= 0){
                $grupos['Sin stock'][] = $r;
            } elseif ($cant < 10){
                $grupos['Stock bajo'][] = $r;
            } else {
                $grupos['Con stock'][] = $r;
            }
        }

        return $grupos;
    }
}
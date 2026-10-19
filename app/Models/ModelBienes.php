<?php
namespace App\Models;

use App\Core\Model;

class ModelBienes extends Model{

    /* ============================================================
       LISTADO PAGINADO (filtra por institución)
       ============================================================ */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = [], int $inst = 0): array{
        $where  = "1=1";
        $params = [];

        if ($inst > 0){
            $where .= " AND b.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            b.descripcion_bien  LIKE :q1
                         OR b.seccion_id        LIKE :q2
                         OR d.departamento_bien LIKE :q3
                         OR g.grupo             LIKE :q4
                         OR s.subgrupo          LIKE :q5
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $params['q5'] = $like;
        }

        if (!empty($filtros['departamento'])){
            $where .= " AND b.bien_depatamento_id = :depto";
            $params['depto'] = $filtros['departamento'];
        }
        if (!empty($filtros['grupo'])){
            $where .= " AND b.grupo_id = :grupo";
            $params['grupo'] = $filtros['grupo'];
        }
        if (!empty($filtros['status'])){
            $where .= " AND b.status_bien = :status";
            $params['status'] = $filtros['status'];
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_bienes_inventario WHERE id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_bienes_inventario b
             LEFT JOIN table_bienes_departamentos d ON d.depatamento_bien_id = b.bien_depatamento_id
             LEFT JOIN table_bienes_grupo g         ON g.id_grupo  = b.grupo_id
             LEFT JOIN table_bienes_subgrupo s      ON s.subgrupo_id = b.subgrupo_id
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT b.id_bien, b.bien_depatamento_id, b.grupo_id, b.subgrupo_id,
                       b.seccion_id, b.descripcion_bien, b.fecha_adquisicion,
                       b.status_bien, b.fecha_creacion, b.fecha_actualizacion,
                       b.user_id, b.id_institucion,
                       d.departamento_bien,
                       g.grupo,
                       s.subgrupo
                FROM table_bienes_inventario b
                LEFT JOIN table_bienes_departamentos d ON d.depatamento_bien_id = b.bien_depatamento_id
                LEFT JOIN table_bienes_grupo g         ON g.id_grupo  = b.grupo_id
                LEFT JOIN table_bienes_subgrupo s      ON s.subgrupo_id = b.subgrupo_id
                WHERE {$where}
                ORDER BY b.id_bien DESC
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
        $sql = "SELECT b.*, d.departamento_bien, g.grupo, s.subgrupo
                FROM table_bienes_inventario b
                LEFT JOIN table_bienes_departamentos d ON d.depatamento_bien_id = b.bien_depatamento_id
                LEFT JOIN table_bienes_grupo g         ON g.id_grupo  = b.grupo_id
                LEFT JOIN table_bienes_subgrupo s      ON s.subgrupo_id = b.subgrupo_id
                WHERE b.id_bien = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /* ============================================================
       CREATE
       ============================================================ */
    public function create(array $d, int $userId, int $inst = 0): int{
        $sql = "INSERT INTO table_bienes_inventario
                    (bien_depatamento_id, grupo_id, subgrupo_id, seccion_id,
                     descripcion_bien, fecha_adquisicion, status_bien,
                     user_id, org, edo, status, id_institucion)
                VALUES
                    (:depto, :grupo, :subgrupo, :seccion,
                     :descripcion, :fecha, :status,
                     :user_id, 0, 0, 1, :inst)";
        $this->query($sql, [
            'depto'       => $d['bien_depatamento_id'],
            'grupo'       => !empty($d['grupo_id'])     ? $d['grupo_id']     : null,
            'subgrupo'    => !empty($d['subgrupo_id'])  ? $d['subgrupo_id']  : null,
            'seccion'     => $d['seccion_id'] ?? '',
            'descripcion' => $d['descripcion_bien'] ?? '',
            'fecha'       => $d['fecha_adquisicion'] ?? null,
            'status'      => $d['status_bien'] ?? '1',
            'user_id'     => $userId,
            'inst'        => $inst > 0 ? $inst : DEFAULT_INSTITUCION,
        ]);
        return (int)$this->lastInsertId();
    }

    /* ============================================================
       UPDATE
       ============================================================ */
    public function update(int $id, array $d): bool{
        $sql = "UPDATE table_bienes_inventario SET
                    bien_depatamento_id = :depto,
                    grupo_id            = :grupo,
                    subgrupo_id         = :subgrupo,
                    seccion_id          = :seccion,
                    descripcion_bien    = :descripcion,
                    fecha_adquisicion   = :fecha,
                    status_bien         = :status
                WHERE id_bien = :id";
        return $this->execute($sql, [
            'depto'       => $d['bien_depatamento_id'],
            'grupo'       => !empty($d['grupo_id'])     ? $d['grupo_id']     : null,
            'subgrupo'    => !empty($d['subgrupo_id'])  ? $d['subgrupo_id']  : null,
            'seccion'     => $d['seccion_id'] ?? '',
            'descripcion' => $d['descripcion_bien'] ?? '',
            'fecha'       => $d['fecha_adquisicion'] ?? null,
            'status'      => $d['status_bien'] ?? '1',
            'id'          => $id,
        ]);
    }

    /* ============================================================
       DELETE
       ============================================================ */
    public function delete(int $id): bool{
        return $this->execute("DELETE FROM table_bienes_inventario WHERE id_bien = :id", ['id' => $id]);
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function departamentos(): array{
        return $this->fetchAll(
            "SELECT depatamento_bien_id AS id, departamento_bien AS nombre
             FROM table_bienes_departamentos
             WHERE departamento_status = 1 OR departamento_status IS NULL
             ORDER BY departamento_bien ASC"
        );
    }

    public function grupos(): array{
        return $this->fetchAll(
            "SELECT id_grupo AS id, grupo AS nombre
             FROM table_bienes_grupo
             WHERE grupo_status = 1 OR grupo_status IS NULL
             ORDER BY grupo ASC"
        );
    }

    public function subgrupos(): array{
        return $this->fetchAll(
            "SELECT subgrupo_id AS id, subgrupo AS nombre
             FROM table_bienes_subgrupo
             WHERE subgrupo_status = 1 OR subgrupo_status IS NULL
             ORDER BY subgrupo ASC"
        );
    }

    public function secciones(): array{
        return $this->fetchAll(
            "SELECT DISTINCT seccion_id AS id, seccion AS nombre
             FROM table_bienes_seccion
             WHERE seccion_id IS NOT NULL AND seccion_id <> ''
             ORDER BY seccion ASC"
        );
    }
}
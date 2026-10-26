<?php
namespace App\Models;

use App\Core\Model;

class ModelCompras extends Model{

    /* ============================================================
       PENDIENTES de costeo
       ============================================================ */
    public function pendientes(string $q = '', int $limit = 10, int $offset = 0, int $inst = 0): array{
        $where  = "cp.status_costeo = 'Pendiente'";
        $params = [];

        if ($inst > 0){
            $where .= " AND cp.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            cp.id_despacho LIKE :q1
                         OR f.id_unidad    LIKE :q2
                         OR fm.modelo_unidad LIKE :q3
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(DISTINCT cp.id_despacho) AS t
             FROM table_compras_pendientes cp
             WHERE cp.status_costeo = 'Pendiente' AND cp.id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(DISTINCT cp.id_despacho) AS t
             FROM table_compras_pendientes cp
             INNER JOIN table_alm_despacho d ON d.id_despacho = cp.id_despacho
             INNER JOIN table_flota f ON f.id_flota = cp.id_flota
             INNER JOIN table_flota_modelo fm ON fm.id_modelo = f.id_modelo
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT cp.id_despacho,
                       d.fecha_despacho,
                       d.numero_orden,
                       f.id_unidad,
                       fm.modelo_unidad,
                       m.marca_unidad,
                       COUNT(cp.id_compra_pendiente) AS articulos_pendientes,
                       COALESCE(SUM(cp.cant_despacho), 0) AS total_unidades
                FROM table_compras_pendientes cp
                INNER JOIN table_alm_despacho d ON d.id_despacho = cp.id_despacho
                INNER JOIN table_flota f ON f.id_flota = cp.id_flota
                INNER JOIN table_flota_modelo fm ON fm.id_modelo = f.id_modelo
                LEFT JOIN table_flota_marca m ON m.id_marca = f.id_marca
                WHERE {$where}
                GROUP BY cp.id_despacho, d.fecha_despacho, d.numero_orden,
                         f.id_unidad, fm.modelo_unidad, m.marca_unidad
                ORDER BY d.fecha_despacho DESC, cp.id_despacho DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       COSTEADAS
       ============================================================ */
    public function costeadas(string $q = '', int $limit = 10, int $offset = 0, array $filtros = [], int $inst = 0): array{
        $where  = "cp.status_costeo = 'Costeado'";
        $params = [];

        if ($inst > 0){
            $where .= " AND cp.id_institucion = :inst";
            $params['inst'] = $inst;
        }

        if ($q !== ''){
            $where .= " AND (
                            cp.id_despacho LIKE :q1
                         OR f.id_unidad    LIKE :q2
                         OR fm.modelo_unidad LIKE :q3
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        if (!empty($filtros['fecha_desde'])){
            $where .= " AND d.fecha_despacho >= :fdesde";
            $params['fdesde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])){
            $where .= " AND d.fecha_despacho <= :fhasta";
            $params['fhasta'] = $filtros['fecha_hasta'];
        }

        $total = (int)($this->fetchOne(
            "SELECT COUNT(DISTINCT cp.id_despacho) AS t
             FROM table_compras_pendientes cp
             WHERE cp.status_costeo = 'Costeado' AND cp.id_institucion = :inst",
            ['inst' => $inst]
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(DISTINCT cp.id_despacho) AS t
             FROM table_compras_pendientes cp
             INNER JOIN table_alm_despacho d ON d.id_despacho = cp.id_despacho
             INNER JOIN table_flota f ON f.id_flota = cp.id_flota
             INNER JOIN table_flota_modelo fm ON fm.id_modelo = f.id_modelo
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT cp.id_despacho,
                       d.fecha_despacho,
                       d.numero_orden,
                       f.id_unidad,
                       fm.modelo_unidad,
                       m.marca_unidad,
                       COUNT(cc.id_costo) AS articulos_costeados,
                       COALESCE(SUM(cc.monto_divisa), 0) AS total_divisa,
                       COALESCE(SUM(cc.monto_bs), 0) AS total_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cc.id_compra_pendiente = cp.id_compra_pendiente
                INNER JOIN table_alm_despacho d ON d.id_despacho = cp.id_despacho
                INNER JOIN table_flota f ON f.id_flota = cp.id_flota
                INNER JOIN table_flota_modelo fm ON fm.id_modelo = f.id_modelo
                LEFT JOIN table_flota_marca m ON m.id_marca = f.id_marca
                WHERE {$where}
                GROUP BY cp.id_despacho, d.fecha_despacho, d.numero_orden,
                         f.id_unidad, fm.modelo_unidad, m.marca_unidad
                ORDER BY d.fecha_despacho DESC, cp.id_despacho DESC
                LIMIT {$limit} OFFSET {$offset}";

        return [
            'data'           => $this->fetchAll($sql, $params),
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       INFO de un despacho
       ============================================================ */
    public function infoDespacho(int $id): ?array{
        $sql = "SELECT d.id_despacho, d.numero_orden, d.fecha_despacho,
                       f.id_unidad, f.vim_unidad,
                       m.marca_unidad, mo.modelo_unidad
                FROM table_alm_despacho d
                INNER JOIN table_flota f ON f.id_flota = d.id_flota
                INNER JOIN table_flota_marca m ON m.id_marca = f.id_marca
                INNER JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                WHERE d.id_despacho = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /* ============================================================
       ARTÍCULOS PENDIENTES de un despacho
       ============================================================ */
    public function articulosPendientes(int $idDespacho): array{
        $sql = "SELECT cp.id_compra_pendiente,
                       cp.cant_despacho,
                       cp.id_producto,
                       p.producto, p.present_producto
                FROM table_compras_pendientes cp
                INNER JOIN table_alm_producto p ON p.id_producto = cp.id_producto
                WHERE cp.id_despacho = :id AND cp.status_costeo = 'Pendiente'
                ORDER BY p.producto ASC";
        return $this->fetchAll($sql, ['id' => $idDespacho]);
    }

    /* ============================================================
       ARTÍCULOS COSTEADOS de un despacho
       ============================================================ */
    public function articulosCosteados(int $idDespacho): array{
        $sql = "SELECT cp.id_compra_pendiente,
                       cp.cant_despacho,
                       p.producto, p.present_producto,
                       cc.tasa_dia, cc.monto_divisa, cc.monto_bs
                FROM table_compras_costos cc
                INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
                INNER JOIN table_alm_producto p ON p.id_producto = cp.id_producto
                WHERE cp.id_despacho = :id AND cp.status_costeo = 'Costeado'
                ORDER BY p.producto ASC";
        return $this->fetchAll($sql, ['id' => $idDespacho]);
    }

    /* ============================================================
       ASIGNAR COSTO
       ============================================================ */
    public function asignarCosto(int $idDespacho, float $tasa, array $articulos, int $userId, int $inst = 0): int{
        if ($tasa <= 0 || empty($articulos)) return 0;

        $inst = $inst > 0 ? $inst : DEFAULT_INSTITUCION;

        $this->db->beginTransaction();
        try{
            $procesados = 0;

            foreach ($articulos as $art){
                $idCompraPendiente = (int)$art['id'];
                $montoDivisa       = (float)$art['monto'];
                $montoBs           = $montoDivisa * $tasa;

                // Insertar costo
                $this->query(
                    "INSERT INTO table_compras_costos
                        (id_compra_pendiente, tasa_dia, monto_divisa, monto_bs,
                         fecha_costeo, id_usuario_costeo, id_institucion)
                     VALUES (:cp, :tasa, :md, :mbs, CURDATE(), :uid, :inst)",
                    [
                        'cp'   => $idCompraPendiente,
                        'tasa' => $tasa,
                        'md'   => $montoDivisa,
                        'mbs'  => $montoBs,
                        'uid'  => $userId,
                        'inst' => $inst,
                    ]
                );

                // Marcar como costeado
                $this->query(
                    "UPDATE table_compras_pendientes
                     SET status_costeo = 'Costeado'
                     WHERE id_compra_pendiente = :id",
                    ['id' => $idCompraPendiente]
                );

                $procesados++;
            }

            $this->db->commit();
            return $procesados;
        } catch (\Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       ANULAR COSTO
       ============================================================ */
    public function anularCosto(int $idDespacho): bool{
        $this->db->beginTransaction();
        try{
            // Obtener IDs de pendientes del despacho
            $pendientes = $this->fetchAll(
                "SELECT id_compra_pendiente FROM table_compras_pendientes
                 WHERE id_despacho = :id",
                ['id' => $idDespacho]
            );

            if (empty($pendientes)) return false;

            $ids = array_column($pendientes, 'id_compra_pendiente');

            // Eliminar costos (usa IN con placeholders)
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->query(
                "DELETE FROM table_compras_costos WHERE id_compra_pendiente IN ($placeholders)",
                $ids
            );

            // Volver a marcar como pendiente
            $this->query(
                "UPDATE table_compras_pendientes
                 SET status_costeo = 'Pendiente'
                 WHERE id_despacho = :id",
                ['id' => $idDespacho]
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    /* ============================================================
       CONTEO ÓRDENES para reporte
       ============================================================ */
    public function conteoOrdenes(int $idFlota, ?string $fechaDesde, ?string $fechaHasta, int $inst = 0): int{
        $where  = "cp.id_flota = :flota AND cp.status_costeo = 'Costeado'";
        $params = ['flota' => $idFlota];

        if ($inst > 0){
            $where .= " AND cp.id_institucion = :inst";
            $params['inst'] = $inst;
        }
        if ($fechaDesde && $fechaHasta){
            $where .= " AND d.fecha_despacho BETWEEN :desde AND :hasta";
            $params['desde'] = $fechaDesde;
            $params['hasta'] = $fechaHasta;
        }

        $row = $this->fetchOne(
            "SELECT COUNT(DISTINCT d.id_despacho) AS total
             FROM table_compras_costos cc
             INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
             INNER JOIN table_alm_despacho d ON d.id_despacho = cp.id_despacho
             WHERE {$where}",
            $params
        );

        return (int)($row['total'] ?? 0);
    }

    /* ============================================================
       FLOTA activa
       ============================================================ */
    public function flota(int $inst = 0): array{
        $sql = "SELECT f.id_flota, f.id_unidad, f.vim_unidad,
                       m.marca_unidad, mo.modelo_unidad
                FROM table_flota f
                LEFT JOIN table_flota_marca m ON m.id_marca = f.id_marca
                LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                WHERE f.status_unidad = 1";
        $params = [];
        if ($inst > 0){
            $sql .= " AND f.id_institucion = :inst";
            $params['inst'] = $inst;
        }
        $sql .= " ORDER BY f.id_unidad ASC";
        return $this->fetchAll($sql, $params);
    }
}
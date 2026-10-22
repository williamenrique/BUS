<?php
namespace App\Models;

use App\Core\Model;

/**
 * Modelo para el historial de cambios de estado de las unidades de flota.
 * Tabla: table_flota_status
 */
class ModelFlotaStatus extends Model
{
    /**
     * Lista paginada de cambios de estado con filtros.
     * Devuelve: data[], total, totalFiltrados, counts (por status)
     */
    public function listarPaginado(
        string $q,
        int $limit,
        int $offset,
        array $filtros,
        int $idInst
    ): array {
        $where  = ["s.id_institucion = :inst"];
        $params = ['inst' => $idInst];

        // Búsqueda por unidad, VIN o motivo
        if ($q !== '') {
            $where[] = "(
                f.id_unidad LIKE :q1
                OR f.vim_unidad LIKE :q2
                OR s.textCambio LIKE :q3
            )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        // Filtro por status
        if (isset($filtros['status']) && $filtros['status'] !== '') {
            $where[] = "s.idstatus = :status";
            $params['status'] = (int)$filtros['status'];
        }

        // Filtro por unidad específica
        if (!empty($filtros['id_flota'])) {
            $where[] = "s.id_flota = :idflota";
            $params['idflota'] = (int)$filtros['id_flota'];
        }

        // Filtro por rango de fechas
        if (!empty($filtros['desde'])) {
            $where[] = "DATE(s.fechaCambio) >= :desde";
            $params['desde'] = $filtros['desde'];
        }
        if (!empty($filtros['hasta'])) {
            $where[] = "DATE(s.fechaCambio) <= :hasta";
            $params['hasta'] = $filtros['hasta'];
        }

        $whereSql = "WHERE " . implode(' AND ', $where);

        // Total sin filtros de status/fechas (solo institución)
        $total = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_flota_status WHERE id_institucion = :i",
            ['i' => $idInst]
        )['t'] ?? 0);

        // Total con filtros
        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_flota_status s
             INNER JOIN table_flota f ON f.id_flota = s.id_flota
             $whereSql",
            $params
        )['t'] ?? 0);

        // Conteos por status (para KPIs)
        $countsRows = $this->fetchAll(
            "SELECT s.idstatus, COUNT(*) AS t
             FROM table_flota_status s
             INNER JOIN table_flota f ON f.id_flota = s.id_flota
             $whereSql
             GROUP BY s.idstatus",
            $params
        );

        $counts = [
            'activo'        => 0,
            'inactivo'      => 0,
            'mantenimiento' => 0,
            'critico'       => 0,
            'otro'          => 0,
            'total'         => $totalFiltrados,
        ];
        foreach ($countsRows as $r) {
            switch ((int)$r['idstatus']) {
                case 1: $counts['activo']        += (int)$r['t']; break;
                case 0: $counts['inactivo']      += (int)$r['t']; break;
                case 2: $counts['mantenimiento'] += (int)$r['t']; break;
                case 4:
                case 5: $counts['critico']       += (int)$r['t']; break;
                default: $counts['otro']         += (int)$r['t']; break;
            }
        }

        // Data paginada
        $data = $this->fetchAll(
            "SELECT s.idCambioStatus, s.id_flota, s.idstatus, s.textCambio,
                    s.fechaCambio, s.usuario_id,
                    f.id_unidad, f.vim_unidad,
                    ma.marca_unidad, mo.modelo_unidad,
                    u.usuario_nick,
                    COALESCE(
                        CONCAT_WS(' ', p.personal_nombre, NULLIF(p.personal_apellido,'0')),
                        u.usuario_nick,
                        'Sistema'
                    ) AS usuario_nombre
             FROM table_flota_status s
             INNER JOIN table_flota f ON f.id_flota = s.id_flota
             LEFT JOIN table_flota_marca  ma ON ma.id_marca = f.id_marca
             LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
             LEFT JOIN table_usuarios u ON u.usuario_id = s.usuario_id
             LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
             $whereSql
             ORDER BY s.fechaCambio DESC, s.idCambioStatus DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return [
            'data'           => $data,
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
            'counts'         => $counts,
        ];
    }

    /**
     * Combos para los filtros: lista de unidades de la institución.
     */
    public function unidadesCombo(int $idInst): array
    {
        return $this->fetchAll(
            "SELECT f.id_flota, f.id_unidad, f.vim_unidad
             FROM table_flota f
             WHERE f.id_institucion = :i
             ORDER BY f.id_unidad ASC",
            ['i' => $idInst]
        );
    }

    /**
     * Devuelve todos los cambios de una unidad (para exportar o modal).
     */
    public function porUnidad(int $idFlota, int $idInst): array
    {
        return $this->fetchAll(
            "SELECT s.idCambioStatus, s.idstatus, s.textCambio, s.fechaCambio,
                    u.usuario_nick,
                    COALESCE(
                        CONCAT_WS(' ', p.personal_nombre, NULLIF(p.personal_apellido,'0')),
                        u.usuario_nick,
                        'Sistema'
                    ) AS usuario_nombre
             FROM table_flota_status s
             LEFT JOIN table_usuarios u ON u.usuario_id = s.usuario_id
             LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
             WHERE s.id_flota = :id AND s.id_institucion = :i
             ORDER BY s.fechaCambio DESC, s.idCambioStatus DESC",
            ['id' => $idFlota, 'i' => $idInst]
        );
    }
}
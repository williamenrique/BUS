<?php
namespace App\Models;

use App\Core\Model;

class ModelEstacion extends Model{

    /* ============================================================
       ESTACIONES
       ============================================================ */
    public function estaciones(): array{
        return $this->fetchAll(
            "SELECT id_estacion, estacion FROM table_es_estacion
             WHERE status_estacion = 1 OR status_estacion IS NULL
             ORDER BY estacion ASC"
        );
    }

    public function findEstacion(int $id): ?array{
        return $this->fetchOne(
            "SELECT id_estacion, estacion FROM table_es_estacion WHERE id_estacion = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function tiposVehiculo(): array{
        return $this->fetchAll(
            "SELECT id_tipo_vehiculo AS id, nombre FROM table_es_tipos_vehiculo
             WHERE status_tipo_vehiculo = 1 ORDER BY nombre ASC"
        );
    }

    public function tiposPago(): array{
        return $this->fetchAll(
            "SELECT id_tipo_pago AS id, nombre FROM table_es_tipos_pago
             WHERE status_tipo_pago = 1 ORDER BY nombre ASC"
        );
    }

    public function tiposCombustible(): array{
        return $this->fetchAll(
            "SELECT id_tipo_combustible AS id, nombre FROM table_es_tipos_combustible
             WHERE status_tipo_combustible = 1 ORDER BY id_tipo_combustible ASC"
        );
    }

    /* ============================================================
       TASA
       ============================================================ */
    public function getTasa(): ?array{
        return $this->fetchOne(
            "SELECT tasa_dia, tasa_update FROM table_es_tasa_dia WHERE id_tasa_dia = 1 LIMIT 1"
        );
    }

    public function updateTasa(float $tasa, bool $isAdmin = false): string{
        $actual = $this->getTasa();

        if (!$isAdmin && $actual && !empty($actual['tasa_update'])){
            $hoy = date('Y-m-d');
            $ultima = substr($actual['tasa_update'], 0, 10);
            if ($hoy === $ultima) return 'already_updated';
        }

        if ($actual){
            $this->execute(
                "UPDATE table_es_tasa_dia SET tasa_dia = :t, tasa_update = NOW() WHERE id_tasa_dia = 1",
                ['t' => $tasa]
            );
        } else {
            $this->execute(
                "INSERT INTO table_es_tasa_dia (id_tasa_dia, tasa_dia, tasa_update) VALUES (1, :t, NOW())",
                ['t' => $tasa]
            );
        }
        return 'updated';
    }

    /* ============================================================
       ÚLTIMOS TICKETS
       ============================================================ */
    public function ultimosTickets(int $idUser, string $fecha, int $idEstacion): array{
        $sql = "SELECT v.id_venta, v.fecha_venta, v.hora_venta, v.litros, v.monto,
                       v.tasa_dia, v.id_user, v.id_tipo_pago,
                       tv.nombre AS tipoVehiculo,
                       tc.nombre AS tipoCombustible,
                       tp.nombre AS tipoPago
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
                INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
                WHERE v.fecha_venta = :fecha AND v.id_user = :uid AND v.status_ticket = 1
                ORDER BY v.id_venta DESC LIMIT 10";
        return $this->fetchAll($sql, ['fecha' => $fecha, 'uid' => $idUser]);
    }

    /* ============================================================
       RESUMEN DEL DÍA
       ============================================================ */
    public function resumenDia(int $idUser, string $fecha, int $idEstacion): array{
        $sql = "SELECT
                    COUNT(v.id_venta) AS total_ventas,
                    COALESCE(SUM(v.litros), 0) AS total_litros,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto ELSE 0 END), 0) AS total_divisa,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END), 0) AS total_efectivo,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END), 0) AS total_debito,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                                      WHEN v.id_tipo_pago = 2 THEN v.monto
                                      WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END), 0) AS total_bs
                FROM table_es_venta v
                WHERE v.fecha_venta = :fecha AND v.status_ticket = 1
                  AND v.id_user = :uid";
        $resumen = $this->fetchOne($sql, ['fecha' => $fecha, 'uid' => $idUser])
            ?: ['total_ventas'=>0,'total_litros'=>0,'total_divisa'=>0,'total_efectivo'=>0,'total_debito'=>0,'total_bs'=>0];

        $resumen['tiposVehiculo'] = $this->fetchAll(
            "SELECT tv.nombre AS tipo_vehiculo, COUNT(v.id_venta) AS cantidad
             FROM table_es_venta v
             INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
             WHERE v.fecha_venta = :fecha AND v.status_ticket = 1
               AND v.id_user = :uid
             GROUP BY v.id_tipo_vehiculo",
            ['fecha' => $fecha, 'uid' => $idUser]
        );

        $resumen['tiposCombustible'] = $this->fetchAll(
            "SELECT tc.id_tipo_combustible, tc.nombre AS tipo_combustible,
                    COUNT(v.id_venta) AS cantidad,
                    COALESCE(SUM(v.litros), 0) AS litros
             FROM table_es_venta v
             INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
             WHERE v.fecha_venta = :fecha AND v.status_ticket = 1
               AND v.id_user = :uid
             GROUP BY v.id_tipo_combustible
             ORDER BY tc.id_tipo_combustible ASC",
            ['fecha' => $fecha, 'uid' => $idUser]
        );

        return $resumen;
    }

    /* ============================================================
       VENTAS PENDIENTES
       ============================================================ */
    public function ventasPendientes(int $idUser, int $idEstacion): array{
        $hoy = date('Y-m-d');
        return $this->fetchAll(
            "SELECT DISTINCT v.fecha_venta, v.id_user
             FROM table_es_venta v
             WHERE v.id_user = :uid AND v.fecha_venta <> :hoy
               AND (v.id_cierre_diario IS NULL OR v.id_cierre_diario = 0)
             ORDER BY v.fecha_venta DESC",
            ['uid' => $idUser, 'hoy' => $hoy]
        );
    }

    /* ============================================================
       REGISTRAR VENTA
       ============================================================ */
    public function setVenta(int $idUser, int $idEstacion, int $tipoVehiculo, int $tipoCombustible, float $litros, int $tipoPago, float $monto, float $tasa): int{
        if ($idEstacion === 0) return 0;

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        $userRow = $this->fetchOne("SELECT usuario_rol_id FROM table_usuarios WHERE usuario_id = :id", ['id' => $idUser]);
        $idRol = (int)($userRow['usuario_rol_id'] ?? 0);

        $countRow = $this->fetchOne(
            "SELECT COUNT(v.id_venta) AS total
             FROM table_es_venta v
             WHERE v.fecha_venta = :f AND v.id_user = :u",
            ['f' => $fecha, 'u' => $idUser]
        );
        $numeroTicket = (int)($countRow['total'] ?? 0) + 1;

        $this->query(
            "INSERT INTO table_es_venta
                (id_venta, id_user, id_tipo_pago, id_tipo_vehiculo, id_tipo_combustible,
                 litros, monto, id_cierre_diario, fecha_venta, hora_venta,
                 tasa_dia, id_rol, status_ticket)
             VALUES
                (:nv, :uid, :tp, :tv, :tc, :l, :m, 0, :f, :h, :t, :r, 1)",
            [
                'nv'  => $numeroTicket,
                'uid' => $idUser,
                'tp'  => $tipoPago,
                'tv'  => $tipoVehiculo,
                'tc'  => $tipoCombustible > 0 ? $tipoCombustible : 1,
                'l'   => $litros,
                'm'   => $monto,
                'f'   => $fecha,
                'h'   => $hora,
                't'   => $tasa,
                'r'   => $idRol,
            ]
        );

        return $numeroTicket;
    }

    public function getTicketData(int $idVenta, int $idUser, string $fecha, int $idEstacion): ?array{
        $sql = "SELECT v.*, tv.nombre AS tipoVehiculo, tp.nombre AS tipoPago,
                       tc.nombre AS tipoCombustible,
                       p.personal_nombre, p.personal_apellido, p.personal_cedula,
                       e.estacion
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
                INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
                LEFT JOIN table_es_estacion e ON e.id_estacion = :est
                WHERE v.id_venta = :v AND v.fecha_venta = :f AND v.id_user = :u
                LIMIT 1";
        return $this->fetchOne($sql, ['v' => $idVenta, 'f' => $fecha, 'u' => $idUser, 'est' => $idEstacion]);
    }

    /* ============================================================
       CIERRE
       ============================================================ */
    public function setDailyCierre(int $idUser, string $fecha, int $idEstacion): int{
        $this->db->beginTransaction();
        try{
            $this->query(
                "INSERT INTO table_es_cierre (id_user, fecha_cierre, id_estacion, status_cierre)
                 VALUES (:u, :f, :e, 1)",
                ['u' => $idUser, 'f' => $fecha, 'e' => $idEstacion]
            );
            $idCierre = (int)$this->lastInsertId();

            $this->query(
                "UPDATE table_es_venta SET id_cierre_diario = :c, status_ticket = 0
                 WHERE id_user = :u AND fecha_venta = :f AND status_ticket = 1",
                ['c' => $idCierre, 'u' => $idUser, 'f' => $fecha]
            );

            $this->db->commit();
            return $idCierre;
        } catch (\Throwable $e){
            $this->db->rollBack();
            throw $e;
        }
    }

    public function dataCierre(int $idCierre): ?array{
        $sql = "SELECT c.id_cierre, c.fecha_cierre, c.id_estacion,
                       p.personal_nombre AS usuario_nombres, p.personal_apellido AS usuario_apellidos,
                       e.estacion,
                       MAX(v.tasa_dia) AS tasa_dia,
                       COUNT(v.id_venta) AS total_ventas,
                       SUM(v.litros) AS total_litros,
                       SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto ELSE 0 END) AS total_divisa,
                       SUM(CASE WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END) AS total_efectivo,
                       SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END) AS total_debito,
                       SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia ELSE v.monto END) AS total_general_bs,
                       COUNT(CASE WHEN v.id_tipo_vehiculo = 1 THEN 1 END) AS cant_auto,
                       COUNT(CASE WHEN v.id_tipo_vehiculo = 2 THEN 1 END) AS cant_moto,
                       COUNT(CASE WHEN v.id_tipo_vehiculo = 3 THEN 1 END) AS cant_camion,
                       COUNT(CASE WHEN v.id_tipo_combustible = 1 THEN 1 END) AS cant_gasolina,
                       COUNT(CASE WHEN v.id_tipo_combustible = 2 THEN 1 END) AS cant_diesel,
                       SUM(CASE WHEN v.id_tipo_combustible = 1 THEN v.litros ELSE 0 END) AS litros_gasolina,
                       SUM(CASE WHEN v.id_tipo_combustible = 2 THEN v.litros ELSE 0 END) AS litros_diesel
                FROM table_es_cierre c
                INNER JOIN table_es_venta v ON v.id_cierre_diario = c.id_cierre
                INNER JOIN table_usuarios u ON u.usuario_id = c.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                LEFT JOIN table_es_estacion e ON e.id_estacion = c.id_estacion
                WHERE c.id_cierre = :id
                GROUP BY c.id_cierre, c.fecha_cierre, c.id_estacion,
                         p.personal_nombre, p.personal_apellido, e.estacion";
        return $this->fetchOne($sql, ['id' => $idCierre]);
    }

    public function getDetallado(int $idUser, string $fecha, int $idEstacion, bool $forReport = false): array{
        $sql = "SELECT v.id_venta AS numero_venta, v.fecha_venta, v.hora_venta,
                       tv.nombre AS tipo_vehiculo,
                       tc.nombre AS tipo_combustible,
                       v.litros AS cantidad_litros, v.monto, v.tasa_dia,
                       tp.nombre AS tipo_pago,
                       CASE WHEN tp.id_tipo_pago = 1 THEN v.monto ELSE 0 END AS monto_divisa,
                       CASE WHEN tp.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                            WHEN tp.id_tipo_pago = 2 THEN v.monto ELSE 0 END AS efectivob,
                       CASE WHEN tp.id_tipo_pago = 3 THEN v.monto ELSE 0 END AS tarjeta_debito,
                       CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS operador,
                       e.estacion
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
                INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                LEFT JOIN table_es_cierre c ON c.id_cierre = v.id_cierre_diario
                LEFT JOIN table_es_estacion e ON e.id_estacion = c.id_estacion
                WHERE v.fecha_venta = :f AND v.id_user = :u";
        $params = ['f' => $fecha, 'u' => $idUser];
        if (!$forReport) $sql .= " AND v.status_ticket = 1";
        $sql .= " ORDER BY v.id_venta ASC";
        return $this->fetchAll($sql, $params);
    }

    /* ============================================================
       DATAVENTA (admin)
       ============================================================ */
    public function litrosTotales(): float{
        return (float)($this->fetchOne("SELECT COALESCE(SUM(litros), 0) AS t FROM table_es_venta")['t'] ?? 0);
    }

    public function litrosPorFecha(string $fecha, string $type = 'day', ?string $fechaFin = null, ?int $idComb = null): float{
        $sql = "SELECT COALESCE(SUM(litros), 0) AS t FROM table_es_venta WHERE ";
        $params = [];
        if ($type === 'month'){
            $sql .= "DATE_FORMAT(fecha_venta, '%Y-%m') = :f";
            $params['f'] = $fecha;
        } else {
            $sql .= "fecha_venta = :f";
            $params['f'] = $fecha;
        }
        if ($idComb) { $sql .= " AND id_tipo_combustible = :c"; $params['c'] = $idComb; }
        return (float)($this->fetchOne($sql, $params)['t'] ?? 0);
    }

    public function historialCierres(): array{
        $sql = "SELECT c.id_cierre, c.fecha_cierre, c.id_user, c.id_estacion,
                       p.personal_nombre AS usuario_nombres, p.personal_apellido AS usuario_apellidos,
                       e.estacion,
                       MAX(v.tasa_dia) AS tasa_dia,
                       SUM(v.litros) AS total_litros_vendidos,
                       SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                                WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END) AS efectivo_bs,
                       SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END) AS debito_bs,
                       SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia ELSE v.monto END) AS total_bs,
                       SUM(CASE WHEN v.id_tipo_combustible = 1 THEN v.litros ELSE 0 END) AS litros_gasolina,
                       SUM(CASE WHEN v.id_tipo_combustible = 2 THEN v.litros ELSE 0 END) AS litros_diesel
                FROM table_es_cierre c
                INNER JOIN table_usuarios u ON u.usuario_id = c.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                INNER JOIN table_es_venta v ON v.id_cierre_diario = c.id_cierre
                LEFT JOIN table_es_estacion e ON e.id_estacion = c.id_estacion
                GROUP BY c.id_cierre, c.fecha_cierre, c.id_user, c.id_estacion,
                         p.personal_nombre, p.personal_apellido, e.estacion
                ORDER BY c.fecha_cierre DESC, c.id_user";
        return $this->fetchAll($sql);
    }

    public function listarCierresPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = []): array{
        $where  = "1=1";
        $params = [];

        if ($q !== ''){
            $where .= " AND (
                            p.personal_nombre      LIKE :q1
                         OR p.personal_apellido    LIKE :q2
                         OR CAST(c.id_cierre AS CHAR) LIKE :q3
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }

        $tipoFecha = $filtros['tipoFecha'] ?? 'day';
        $fecha     = trim((string)($filtros['fecha'] ?? ''));

        if ($fecha !== ''){
            if ($tipoFecha === 'month'){
                $where .= " AND DATE_FORMAT(c.fecha_cierre, '%Y-%m') = :f";
            } else {
                $where .= " AND c.fecha_cierre = :f";
            }
            $params['f'] = $fecha;
        }

        $totalGlobal = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_es_cierre"
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(DISTINCT c.id_cierre) AS t
             FROM table_es_cierre c
             INNER JOIN table_usuarios u ON u.usuario_id = c.id_user
             INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT c.id_cierre, c.fecha_cierre, c.id_user, c.id_estacion,
                       p.personal_nombre AS usuario_nombres, p.personal_apellido AS usuario_apellidos,
                       e.estacion,
                       MAX(v.tasa_dia) AS tasa_dia,
                       SUM(v.litros) AS total_litros_vendidos,
                       SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                                WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END) AS efectivo_bs,
                       SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END) AS debito_bs,
                       SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto * v.tasa_dia ELSE v.monto END) AS total_bs
                FROM table_es_cierre c
                INNER JOIN table_usuarios u ON u.usuario_id = c.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                INNER JOIN table_es_venta v ON v.id_cierre_diario = c.id_cierre
                LEFT JOIN table_es_estacion e ON e.id_estacion = c.id_estacion
                WHERE {$where}
                GROUP BY c.id_cierre, c.fecha_cierre, c.id_user, c.id_estacion,
                         p.personal_nombre, p.personal_apellido, e.estacion
                ORDER BY c.fecha_cierre DESC, c.id_cierre DESC
                LIMIT {$limit} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'           => $data,
            'total'          => $totalGlobal,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    public function listarVentasAbiertasPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = []): array{
        $where  = "v.status_ticket = 1";
        $params = [];

        if ($q !== ''){
            $where .= " AND (
                            p.personal_nombre   LIKE :q1
                         OR p.personal_apellido LIKE :q2
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
        }

        $totalGlobal = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM (
                SELECT v.id_user, v.fecha_venta
                FROM table_es_venta v
                WHERE v.status_ticket = 1
                GROUP BY v.id_user, v.fecha_venta
             ) AS sub"
        )['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM (
                SELECT v.id_user, v.fecha_venta
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE {$where}
                GROUP BY v.id_user, v.fecha_venta
             ) AS sub",
            $params
        )['t'] ?? 0);

        $sql = "SELECT v.id_user, v.fecha_venta,
                       SUM(v.litros) AS total_litros,
                       p.personal_nombre AS nombre, p.personal_apellido AS apellido
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE {$where}
                GROUP BY v.id_user, v.fecha_venta, p.personal_nombre, p.personal_apellido
                ORDER BY v.fecha_venta DESC, v.id_user
                LIMIT {$limit} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'           => $data,
            'total'          => $totalGlobal,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    public function listarVentasCierrePaginado(int $idCierre, string $q = '', int $limit = 10, int $offset = 0): array{
        $where  = "v.id_cierre_diario = :c";
        $params = ['c' => $idCierre];

        if ($q !== ''){
            $where .= " AND (
                            CAST(v.id_venta AS CHAR) LIKE :q1
                         OR tp.nombre LIKE :q2
                         OR tv.nombre LIKE :q3
                         OR tc.nombre LIKE :q4
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_es_venta v
             INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
             INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
             INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT v.id_venta, v.fecha_venta, v.hora_venta, v.id_user,
                       tv.nombre AS tipo_vehiculo,
                       tc.nombre AS tipo_combustible,
                       tp.nombre AS tipo_pago,
                       v.litros, v.monto, v.tasa_dia
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
                INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
                WHERE {$where}
                ORDER BY v.id_venta ASC
                LIMIT {$limit} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'           => $data,
            'total'          => $totalFiltrados,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    public function ventasPorCierre(int $idCierre, int $idUser, string $fecha): array{
        $sql = "SELECT v.*, tv.nombre AS tipo_vehiculo, tc.nombre AS tipo_combustible, tp.nombre AS tipo_pago
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
                INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
                WHERE v.fecha_venta = :f AND v.id_user = :u AND v.id_cierre_diario = :c
                ORDER BY v.id_venta ASC";
        return $this->fetchAll($sql, ['f' => $fecha, 'u' => $idUser, 'c' => $idCierre]);
    }

    public function ventasAbiertas(): array{
        return $this->fetchAll(
            "SELECT v.id_user, v.fecha_venta,
                    SUM(v.litros) AS total_litros,
                    p.personal_nombre AS nombre, p.personal_apellido AS apellido
             FROM table_es_venta v
             INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
             INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
             WHERE v.status_ticket = 1
             GROUP BY v.id_user, v.fecha_venta, p.personal_nombre, p.personal_apellido
             ORDER BY v.fecha_venta DESC"
        );
    }

    public function ventasAbiertasPorUsuario(int $idUser, string $fecha, int $idEstacion): array{
        return $this->getDetallado($idUser, $fecha, $idEstacion, false);
    }

    public function closeSale(int $idVenta, string $fecha, int $idUser): bool{
        return $this->execute(
            "DELETE FROM table_es_venta WHERE id_venta = :v AND fecha_venta = :f AND id_user = :u AND status_ticket = 1",
            ['v' => $idVenta, 'f' => $fecha, 'u' => $idUser]
        );
    }

    public function deleteVenta(int $idVenta, string $fecha, int $idUser): bool{
        return $this->execute(
            "DELETE FROM table_es_venta WHERE id_venta = :v AND fecha_venta = :f AND id_user = :u",
            ['v' => $idVenta, 'f' => $fecha, 'u' => $idUser]
        );
    }

    public function deleteAllOpenSales(int $idUser, string $fecha): bool{
        return $this->execute(
            "DELETE FROM table_es_venta WHERE id_user = :u AND fecha_venta = :f AND status_ticket = 1",
            ['u' => $idUser, 'f' => $fecha]
        );
    }

    public function deleteCierre(int $idCierre, int $idUser, int $idEstacion, string $fecha): bool{
        $this->db->beginTransaction();
        try{
            $this->query(
                "UPDATE table_es_venta
                 SET id_cierre_diario = 0, status_ticket = 1
                 WHERE id_cierre_diario = :c AND fecha_venta = :f",
                ['c' => $idCierre, 'f' => $fecha]
            );

            $this->query(
                "DELETE FROM table_es_cierre WHERE id_cierre = :c AND id_user = :u AND fecha_cierre = :f",
                ['c' => $idCierre, 'u' => $idUser, 'f' => $fecha]
            );

            $this->db->commit();
            return true;
        } catch (\Throwable $e){
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteCierreTotal(int $idCierre, int $idUser, string $fecha): bool{
        $this->db->beginTransaction();
        try{
            $this->query(
                "DELETE FROM table_es_venta WHERE id_cierre_diario = :c AND id_user = :u AND fecha_venta = :f",
                ['c' => $idCierre, 'u' => $idUser, 'f' => $fecha]
            );
            $this->query(
                "DELETE FROM table_es_cierre WHERE id_cierre = :c AND id_user = :u AND fecha_cierre = :f",
                ['c' => $idCierre, 'u' => $idUser, 'f' => $fecha]
            );
            $this->db->commit();
            return true;
        } catch (\Throwable $e){
            $this->db->rollBack();
            return false;
        }
    }

    /* ============================================================
       USUARIO / SESIÓN
       ============================================================ */
    public function usuarioInfo(int $idUser): ?array{
        return $this->fetchOne(
            "SELECT usuario_id, usuario_departamento_id, usuario_rol_id
             FROM table_usuarios WHERE usuario_id = :id",
            ['id' => $idUser]
        );
    }

    /* ============================================================
       ESTACIÓN EN LA SESIÓN
       ============================================================ */

    public function guardarEstacionSesion(string $sessionId, int $idEstacion): bool{
        $row = $this->fetchOne(
            "SELECT id FROM table_usuario_sessions WHERE session_id = :sid LIMIT 1",
            ['sid' => $sessionId]
        );
        if (!$row){
            error_log("ModelEstacion::guardarEstacionSesion — no existe session_id={$sessionId}");
            return false;
        }

        $ok = $this->execute(
            "UPDATE table_usuario_sessions
             SET id_estacion = :est
             WHERE session_id = :sid",
            ['est' => $idEstacion, 'sid' => $sessionId]
        );
        return (bool)$ok;
    }

    public function obtenerEstacionSesion(string $sessionId): int{
        $row = $this->fetchOne(
            "SELECT id_estacion FROM table_usuario_sessions
             WHERE session_id = :sid LIMIT 1",
            ['sid' => $sessionId]
        );
        return (int)($row['id_estacion'] ?? 0);
    }

    public function limpiarEstacionSesion(string $sessionId): bool{
        return (bool)$this->execute(
            "UPDATE table_usuario_sessions SET id_estacion = NULL
             WHERE session_id = :sid",
            ['sid' => $sessionId]
        );
    }

    /* ============================================================
       PDF · CIERRE DEL DÍA
       ============================================================ */
    public function ventasDetalleCierre(int $idCierre): array{
        $sql = "SELECT v.id_venta AS numero_venta,
                       v.hora_venta,
                       tv.nombre AS tipo_vehiculo,
                       tc.nombre AS tipo_combustible,
                       v.litros AS cantidad_litros,
                       v.monto,
                       v.tasa_dia,
                       tp.nombre AS tipo_pago,
                       CASE WHEN tp.id_tipo_pago = 1 THEN v.monto ELSE 0 END AS monto_divisa,
                       CASE WHEN tp.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                            WHEN tp.id_tipo_pago = 2 THEN v.monto ELSE 0 END AS efectivob,
                       CASE WHEN tp.id_tipo_pago = 3 THEN v.monto ELSE 0 END AS tarjeta_debito
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_combustible tc ON tc.id_tipo_combustible = v.id_tipo_combustible
                INNER JOIN table_es_tipos_pago tp ON tp.id_tipo_pago = v.id_tipo_pago
                WHERE v.id_cierre_diario = :id
                ORDER BY v.id_venta ASC";
        return $this->fetchAll($sql, ['id' => $idCierre]);
    }

    /* ============================================================
       PDF · REPORTE DE LITROS (filtrado por estación)
       ============================================================ */
    public function litrosReporte(string $fecha, string $type = 'day', ?string $fechaFin = null, ?int $idEstacion = null): array{
        $where  = [];
        $params = [];

        if ($type === 'month'){
            if (!empty($fechaFin) && $fechaFin !== $fecha){
                $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') BETWEEN :f AND :ff";
                $params['f']  = $fecha;
                $params['ff'] = $fechaFin;
                $agruparPor  = "DATE_FORMAT(v.fecha_venta, '%Y-%m')";
                $selectAlias = "DATE_FORMAT(v.fecha_venta, '%Y-%m') AS mes";
            } else {
                $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') = :f";
                $params['f'] = $fecha;
                $agruparPor  = "v.fecha_venta";
                $selectAlias = "v.fecha_venta";
            }
        } else {
            if (!empty($fechaFin) && $fechaFin !== $fecha){
                $where[] = "v.fecha_venta BETWEEN :f AND :ff";
                $params['f']  = $fecha;
                $params['ff'] = $fechaFin;
            } else {
                $where[] = "v.fecha_venta = :f";
                $params['f'] = $fecha;
            }
            $agruparPor  = "tv.nombre";
            $selectAlias = "tv.nombre AS tipo_vehiculo";
        }

        if ($idEstacion !== null && $idEstacion > 0){
            $where[] = "u.usuario_estacion_id = :est";
            $params['est'] = $idEstacion;
        }

        $whereSql = implode(' AND ', $where);

        if ($type === 'month'){
            $sql = "SELECT {$selectAlias},
                           COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS total_litros
                    FROM table_es_venta v
                    INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                    WHERE {$whereSql}
                    GROUP BY {$agruparPor}
                    ORDER BY {$agruparPor} ASC";
            return $this->fetchAll($sql, $params);
        }

        $sql = "SELECT {$selectAlias},
                       COUNT(*) AS cantidad_ventas,
                       COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS total_litros
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                WHERE {$whereSql}
                GROUP BY {$agruparPor}
                ORDER BY {$agruparPor} ASC";
        return $this->fetchAll($sql, $params);
    }

    public function litrosReporteTotal(string $fecha, string $type = 'day', ?string $fechaFin = null, ?int $idEstacion = null): float{
        $where  = [];
        $params = [];

        if ($type === 'month'){
            if (!empty($fechaFin) && $fechaFin !== $fecha){
                $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') BETWEEN :f AND :ff";
                $params['f']  = $fecha;
                $params['ff'] = $fechaFin;
            } else {
                $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') = :f";
                $params['f'] = $fecha;
            }
        } else {
            if (!empty($fechaFin) && $fechaFin !== $fecha){
                $where[] = "v.fecha_venta BETWEEN :f AND :ff";
                $params['f']  = $fecha;
                $params['ff'] = $fechaFin;
            } else {
                $where[] = "v.fecha_venta = :f";
                $params['f'] = $fecha;
            }
        }

        if ($idEstacion !== null && $idEstacion > 0){
            $where[] = "u.usuario_estacion_id = :est";
            $params['est'] = $idEstacion;
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS t
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                WHERE {$whereSql}";
        return (float)($this->fetchOne($sql, $params)['t'] ?? 0);
    }

    public function litrosPorCombustible(string $fecha, string $type, ?string $fechaFin, ?int $idEstacion, int $idComb): float{
        $where  = [];
        $params = [];

        if ($type === 'month'){
            if (!empty($fechaFin) && $fechaFin !== $fecha){
                $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') BETWEEN :f AND :ff";
                $params['f']  = $fecha;
                $params['ff'] = $fechaFin;
            } else {
                $where[] = "DATE_FORMAT(v.fecha_venta, '%Y-%m') = :f";
                $params['f'] = $fecha;
            }
        } else {
            if (!empty($fechaFin) && $fechaFin !== $fecha){
                $where[] = "v.fecha_venta BETWEEN :f AND :ff";
                $params['f']  = $fecha;
                $params['ff'] = $fechaFin;
            } else {
                $where[] = "v.fecha_venta = :f";
                $params['f'] = $fecha;
            }
        }

        if ($idEstacion !== null && $idEstacion > 0){
            $where[] = "u.usuario_estacion_id = :est";
            $params['est'] = $idEstacion;
        }

        $where[] = "v.id_tipo_combustible = :comb";
        $params['comb'] = $idComb;

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))), 0) AS t
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                WHERE {$whereSql}";
        return (float)($this->fetchOne($sql, $params)['t'] ?? 0);
    }

        /**
     * Devuelve nombre y apellido del vendedor (usando table_personal).
     */
    public function nombreVendedor(int $idUser): array{
        $row = $this->fetchOne(
            "SELECT p.personal_nombre, p.personal_apellido
             FROM table_usuarios u
             INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
             WHERE u.usuario_id = :id LIMIT 1",
            ['id' => $idUser]
        );

        return [
            'nombre'   => $row['personal_nombre']   ?? '',
            'apellido' => $row['personal_apellido'] ?? '',
        ];
    }
}
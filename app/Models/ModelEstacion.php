<?php
namespace App\Models;

use App\Core\Model;

class ModelEstacion extends Model{

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

    /* ============================================================
       TASA DEL DÍA (global, id = 1)
       ============================================================ */
    public function getTasa(): ?array{
        return $this->fetchOne(
            "SELECT tasa_dia, tasa_update FROM table_es_tasa_dia WHERE id_tasa_dia = 1 LIMIT 1"
        );
    }

    public function updateTasa(float $tasa): string{
        $actual = $this->getTasa();

        // Si ya se actualizó hoy → bloquear
        if ($actual && !empty($actual['tasa_update'])){
            $hoy = date('Y-m-d');
            $ultima = substr($actual['tasa_update'], 0, 10);
            if ($hoy === $ultima){
                return 'already_updated';
            }
        }

        // Update o insert si no existe
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
       VENTAS del día
       ============================================================ */
    public function ultimosTickets(int $idUser, string $fecha, int $idEstacion = 0): array{
        $sql = "SELECT v.id_venta, v.fecha_venta, v.hora_venta, v.litros, v.monto,
                       v.tasa_dia, v.id_user,
                       tv.nombre AS tipoVehiculo,
                       tp.nombre AS tipoPago
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_pago tp    ON tp.id_tipo_pago = v.id_tipo_pago
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                WHERE v.fecha_venta = :fecha
                  AND v.id_user = :uid
                  AND v.status_ticket = 1";
        $params = ['fecha' => $fecha, 'uid' => $idUser];

        if ($idEstacion > 0){
            $sql .= " AND u.usuario_estacion_id = :est";
            $params['est'] = $idEstacion;
        }

        $sql .= " ORDER BY v.id_venta DESC LIMIT 10";
        return $this->fetchAll($sql, $params);
    }

    public function resumenDia(int $idUser, string $fecha, int $idEstacion = 0): array{
        $sql = "SELECT
                    COUNT(v.id_venta) AS total_ventas,
                    COALESCE(SUM(v.litros), 0) AS total_litros,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN v.monto ELSE 0 END), 0) AS total_divisa,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN v.monto ELSE 0 END), 0) AS total_efectivo,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN v.monto ELSE 0 END), 0) AS total_debito,
                    COALESCE(SUM(CASE WHEN v.id_tipo_pago = 1 THEN CAST(v.monto AS DECIMAL(10,2)) * CAST(v.tasa_dia AS DECIMAL(10,2)) ELSE 0 END), 0)
                    + COALESCE(SUM(CASE WHEN v.id_tipo_pago = 2 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0)
                    + COALESCE(SUM(CASE WHEN v.id_tipo_pago = 3 THEN CAST(v.monto AS DECIMAL(10,2)) ELSE 0 END), 0) AS total_bs
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                WHERE v.fecha_venta = :fecha
                  AND v.status_ticket = 1
                  AND v.id_user = :uid";
        $params = ['fecha' => $fecha, 'uid' => $idUser];

        if ($idEstacion > 0){
            $sql .= " AND u.usuario_estacion_id = :est";
            $params['est'] = $idEstacion;
        }

        $resumen = $this->fetchOne($sql, $params) ?: [
            'total_ventas' => 0, 'total_litros' => 0,
            'total_divisa' => 0, 'total_efectivo' => 0,
            'total_debito' => 0, 'total_bs' => 0,
        ];

        // Desglose por tipo de vehículo
        $sql2 = "SELECT tv.nombre AS tipo_vehiculo, COUNT(v.id_tipo_vehiculo) AS cantidad
                 FROM table_es_venta v
                 INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                 INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                 WHERE v.fecha_venta = :fecha
                   AND v.status_ticket = 1
                   AND v.id_user = :uid";
        $params2 = ['fecha' => $fecha, 'uid' => $idUser];

        if ($idEstacion > 0){
            $sql2 .= " AND u.usuario_estacion_id = :est";
            $params2['est'] = $idEstacion;
        }
        $sql2 .= " GROUP BY v.id_tipo_vehiculo";
        $resumen['tiposVehiculo'] = $this->fetchAll($sql2, $params2);

        return $resumen;
    }

    /* ============================================================
       VENTAS PENDIENTES (días sin cierre)
       ============================================================ */
    public function ventasPendientes(int $idUser, int $idEstacion = 0): array{
        $hoy = date('Y-m-d');
        $sql = "SELECT DISTINCT v.fecha_venta, v.id_user
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                WHERE v.id_user = :uid
                  AND v.fecha_venta <> :hoy
                  AND (v.id_cierre_diario IS NULL OR v.id_cierre_diario = 0)";
        $params = ['uid' => $idUser, 'hoy' => $hoy];

        if ($idEstacion > 0){
            $sql .= " AND u.usuario_estacion_id = :est";
            $params['est'] = $idEstacion;
        }

        $sql .= " ORDER BY v.fecha_venta DESC";
        return $this->fetchAll($sql, $params);
    }

    /* ============================================================
       REGISTRAR VENTA
       ============================================================ */
    public function setVenta(int $idUser, int $idEstacion, int $tipoVehiculo, float $litros, int $tipoPago, float $monto, float $tasa): int{
        if ($idEstacion === 0) return 0;

        $fecha = date('Y-m-d');
        $hora  = date('H:i:s');

        // Obtener rol
        $userRow = $this->fetchOne(
            "SELECT usuario_rol_id FROM table_usuarios WHERE usuario_id = :id",
            ['id' => $idUser]
        );
        $idRol = (int)($userRow['usuario_rol_id'] ?? 0);

        // Número de ticket del día (por usuario+estación)
        $countRow = $this->fetchOne(
            "SELECT COUNT(v.id_venta) AS total
             FROM table_es_venta v
             INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
             WHERE v.fecha_venta = :f AND v.id_user = :u AND u.usuario_estacion_id = :e",
            ['f' => $fecha, 'u' => $idUser, 'e' => $idEstacion]
        );
        $numeroTicket = (int)($countRow['total'] ?? 0) + 1;

        $this->query(
            "INSERT INTO table_es_venta
                (id_venta, id_user, id_tipo_pago, id_tipo_vehiculo,
                 litros, monto, id_cierre_diario, fecha_venta, hora_venta,
                 tasa_dia, id_rol, status_ticket)
             VALUES
                (:nv, :uid, :tp, :tv,
                 :l, :m, 0, :f, :h,
                 :t, :r, 1)",
            [
                'nv'  => $numeroTicket,
                'uid' => $idUser,
                'tp'  => $tipoPago,
                'tv'  => $tipoVehiculo,
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

    public function getTicketData(int $idVenta, int $idUser, string $fecha, int $idEstacion = 0): ?array{
        $sql = "SELECT v.*, tv.nombre AS tipoVehiculo, tp.nombre AS tipoPago,
                       p.personal_nombre, p.personal_apellido, p.personal_cedula,
                       e.estacion
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_pago tp    ON tp.id_tipo_pago = v.id_tipo_pago
                LEFT JOIN table_es_estacion e ON e.id_estacion = u.usuario_estacion_id
                WHERE v.id_venta = :v AND v.fecha_venta = :f AND v.id_user = :u";
        $params = ['v' => $idVenta, 'f' => $fecha, 'u' => $idUser];

        if ($idEstacion > 0){
            $sql .= " AND u.usuario_estacion_id = :e";
            $params['e'] = $idEstacion;
        }

        return $this->fetchOne($sql, $params);
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
                "UPDATE table_es_venta
                 SET id_cierre_diario = :c, status_ticket = 0
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
        $sql = "SELECT c.id_cierre, c.fecha_cierre,
                       p.personal_nombre, p.personal_apellido,
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
                       COUNT(CASE WHEN v.id_tipo_vehiculo = 3 THEN 1 END) AS cant_camion
                FROM table_es_cierre c
                INNER JOIN table_es_venta v ON v.id_cierre_diario = c.id_cierre
                INNER JOIN table_usuarios u ON u.usuario_id = c.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                LEFT JOIN table_es_estacion e ON e.id_estacion = u.usuario_estacion_id
                WHERE c.id_cierre = :id
                GROUP BY c.id_cierre, c.fecha_cierre, p.personal_nombre, p.personal_apellido, e.estacion";
        return $this->fetchOne($sql, ['id' => $idCierre]);
    }

    public function getDetallado(int $idUser, string $fecha, int $idEstacion, bool $forReport = false): array{
        $sql = "SELECT v.id_venta AS numero_venta, v.fecha_venta, v.hora_venta,
                       v.litros AS cantidad_litros, v.monto, v.tasa_dia,
                       tv.nombre AS tipo_vehiculo,
                       tp.nombre AS tipo_pago,
                       CASE WHEN tp.id_tipo_pago = 1 THEN v.monto ELSE 0 END AS monto_divisa,
                       CASE WHEN tp.id_tipo_pago = 1 THEN v.monto * v.tasa_dia
                            WHEN tp.id_tipo_pago = 2 THEN v.monto ELSE 0 END AS efectivob,
                       CASE WHEN tp.id_tipo_pago = 3 THEN v.monto ELSE 0 END AS tarjeta_debito,
                       CONCAT(p.personal_nombre, ' ', p.personal_apellido) AS operador,
                       e.estacion
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_pago tp    ON tp.id_tipo_pago = v.id_tipo_pago
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                LEFT JOIN table_es_estacion e ON e.id_estacion = u.usuario_estacion_id
                WHERE v.fecha_venta = :f AND v.id_user = :u AND u.usuario_estacion_id = :e";
        $params = ['f' => $fecha, 'u' => $idUser, 'e' => $idEstacion];

        if (!$forReport){
            $sql .= " AND v.status_ticket = 1";
        }

        $sql .= " ORDER BY v.id_venta ASC";
        return $this->fetchAll($sql, $params);
    }

    /* ============================================================
       HISTORIAL DE CIERRES (para mantenimiento)
       ============================================================ */
    public function historialCierres(): array{
        $sql = "SELECT c.id_cierre, c.fecha_cierre, c.id_user,
                       p.personal_nombre, p.personal_apellido,
                       u.usuario_estacion_id AS id_estacion,
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
                GROUP BY c.id_cierre, c.fecha_cierre, c.id_user,
                         p.personal_nombre, p.personal_apellido, u.usuario_estacion_id
                ORDER BY c.fecha_cierre DESC, c.id_user";
        return $this->fetchAll($sql);
    }

    public function ventasPorCierre(int $idCierre, int $idUser, string $fecha): array{
        $sql = "SELECT v.*, tv.nombre AS tipo_vehiculo, tp.nombre AS tipo_pago
                FROM table_es_venta v
                INNER JOIN table_es_tipos_vehiculo tv ON tv.id_tipo_vehiculo = v.id_tipo_vehiculo
                INNER JOIN table_es_tipos_pago tp    ON tp.id_tipo_pago = v.id_tipo_pago
                WHERE v.fecha_venta = :f AND v.id_user = :u AND v.id_cierre_diario = :c";
        return $this->fetchAll($sql, ['f' => $fecha, 'u' => $idUser, 'c' => $idCierre]);
    }

    /* ============================================================
       ELIMINAR / VENTAS ABIERTAS
       ============================================================ */
    public function deleteVenta(int $idVenta, string $fecha, int $idUser): bool{
        return $this->execute(
            "DELETE FROM table_es_venta WHERE id_venta = :v AND fecha_venta = :f AND id_user = :u",
            ['v' => $idVenta, 'f' => $fecha, 'u' => $idUser]
        );
    }

    public function ventasAbiertas(): array{
        $sql = "SELECT v.id_user, v.fecha_venta,
                       SUM(v.litros) AS total_litros,
                       p.personal_nombre, p.personal_apellido
                FROM table_es_venta v
                INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE v.status_ticket = 1
                GROUP BY v.id_user, v.fecha_venta, p.personal_nombre, p.personal_apellido
                ORDER BY v.fecha_venta DESC";
        return $this->fetchAll($sql);
    }

    public function cerrarVenta(int $idVenta): bool{
        return $this->execute(
            "UPDATE table_es_venta SET status_ticket = 0 WHERE id_venta = :v",
            ['v' => $idVenta]
        );
    }

    public function deleteCierre(int $idCierre, int $idUser, int $idEstacion, string $fecha): bool{
        $this->db->beginTransaction();
        try{
            $this->query(
                "UPDATE table_es_venta v
                 INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                 SET v.id_cierre_diario = 0, v.status_ticket = 1
                 WHERE v.id_cierre_diario = :c AND v.fecha_venta = :f AND u.usuario_estacion_id = :e",
                ['c' => $idCierre, 'f' => $fecha, 'e' => $idEstacion]
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

    public function usuarioInfo(int $idUser): ?array{
        return $this->fetchOne(
            "SELECT usuario_id, usuario_estacion_id FROM table_usuarios WHERE usuario_id = :id",
            ['id' => $idUser]
        );
    }
}
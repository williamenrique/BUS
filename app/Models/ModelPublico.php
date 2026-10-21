<?php
namespace App\Models;

use App\Core\Model;

class ModelPublico extends Model{

    /* ============================================================
       INSTITUCIONES
       ============================================================ */
    public function getInstituciones(): array{
        return $this->fetchAll(
            "SELECT id_institucion, nombre FROM table_instituciones
             WHERE status = 1 ORDER BY id_institucion ASC"
        );
    }

    public function getNombreInstitucion(int $id): string{
        $row = $this->fetchOne(
            "SELECT nombre FROM table_instituciones WHERE id_institucion = :id LIMIT 1",
            ['id' => $id]
        );
        return $row['nombre'] ?? 'Institución Desconocida';
    }

    /* ============================================================
       FLOTA
       ============================================================ */
    public function getEstadoFlota(int $idInst): array{
        $rows = $this->fetchAll(
            "SELECT status_unidad, COUNT(*) AS total FROM table_flota
             WHERE status_unidad BETWEEN 1 AND 4 AND id_institucion = :i
             GROUP BY status_unidad",
            ['i' => $idInst]
        );

        $r = ['total'=>0,'operativas'=>0,'inoperativas'=>0,'en_mantenimiento'=>0,'criticas'=>0];
        foreach ($rows as $row){
            switch ((int)$row['status_unidad']){
                case 1: $r['operativas'] = (int)$row['total']; break;
                case 2: $r['inoperativas'] = (int)$row['total']; break;
                case 3: $r['en_mantenimiento'] = (int)$row['total']; break;
                case 4: $r['criticas'] = (int)$row['total']; break;
            }
        }
        $r['total'] = $r['operativas'] + $r['inoperativas'] + $r['en_mantenimiento'] + $r['criticas'];
        return $r;
    }

    public function getUnidadesPorEstado($status, int $idInst): array{
        return $this->fetchAll(
            "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.fecha_creacion,
                    fm.marca_unidad, fmo.modelo_unidad,
                    f.transmision, f.tipo_combustible, f.status_unidad,
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km
                              WHERE km.id_flota = f.id_flota
                              ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) AS km_actual,
                    COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah
                              WHERE ah.id_flota = f.id_flota
                              ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS ultimo_cambio_aceite,
                    COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                              FROM table_flota_aceite_historial ah
                              WHERE ah.id_flota = f.id_flota
                              ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS proximo_cambio_aceite
             FROM table_flota f
             INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
             INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
             WHERE f.status_unidad = :s AND f.id_institucion = :i
             ORDER BY f.id_unidad",
            ['s' => (int)$status, 'i' => $idInst]
        );
    }

    /* ============================================================
       ACEITE
       ============================================================ */
    public function getEstadoAceite(int $idInst): array{
        $rows = $this->fetchAll(
            "SELECT tf.id_flota,
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km
                              WHERE km.id_flota = tf.id_flota
                              ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) AS km_actual,
                    COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                              FROM table_flota_aceite_historial ah
                              WHERE ah.id_flota = tf.id_flota
                              ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS prox_cambio
             FROM table_flota tf
             WHERE tf.status_unidad = 1 AND tf.id_institucion = :i",
            ['i' => $idInst]
        );

        $r = ['requerido'=>0,'proximo'=>0,'ok'=>0];
        foreach ($rows as $row){
            $km = (int)$row['km_actual'];
            $prox = (int)$row['prox_cambio'];
            if ($prox <= 0) continue;
            $diff = $prox - $km;
            if ($diff <= 0) $r['requerido']++;
            elseif ($diff <= 1000) $r['proximo']++;
            else $r['ok']++;
        }
        return $r;
    }

    public function getUnidadesPorEstadoAceite($status, int $idInst): array{
        $base = "SELECT f.id_flota, f.id_unidad, f.vim_unidad, f.fecha_creacion,
                        fm.marca_unidad, fmo.modelo_unidad,
                        f.transmision, f.tipo_combustible,
                        COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km
                                WHERE km.id_flota = f.id_flota
                                ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) AS km_actual,
                        COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah
                                WHERE ah.id_flota = f.id_flota
                                ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS ultimo_cambio_aceite,
                        COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                                FROM table_flota_aceite_historial ah
                                WHERE ah.id_flota = f.id_flota
                                ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS proximo_cambio_aceite
                FROM table_flota f
                INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
                INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
                WHERE f.status_unidad = 1 AND f.id_institucion = :i";

        $rows = $this->fetchAll($base, ['i' => $idInst]);

        // Si piden TODO, devolver todas sin filtrar
        if ($status === 'todos'){
            return $rows;
        }

        // Filtrar según estado solicitado
        $filtered = [];
        foreach ($rows as $row){
            $km   = (int)$row['km_actual'];
            $prox = (int)$row['proximo_cambio_aceite'];

            if ($prox <= 0){
                if ($status === 'sin_registro') $filtered[] = $row;
                continue;
            }
            $diff = $prox - $km;

            if ($status === 'requerido' && $diff <= 0) $filtered[] = $row;
            elseif ($status === 'proximo' && $diff > 0 && $diff <= 1000) $filtered[] = $row;
            elseif ($status === 'ok' && $diff > 1000) $filtered[] = $row;
        }
        return $filtered;
    }

    /* ============================================================
       RESUMEN FLOTA
       ============================================================ */
    public function getResumenFlota(int $idInst): array{
        $rows = $this->fetchAll(
            "SELECT fm.marca_unidad AS marca, fmo.modelo_unidad AS modelo,
                    CONCAT(fm.marca_unidad, ' ', fmo.modelo_unidad) AS marca_modelo,
                    f.transmision, f.tipo_combustible AS combustible, f.status_unidad, COUNT(*) AS total
             FROM table_flota f
             INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
             INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
             WHERE f.status_unidad IN (1,2,3,5) AND f.id_institucion = :i
             GROUP BY fm.marca_unidad, fmo.modelo_unidad, f.transmision, f.tipo_combustible, f.status_unidad
             ORDER BY fm.marca_unidad, fmo.modelo_unidad",
            ['i' => $idInst]
        );

        $grouped = [];
        foreach ($rows as $row){
            $key = $row['marca_modelo'] . '|' . $row['transmision'] . '|' . $row['combustible'];
            if (!isset($grouped[$key])){
                $grouped[$key] = [
                    'marca_modelo'=>$row['marca_modelo'],
                    'transmision'=>$row['transmision'],
                    'combustible'=>$row['combustible'],
                    'total'=>0,'operativas'=>0,'inoperativas'=>0,
                ];
            }
            $grouped[$key]['total'] += (int)$row['total'];
            if ((int)$row['status_unidad'] === 1) $grouped[$key]['operativas'] += (int)$row['total'];
            else $grouped[$key]['inoperativas'] += (int)$row['total'];
        }
        return array_values($grouped);
    }

    /* ============================================================
       MOVIMIENTOS
       ============================================================ */
    public function getDespachosPublic(string $fi, string $ff, int $idInst): array{
        $despachos = $this->fetchAll(
            "SELECT d.id_despacho, d.id_flota, f.id_unidad,
                    d.operador_id, d.mecanico_id, d.despachador_id,
                    CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido,'0')) AS operador_nombre,
                    CONCAT_WS(' ', p_mec.personal_nombre, NULLIF(p_mec.personal_apellido,'0')) AS mecanico_nombre,
                    CONCAT_WS(' ', p_desp.personal_nombre, NULLIF(p_desp.personal_apellido,'0')) AS despachador_nombre,
                    d.fecha_despacho, d.observacion, d.estado_orden, d.status_despacho
             FROM table_alm_despacho d
             LEFT JOIN table_flota f ON d.id_flota = f.id_flota
             LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
             LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
             LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
             WHERE d.fecha_despacho BETWEEN :fi AND :ff
               AND (f.id_flota IS NULL OR f.id_institucion = :i)
             ORDER BY d.fecha_despacho DESC",
            ['fi' => $fi, 'ff' => $ff, 'i' => $idInst]
        );

        if (!empty($despachos)){
            $ids = array_column($despachos, 'id_despacho');
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $prod = $this->fetchAll(
                "SELECT rd.id_despacho, p.producto, p.present_producto, rd.cant_despacho
                 FROM table_alm_relacion_despacho rd
                 INNER JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                 WHERE rd.id_despacho IN ($ph)",
                $ids
            );
            $byDesp = [];
            foreach ($prod as $p){ $byDesp[$p['id_despacho']][] = $p; }
            foreach ($despachos as &$d){ $d['productos'] = $byDesp[$d['id_despacho']] ?? []; }
            unset($d);
        }
        return $despachos;
    }

    public function getMantenimientosPublic(string $fi, string $ff, int $idInst): array{
        return $this->fetchAll(
            "SELECT m.id_unidad_mantenimiento, m.id_flota, f.id_unidad,
                    m.ruta_unidad, m.operardor_unidad, m.nomb_mecanico, m.km_unidad,
                    m.tipo_mantenimiento, m.diagnostico, m.recomendacion,
                    m.obsOperador, m.obsSupervisor, m.obsSalida,
                    m.fecha_entrada, m.fecha_salida, m.status_mantenimiento,
                    m.usuario_id, u.usuario_nick
             FROM table_flota_mantenimiento m
             LEFT JOIN table_flota f ON m.id_flota = f.id_flota
             LEFT JOIN table_usuarios u ON m.usuario_id = u.usuario_id
             WHERE m.fecha_entrada BETWEEN :fi AND :ff AND f.id_institucion = :i
             ORDER BY m.fecha_entrada DESC",
            ['fi' => $fi, 'ff' => $ff, 'i' => $idInst]
        );
    }

    public function getCambiosAceitePublic(string $fi, string $ff, int $idInst): array{
        return $this->fetchAll(
            "SELECT ah.id_aceite_historial, ah.id_flota, f.id_unidad,
                    ah.fecha_cambio, ah.kilometraje_cambio, ah.kilometraje_anterior,
                    ah.kilometraje_proximo_cambio, ah.usuario_id, ah.observaciones, u.usuario_nick
             FROM table_flota_aceite_historial ah
             LEFT JOIN table_flota f ON ah.id_flota = f.id_flota
             LEFT JOIN table_usuarios u ON ah.usuario_id = u.usuario_id
             WHERE ah.fecha_cambio BETWEEN :fi AND :ff AND f.id_institucion = :i
             ORDER BY ah.fecha_cambio DESC",
            ['fi' => $fi, 'ff' => $ff, 'i' => $idInst]
        );
    }

    public function getKilometrajePublic(string $fi, string $ff, int $idInst): array{
        return $this->fetchAll(
            "SELECT k.id_kilometraje, k.id_flota, f.id_unidad,
                    k.kilometraje_actual, k.fecha_actualizacion, k.usuario_id, u.usuario_nick
             FROM table_flota_kilometraje k
             LEFT JOIN table_flota f ON k.id_flota = f.id_flota
             LEFT JOIN table_usuarios u ON k.usuario_id = u.usuario_id
             WHERE DATE(k.fecha_actualizacion) BETWEEN :fi AND :ff AND f.id_institucion = :i
             ORDER BY k.fecha_actualizacion DESC",
            ['fi' => $fi, 'ff' => $ff, 'i' => $idInst]
        );
    }

    /* ============================================================
       DETALLES
       ============================================================ */
    public function getDetalleOrden(int $idDespacho): ?array{
        $orden = $this->fetchOne(
            "SELECT d.id_despacho, d.numero_orden, d.id_institucion, d.id_flota,
                    f.id_unidad, f.vim_unidad,
                    ma.marca_unidad, mo.modelo_unidad,
                    d.operador_id, d.mecanico_id, d.despachador_id,
                    CONCAT_WS(' ', p_op.personal_nombre, NULLIF(p_op.personal_apellido,'0')) AS operador_nombre,
                    CONCAT_WS(' ', p_mec.personal_nombre, NULLIF(p_mec.personal_apellido,'0')) AS mecanico_nombre,
                    CONCAT_WS(' ', p_desp.personal_nombre, NULLIF(p_desp.personal_apellido,'0')) AS despachador_nombre,
                    d.fecha_despacho, d.observacion, d.estado_orden, d.status_despacho,
                    COALESCE(CONCAT_WS(' ', p_u.personal_nombre, NULLIF(p_u.personal_apellido,'0')), u.usuario_nick, 'Sistema') AS usuario_registro
             FROM table_alm_despacho d
             LEFT JOIN table_flota f ON d.id_flota = f.id_flota
             LEFT JOIN table_flota_marca ma ON f.id_marca = ma.id_marca
             LEFT JOIN table_flota_modelo mo ON f.id_modelo = mo.id_modelo
             LEFT JOIN table_usuarios u ON d.user_id = u.usuario_id
             LEFT JOIN table_personal p_u ON u.usuario_id_personal = p_u.id_personal
             LEFT JOIN table_personal p_op ON d.operador_id = p_op.id_personal
             LEFT JOIN table_personal p_mec ON d.mecanico_id = p_mec.id_personal
             LEFT JOIN table_personal p_desp ON d.despachador_id = p_desp.id_personal
             WHERE d.id_despacho = :id LIMIT 1",
            ['id' => $idDespacho]
        );
        if (!$orden) return null;

        $orden['productos'] = $this->fetchAll(
            "SELECT rd.id_despacho, p.id_producto, p.producto, p.present_producto,
                    rd.cant_despacho, ub.ubicacion
             FROM table_alm_relacion_despacho rd
             INNER JOIN table_alm_producto p ON rd.id_producto = p.id_producto
             LEFT JOIN table_alm_ubicacion ub ON ub.id_ubicacion = p.id_ubicacion
             WHERE rd.id_despacho = :id",
            ['id' => $idDespacho]
        );
        return $orden;
    }

    public function getDetalleAceite(int $id): ?array{
        return $this->fetchOne(
            "SELECT ah.*, f.id_unidad, f.vim_unidad, u.usuario_nick,
                    COALESCE(CONCAT(p.personal_nombre,' ',p.personal_apellido), u.usuario_nick, 'Sistema') AS responsable_nombre
             FROM table_flota_aceite_historial ah
             LEFT JOIN table_flota f ON ah.id_flota = f.id_flota
             LEFT JOIN table_usuarios u ON ah.usuario_id = u.usuario_id
             LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
             WHERE ah.id_aceite_historial = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function getDetalleMantenimiento(int $id): ?array{
        return $this->fetchOne(
            "SELECT m.*, f.id_unidad, f.vim_unidad, u.usuario_nick,
                    COALESCE(CONCAT(p.personal_nombre,' ',p.personal_apellido), u.usuario_nick, 'Sistema') AS responsable_nombre
             FROM table_flota_mantenimiento m
             LEFT JOIN table_flota f ON m.id_flota = f.id_flota
             LEFT JOIN table_usuarios u ON m.usuario_id = u.usuario_id
             LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
             WHERE m.id_unidad_mantenimiento = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function getDetalleKilometraje(int $id): ?array{
        return $this->fetchOne(
            "SELECT k.*, f.id_unidad, f.vim_unidad, u.usuario_nick,
                    COALESCE(CONCAT(p.personal_nombre,' ',p.personal_apellido), u.usuario_nick, 'Sistema') AS responsable_nombre,
                    (SELECT k2.kilometraje_actual FROM table_flota_kilometraje k2
                     WHERE k2.id_flota = k.id_flota
                       AND (k2.fecha_actualizacion < k.fecha_actualizacion
                            OR (k2.fecha_actualizacion = k.fecha_actualizacion AND k2.id_kilometraje < k.id_kilometraje))
                     ORDER BY k2.fecha_actualizacion DESC, k2.id_kilometraje DESC LIMIT 1) AS kilometraje_anterior
             FROM table_flota_kilometraje k
             LEFT JOIN table_flota f ON k.id_flota = f.id_flota
             LEFT JOIN table_usuarios u ON k.usuario_id = u.usuario_id
             LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
             WHERE k.id_kilometraje = :id LIMIT 1",
            ['id' => $id]
        );
    }

    public function getUnidadInfo(int $idFlota): ?array{
        return $this->fetchOne(
            "SELECT f.id_flota, f.id_unidad, fm.marca_unidad AS marca, fmo.modelo_unidad AS modelo,
                    f.transmision, f.tipo_combustible AS combustible, f.status_unidad,
                    COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km
                              WHERE km.id_flota = f.id_flota
                              ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1), 0) AS km_actual,
                    COALESCE((SELECT ah.kilometraje_cambio FROM table_flota_aceite_historial ah
                              WHERE ah.id_flota = f.id_flota
                              ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS ultimo_cambio_aceite,
                    COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                              FROM table_flota_aceite_historial ah
                              WHERE ah.id_flota = f.id_flota
                              ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1), 0) AS proximo_cambio_aceite
             FROM table_flota f
             INNER JOIN table_flota_marca fm ON f.id_marca = fm.id_marca
             INNER JOIN table_flota_modelo fmo ON f.id_modelo = fmo.id_modelo
             WHERE f.id_flota = :id LIMIT 1",
            ['id' => $idFlota]
        );
    }

    /* ============================================================
       HISTORIAL DE UNIDAD
       ============================================================ */
    public function selectHistorialUnidad(int $idFlota, array $postData, int $perPage): array{
        $fi = !empty($postData['fechaInicio']) ? $postData['fechaInicio'] : null;
        $ff = !empty($postData['fechaFin'])    ? $postData['fechaFin']    : null;
        $filtroTipo = !empty($postData['filtroTipo']) ? $postData['filtroTipo'] : null;
        $filtroTerm = !empty($postData['filtroTermino']) ? $postData['filtroTermino'] : null;
        $page = isset($postData['page']) ? max(1, (int)$postData['page']) : 1;
        $offset = ($page - 1) * $perPage;

        $where = ["h.id_flota = ?"];
        $params = [$idFlota];
        if ($fi && $ff){ $where[] = "DATE(h.fecha) BETWEEN ? AND ?"; $params[] = $fi; $params[] = $ff; }
        elseif ($fi){ $where[] = "DATE(h.fecha) >= ?"; $params[] = $fi; }
        elseif ($ff){ $where[] = "DATE(h.fecha) <= ?"; $params[] = $ff; }
        if ($filtroTipo){ $where[] = "h.tipo = ?"; $params[] = $filtroTipo; }
        if ($filtroTerm){ $where[] = "(h.id_evento LIKE ? OR h.detalles LIKE ?)"; $params[] = "%{$filtroTerm}%"; $params[] = "%{$filtroTerm}%"; }

        $whereSql = "WHERE " . implode(' AND ', $where);

        $union = "
            (SELECT 'despacho' AS tipo, d.id_despacho AS id_evento, d.id_flota, d.fecha_despacho AS fecha,
                     JSON_OBJECT('observacion', d.observacion) AS detalles, d.user_id AS usuario_id
              FROM table_alm_despacho d WHERE d.status_despacho = 1)
            UNION ALL
            (SELECT 'aceite' AS tipo, ah.id_aceite_historial AS id_evento, ah.id_flota, ah.fecha_cambio AS fecha,
                     JSON_OBJECT('kilometraje_cambio', ah.kilometraje_cambio,
                                 'kilometraje_anterior', ah.kilometraje_anterior,
                                 'kilometraje_proximo_cambio', ah.kilometraje_proximo_cambio) AS detalles,
                     ah.usuario_id
              FROM table_flota_aceite_historial ah)
            UNION ALL
            (SELECT 'mantenimiento' AS tipo, m.id_unidad_mantenimiento AS id_evento, m.id_flota, m.fecha_entrada AS fecha,
                     JSON_OBJECT('tipo_mantenimiento', m.tipo_mantenimiento, 'diagnostico', m.diagnostico) AS detalles,
                     m.usuario_id
              FROM table_flota_mantenimiento m)
            UNION ALL
            (SELECT 'status' AS tipo, s.idCambioStatus AS id_evento, s.id_flota, s.fechaCambio AS fecha,
                     JSON_OBJECT('status_id', s.idstatus, 'motivo', s.textCambio,
                                 'status_texto', CASE s.idstatus WHEN 1 THEN 'Activo' WHEN 0 THEN 'Inactivo'
                                                                WHEN 2 THEN 'En Mantenimiento' ELSE 'Desconocido' END) AS detalles,
                     s.usuario_id
              FROM table_flota_status s)
        ";

        $total = (int)($this->fetchOne("SELECT COUNT(*) AS total FROM ($union) AS h $whereSql", $params)['total'] ?? 0);

        $countsRows = $this->fetchAll("SELECT tipo, COUNT(*) AS total FROM ($union) AS h $whereSql GROUP BY tipo", $params);
        $counts = ['despacho'=>0,'mantenimiento'=>0,'aceite'=>0,'status'=>0];
        foreach ($countsRows as $r){ $counts[$r['tipo']] = (int)$r['total']; }

        $items = $this->fetchAll(
            "SELECT h.*, COALESCE(CONCAT(p.personal_nombre,' ',p.personal_apellido), d.departamento_nombre, 'Sistema') AS usuario
             FROM ($union) AS h
             LEFT JOIN table_usuarios u ON h.usuario_id = u.usuario_id
             LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
             LEFT JOIN table_departamentos d ON u.usuario_departamento_id = d.departamento_id
             $whereSql ORDER BY h.fecha DESC, h.id_evento DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return ['total_items' => $total, 'items' => $items, 'counts' => $counts];
    }

    /* ============================================================
       ESTACIÓN
       ============================================================ */
    public function getVentasEstacionPublic(string $fi, string $ff, $estacionId = null): array{
        $sql = "SELECT v.id_venta, v.id_user, v.id_tipo_pago, v.id_tipo_vehiculo, v.id_tipo_combustible,
                       v.litros, v.monto, v.fecha_venta, v.hora_venta, v.tasa_dia, v.status_ticket,
                       u.usuario_nick,
                       COALESCE(NULLIF(TRIM(CONCAT_WS(' ',
                           NULLIF(TRIM(p.personal_nombre), ''),
                           CASE WHEN p.personal_apellido IS NOT NULL
                                 AND TRIM(p.personal_apellido) <> ''
                                 AND TRIM(p.personal_apellido) <> '0'
                                THEN CONCAT(LEFT(TRIM(p.personal_apellido),1), '.')
                                ELSE NULL END
                       )), ''), u.usuario_nick) AS vendedor_nombre,
                       tv.nombre AS tipo_vehiculo, tp.nombre AS tipo_pago,
                       tc.nombre AS tipo_combustible, e.estacion AS estacion_nombre
                FROM table_es_venta v
                LEFT JOIN table_usuarios u ON v.id_user = u.usuario_id
                LEFT JOIN table_personal p ON u.usuario_id_personal = p.id_personal
                LEFT JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
                LEFT JOIN table_es_tipos_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
                LEFT JOIN table_es_tipos_combustible tc ON v.id_tipo_combustible = tc.id_tipo_combustible
                LEFT JOIN table_es_estacion e ON u.usuario_estacion_id = e.id_estacion
                WHERE v.fecha_venta BETWEEN :fi AND :ff";

        $params = ['fi' => $fi, 'ff' => $ff];
        if ($estacionId && $estacionId !== 'todas'){
            $sql .= " AND u.usuario_estacion_id = :est";
            $params['est'] = $estacionId;
        }
        $sql .= " ORDER BY v.fecha_venta DESC, v.hora_venta DESC";
        return $this->fetchAll($sql, $params);
    }

    public function getEstacionesPublic(): array{
        return $this->fetchAll(
            "SELECT id_estacion, estacion FROM table_es_estacion
             WHERE status_estacion = 1 ORDER BY estacion"
        );
    }
}
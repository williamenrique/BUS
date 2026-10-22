<?php
namespace App\Models;

use App\Core\Model;

class ModelDashboard extends Model
{
    /* ============================================================
       EXISTENTES (mantener compatibilidad)
       ============================================================ */
    public function notificaciones(int $limit = 10): array
    {
        try {
            return $this->fetchAll(
                "SELECT id_notificacion, tipo_notificacion, mensaje,
                        leido, fecha_creacion
                 FROM table_notificaciones
                 ORDER BY fecha_creacion DESC
                 LIMIT {$limit}"
            );
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function totales(): array
    {
        $data = ['usuarios'=>0,'flota'=>0,'personal'=>0,'estaciones'=>0];
        try {
            $data['usuarios']   = (int)($this->fetchOne("SELECT COUNT(*) t FROM table_usuarios WHERE usuario_status=1")['t'] ?? 0);
            $data['flota']      = (int)($this->fetchOne("SELECT COUNT(*) t FROM table_flota")['t'] ?? 0);
            $data['personal']   = (int)($this->fetchOne("SELECT COUNT(*) t FROM table_personal")['t'] ?? 0);
            $data['estaciones'] = (int)($this->fetchOne("SELECT COUNT(*) t FROM table_es_estacion")['t'] ?? 0);
        } catch (\Throwable $e) {}
        return $data;
    }

    /* ============================================================
       NUEVOS · INSTITUCIONES
       ============================================================ */
    public function getInstituciones(): array
    {
        try {
            return $this->fetchAll(
                "SELECT id_institucion, nombre FROM table_instituciones
                 WHERE status = 1 ORDER BY id_institucion ASC"
            );
        } catch (\Throwable $e) { return []; }
    }

    /* ============================================================
       NUEVOS · ALMACÉN
       ============================================================ */
    public function getAlmacenDashboard(int $idInst): array
    {
        $r = [
            'productos_sin_stock'   => 0,
            'productos_stock_bajo'  => 0,
            'total_productos'       => 0,
            'orders_aprobadas'      => ['total_aprobadas'=>0],
            'orders_despachadas'    => ['total_despachadas'=>0],
            'consumibles'           => ['mes_actual'=>0,'mes_anterior'=>0],
        ];

        try {
            $r['total_productos'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_alm_producto WHERE id_institucion = :i",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['productos_sin_stock'] = (int)($this->fetchOne(
                "SELECT COUNT(DISTINCT p.id_producto) t
                 FROM table_alm_producto p
                 LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                 WHERE p.id_institucion = :i
                   AND COALESCE(rp.cant_producto, 0) <= 0",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['productos_stock_bajo'] = (int)($this->fetchOne(
                "SELECT COUNT(DISTINCT p.id_producto) t
                 FROM table_alm_producto p
                 LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                 WHERE p.id_institucion = :i
                   AND COALESCE(rp.cant_producto, 0) > 0
                   AND COALESCE(rp.cant_producto, 0) < 10",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['orders_aprobadas']['total_aprobadas'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_alm_despacho
                 WHERE id_institucion = :i AND estado_orden = 2",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['orders_despachadas']['total_despachadas'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_alm_despacho
                 WHERE id_institucion = :i AND estado_orden = 3
                   AND MONTH(fecha_despacho) = MONTH(CURDATE())
                   AND YEAR(fecha_despacho)  = YEAR(CURDATE())",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['consumibles']['mes_actual'] = (float)($this->fetchOne(
                "SELECT COALESCE(SUM(rd.cant_despacho),0) t
                 FROM table_alm_relacion_despacho rd
                 INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                 INNER JOIN table_alm_producto p ON p.id_producto = rd.id_producto
                 INNER JOIN table_alm_enlace_producto ep ON ep.id_enlace_producto = p.id_enlace_producto
                 WHERE d.id_institucion = :i
                   AND d.estado_orden = 3
                   AND ep.enlace_producto LIKE '%LUBRICANTE%'
                   AND MONTH(d.fecha_despacho) = MONTH(CURDATE())
                   AND YEAR(d.fecha_despacho)  = YEAR(CURDATE())",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['consumibles']['mes_anterior'] = (float)($this->fetchOne(
                "SELECT COALESCE(SUM(rd.cant_despacho),0) t
                 FROM table_alm_relacion_despacho rd
                 INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                 INNER JOIN table_alm_producto p ON p.id_producto = rd.id_producto
                 INNER JOIN table_alm_enlace_producto ep ON ep.id_enlace_producto = p.id_enlace_producto
                 WHERE d.id_institucion = :i
                   AND d.estado_orden = 3
                   AND ep.enlace_producto LIKE '%LUBRICANTE%'
                   AND MONTH(d.fecha_despacho) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
                   AND YEAR(d.fecha_despacho)  = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))",
                ['i'=>$idInst]
            )['t'] ?? 0);
        } catch (\Throwable $e) {}

        return $r;
    }

    public function getTopProductosDespachados(int $idInst, int $limit = 10): array
    {
        try {
            return $this->fetchAll(
                "SELECT p.producto, p.present_producto,
                        ep.enlace_producto, u.ubicacion,
                        SUM(rd.cant_despacho) AS total_despachado
                 FROM table_alm_relacion_despacho rd
                 INNER JOIN table_alm_despacho d ON d.id_despacho = rd.id_despacho
                 INNER JOIN table_alm_producto p ON p.id_producto = rd.id_producto
                 LEFT JOIN table_alm_enlace_producto ep ON ep.id_enlace_producto = p.id_enlace_producto
                 LEFT JOIN table_alm_ubicacion u ON u.id_ubicacion = p.id_ubicacion
                 WHERE d.id_institucion = :i
                   AND d.estado_orden = 3
                   AND MONTH(d.fecha_despacho) = MONTH(CURDATE())
                   AND YEAR(d.fecha_despacho)  = YEAR(CURDATE())
                 GROUP BY p.id_producto, p.producto, p.present_producto, ep.enlace_producto, u.ubicacion
                 ORDER BY total_despachado DESC
                 LIMIT {$limit}",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) { return []; }
    }

    public function getUltimasOrdenes(int $idInst, int $limit = 10): array
    {
        try {
            return $this->fetchAll(
                "SELECT d.id_despacho, d.numero_orden, d.fecha_despacho,
                        d.estado_orden,
                        f.id_unidad, mo.modelo_unidad,
                        CONCAT_WS(' ', po.personal_nombre, NULLIF(po.personal_apellido,'0')) AS operador_nombre,
                        COUNT(rd.id_producto) AS total_articulos
                 FROM table_alm_despacho d
                 LEFT JOIN table_flota f ON f.id_flota = d.id_flota
                 LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                 LEFT JOIN table_personal po ON po.id_personal = d.operador_id
                 LEFT JOIN table_alm_relacion_despacho rd ON rd.id_despacho = d.id_despacho
                 WHERE d.id_institucion = :i
                 GROUP BY d.id_despacho, d.numero_orden, d.fecha_despacho, d.estado_orden,
                          f.id_unidad, mo.modelo_unidad, po.personal_nombre, po.personal_apellido
                 ORDER BY d.fecha_despacho DESC, d.id_despacho DESC
                 LIMIT {$limit}",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) { return []; }
    }

    /* ============================================================
       NUEVOS · FLOTA / OPERACIONES
       ============================================================ */
    public function getOperacionesDashboard(int $idInst): array
    {
        $status = ['operativas'=>0,'inoperativas'=>0,'mantenimiento'=>0,'criticas'=>0];
        $aceite = ['requerido'=>0,'proximo'=>0,'ok'=>0];
        $grouped = [];

        try {
            $rows = $this->fetchAll(
                "SELECT status_unidad, COUNT(*) t FROM table_flota
                 WHERE status_unidad BETWEEN 1 AND 5
                   AND id_institucion = :i
                 GROUP BY status_unidad",
                ['i'=>$idInst]
            );
            foreach ($rows as $row) {
                switch ((int)$row['status_unidad']) {
                    case 1: $status['operativas'] = (int)$row['t']; break;
                    case 2: $status['inoperativas'] = (int)$row['t']; break;
                    case 3: $status['mantenimiento'] = (int)$row['t']; break;
                    case 5: $status['criticas'] = (int)$row['t']; break;
                }
            }

            $aceRows = $this->fetchAll(
                "SELECT tf.id_flota,
                        COALESCE((SELECT km.kilometraje_actual FROM table_flota_kilometraje km
                                  WHERE km.id_flota = tf.id_flota
                                  ORDER BY km.fecha_actualizacion DESC, km.id_kilometraje DESC LIMIT 1),0) km_actual,
                        COALESCE((SELECT CASE WHEN ah.kilometraje_cambio > 0 THEN ah.kilometraje_cambio + 5000 ELSE 0 END
                                  FROM table_flota_aceite_historial ah
                                  WHERE ah.id_flota = tf.id_flota
                                  ORDER BY ah.fecha_cambio DESC, ah.id_aceite_historial DESC LIMIT 1),0) prox
                 FROM table_flota tf
                 WHERE tf.status_unidad = 1 AND tf.id_institucion = :i",
                ['i'=>$idInst]
            );
            foreach ($aceRows as $row) {
                $km = (int)$row['km_actual'];
                $prox = (int)$row['prox'];
                if ($prox <= 0) continue;
                $diff = $prox - $km;
                if ($diff <= 0) $aceite['requerido']++;
                elseif ($diff <= 1000) $aceite['proximo']++;
                else $aceite['ok']++;
            }

            $grouped = $this->fetchAll(
                "SELECT CONCAT(fm.marca_unidad,' / ',fmo.modelo_unidad) AS unidad_grupo,
                        fm.marca_unidad, fmo.modelo_unidad,
                        f.transmision, f.tipo_combustible,
                        COUNT(*) AS total,
                        SUM(CASE WHEN f.status_unidad = 1 THEN 1 ELSE 0 END) AS operativas,
                        SUM(CASE WHEN f.status_unidad <> 1 THEN 1 ELSE 0 END) AS inoperativas
                 FROM table_flota f
                 INNER JOIN table_flota_marca fm ON fm.id_marca = f.id_marca
                 INNER JOIN table_flota_modelo fmo ON fmo.id_modelo = f.id_modelo
                 WHERE f.status_unidad IN (1,2,3,5)
                   AND f.id_institucion = :i
                 GROUP BY fm.marca_unidad, fmo.modelo_unidad, f.transmision, f.tipo_combustible
                 ORDER BY fm.marca_unidad, fmo.modelo_unidad",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) {}

        return [
            'status'        => $status,
            'aceite_status' => $aceite,
            'grouped'       => $grouped,
        ];
    }

    /* ============================================================
       NUEVOS · COMPRAS
       ============================================================ */
    public function getComprasDashboardData(int $idInst): array
    {
        $r = [
            'requisiciones_pendientes'=>0,
            'ordenes_aprobadas'=>0,
            'articulos_sin_stock'=>0,
            'articulos_stock_bajo'=>0,
            'ordenes_despachadas_mes'=>0,
            'ordenes_costeadas_mes'=>0,
            'total_divisa_mes'=>0,
            'total_bs_mes'=>0,
        ];

        try {
            $r['requisiciones_pendientes'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_alm_requisicion
                 WHERE status_requisicion = 1",
                []
            )['t'] ?? 0);

            $r['ordenes_aprobadas'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_alm_despacho
                 WHERE id_institucion = :i AND estado_orden = 2",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['articulos_sin_stock'] = (int)($this->fetchOne(
                "SELECT COUNT(DISTINCT p.id_producto) t
                 FROM table_alm_producto p
                 LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                 WHERE p.id_institucion = :i
                   AND COALESCE(rp.cant_producto, 0) <= 0",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['articulos_stock_bajo'] = (int)($this->fetchOne(
                "SELECT COUNT(DISTINCT p.id_producto) t
                 FROM table_alm_producto p
                 LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                 WHERE p.id_institucion = :i
                   AND COALESCE(rp.cant_producto, 0) > 0
                   AND COALESCE(rp.cant_producto, 0) < 10",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['ordenes_despachadas_mes'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_alm_despacho
                 WHERE id_institucion = :i AND estado_orden = 3
                   AND MONTH(fecha_despacho) = MONTH(CURDATE())
                   AND YEAR(fecha_despacho)  = YEAR(CURDATE())",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['ordenes_costeadas_mes'] = (int)($this->fetchOne(
                "SELECT COUNT(DISTINCT cp.id_despacho) t
                 FROM table_compras_costos cc
                 INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
                 WHERE cp.id_institucion = :i
                   AND MONTH(cc.fecha_costeo) = MONTH(CURDATE())
                   AND YEAR(cc.fecha_costeo)  = YEAR(CURDATE())",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['total_divisa_mes'] = (float)($this->fetchOne(
                "SELECT COALESCE(SUM(cc.monto_divisa),0) t
                 FROM table_compras_costos cc
                 INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
                 WHERE cp.id_institucion = :i
                   AND MONTH(cc.fecha_costeo) = MONTH(CURDATE())
                   AND YEAR(cc.fecha_costeo)  = YEAR(CURDATE())",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $r['total_bs_mes'] = (float)($this->fetchOne(
                "SELECT COALESCE(SUM(cc.monto_bs),0) t
                 FROM table_compras_costos cc
                 INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
                 WHERE cp.id_institucion = :i
                   AND MONTH(cc.fecha_costeo) = MONTH(CURDATE())
                   AND YEAR(cc.fecha_costeo)  = YEAR(CURDATE())",
                ['i'=>$idInst]
            )['t'] ?? 0);
        } catch (\Throwable $e) {}

        return $r;
    }

    public function getTopProductosCosteados(int $idInst, int $limit = 10): array
    {
        try {
            return $this->fetchAll(
                "SELECT p.producto, ep.enlace_producto,
                        pr.empresa_proveedor AS proveedor,
                        COUNT(cc.id_costo) AS veces_costeadas,
                        COALESCE(SUM(cc.monto_divisa),0) AS total_divisa,
                        COALESCE(SUM(cc.monto_bs),0)     AS total_bs
                 FROM table_compras_costos cc
                 INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
                 INNER JOIN table_alm_producto p ON p.id_producto = cp.id_producto
                 LEFT JOIN table_alm_enlace_producto ep ON ep.id_enlace_producto = p.id_enlace_producto
                 LEFT JOIN table_alm_relacion_producto rp ON rp.id_producto = p.id_producto
                 LEFT JOIN table_proveedor pr ON pr.id_proveedor = rp.id_proveedor
                 WHERE cp.id_institucion = :i
                   AND MONTH(cc.fecha_costeo) = MONTH(CURDATE())
                   AND YEAR(cc.fecha_costeo)  = YEAR(CURDATE())
                 GROUP BY p.id_producto, p.producto, ep.enlace_producto, pr.empresa_proveedor
                 ORDER BY veces_costeadas DESC, total_bs DESC
                 LIMIT {$limit}",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) { return []; }
    }

    public function getUltimasRequisiciones(int $idInst, int $limit = 10): array
    {
        try {
            return $this->fetchAll(
                "SELECT d.id_despacho, d.numero_orden, d.fecha_despacho, d.estado_orden,
                        f.id_unidad, mo.modelo_unidad,
                        CONCAT_WS(' ', ps.personal_nombre, NULLIF(ps.personal_apellido,'0')) AS solicitante,
                        COUNT(rd.id_producto) AS total_articulos
                 FROM table_alm_despacho d
                 LEFT JOIN table_flota f ON f.id_flota = d.id_flota
                 LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                 LEFT JOIN table_personal ps ON ps.id_personal = d.operador_id
                 LEFT JOIN table_alm_relacion_despacho rd ON rd.id_despacho = d.id_despacho
                 WHERE d.id_institucion = :i AND d.estado_orden = 1
                 GROUP BY d.id_despacho, d.numero_orden, d.fecha_despacho, d.estado_orden,
                          f.id_unidad, mo.modelo_unidad, ps.personal_nombre, ps.personal_apellido
                 ORDER BY d.fecha_despacho DESC, d.id_despacho DESC
                 LIMIT {$limit}",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) { return []; }
    }

    public function getUltimasComprasCosteadas(int $idInst, int $limit = 10): array
    {
        try {
            return $this->fetchAll(
                "SELECT d.id_despacho, d.numero_orden, d.fecha_despacho,
                        f.id_unidad, mo.modelo_unidad,
                        COUNT(cc.id_costo) AS articulos_costeados,
                        COALESCE(SUM(cc.monto_divisa),0) AS total_divisa,
                        COALESCE(SUM(cc.monto_bs),0)     AS total_bs
                 FROM table_compras_costos cc
                 INNER JOIN table_compras_pendientes cp ON cp.id_compra_pendiente = cc.id_compra_pendiente
                 INNER JOIN table_alm_despacho d ON d.id_despacho = cp.id_despacho
                 LEFT JOIN table_flota f ON f.id_flota = d.id_flota
                 LEFT JOIN table_flota_modelo mo ON mo.id_modelo = f.id_modelo
                 WHERE cp.id_institucion = :i
                 GROUP BY d.id_despacho, d.numero_orden, d.fecha_despacho, f.id_unidad, mo.modelo_unidad
                 ORDER BY MAX(cc.fecha_costeo) DESC, d.id_despacho DESC
                 LIMIT {$limit}",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) { return []; }
    }

    /* ============================================================
       NUEVOS · BIENES
       ============================================================ */
    public function getBienesDashboardData(int $idInst): array
    {
        $summary = ['total_bienes'=>0,'total_activos'=>0,'total_reparacion'=>0,'total_baja'=>0];
        $recent = [];

        try {
            $summary['total_bienes'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_bienes_inventario WHERE id_institucion = :i",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $summary['total_activos'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_bienes_inventario
                 WHERE id_institucion = :i AND status_bien = '1'",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $summary['total_reparacion'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_bienes_inventario
                 WHERE id_institucion = :i AND status_bien = '3'",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $summary['total_baja'] = (int)($this->fetchOne(
                "SELECT COUNT(*) t FROM table_bienes_inventario
                 WHERE id_institucion = :i AND status_bien = '4'",
                ['i'=>$idInst]
            )['t'] ?? 0);

            $recent = $this->fetchAll(
                "SELECT b.id_bien, b.descripcion_bien, b.fecha_adquisicion,
                        d.departamento_bien
                 FROM table_bienes_inventario b
                 LEFT JOIN table_bienes_departamentos d ON d.depatamento_bien_id = b.bien_depatamento_id
                 WHERE b.id_institucion = :i
                 ORDER BY b.fecha_creacion DESC, b.id_bien DESC
                 LIMIT 10",
                ['i'=>$idInst]
            );
        } catch (\Throwable $e) {}

        return ['summary' => $summary, 'recent' => $recent];
    }

    /* ============================================================
       NUEVOS · ESTACIÓN (no multi-institución, pero se filtra por estación)
       ============================================================ */
    public function getEstacionDashboardData(int $idUser): array
    {
        $r = ['total_ventas'=>0,'total_litros'=>0,'user_activo'=>0];

        try {
            $hoy = date('Y-m-d');
            $res = $this->fetchOne(
                "SELECT COUNT(v.id_venta) AS total_ventas,
                        COALESCE(SUM(v.litros),0) AS total_litros
                 FROM table_es_venta v
                 WHERE v.fecha_venta = :f AND v.status_ticket = 1
                   AND v.id_user = :u",
                ['f'=>$hoy, 'u'=>$idUser]
            );
            if ($res) {
                $r['total_ventas'] = (int)$res['total_ventas'];
                $r['total_litros'] = (float)$res['total_litros'];
            }

            $r['user_activo'] = (int)($this->fetchOne(
                "SELECT COUNT(DISTINCT id_user) t FROM table_es_venta
                 WHERE fecha_venta = :f AND status_ticket = 1",
                ['f'=>$hoy]
            )['t'] ?? 0);
        } catch (\Throwable $e) {}

        return $r;
    }

    public function getAvailableMonths(): array
    {
        try {
            return $this->fetchAll(
                "SELECT DISTINCT DATE_FORMAT(fecha_venta,'%Y-%m') AS mes
                 FROM table_es_venta
                 ORDER BY mes DESC"
            );
        } catch (\Throwable $e) { return []; }
    }

    public function getMonthlyLiters(string $start, string $end): array
    {
        try {
            return $this->fetchAll(
                "SELECT DATE_FORMAT(fecha_venta,'%Y-%m') AS mes_venta,
                        COALESCE(SUM(CAST(litros AS DECIMAL(10,2))),0) AS total_litros
                 FROM table_es_venta
                 WHERE DATE_FORMAT(fecha_venta,'%Y-%m') BETWEEN :s AND :e
                 GROUP BY mes_venta
                 ORDER BY mes_venta ASC",
                ['s'=>$start, 'e'=>$end]
            );
        } catch (\Throwable $e) { return []; }
    }

    public function getDailySalesByUser(string $fecha): array
    {
        try {
            return $this->fetchAll(
                "SELECT CONCAT_WS(' ', p.personal_nombre, NULLIF(p.personal_apellido,'0')) AS usuario,
                        COALESCE(SUM(CAST(v.litros AS DECIMAL(10,2))),0) AS total_litros
                 FROM table_es_venta v
                 INNER JOIN table_usuarios u ON u.usuario_id = v.id_user
                 LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                 WHERE v.fecha_venta = :f AND v.status_ticket = 1
                 GROUP BY v.id_user, p.personal_nombre, p.personal_apellido
                 ORDER BY total_litros DESC",
                ['f'=>$fecha]
            );
        } catch (\Throwable $e) { return []; }
    }
}
<?php
class PublicoModel extends Mysql {
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get despachos from almacén for public view
     */
    public function getDespachosPublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                d.id_despacho,
                d.id_flota,
                f.id_unidad,
                d.operador,
                d.mecanico,
                d.despachador,
                d.fecha_despacho,
                d.observacion,
                d.estado_orden,
                d.status_despacho
            FROM table_alm_despacho d
            LEFT JOIN table_flota f ON d.id_flota = f.id_flota
            WHERE d.fecha_despacho BETWEEN ? AND ?
            ORDER BY d.fecha_despacho DESC
        ";
        $despachos = $this->select_all($query, [$fechaInicio, $fechaFin]);
        
        // Get products for each despacho
        if (!empty($despachos)) {
            $ids = array_column($despachos, 'id_despacho');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $queryProductos = "
                SELECT 
                    rd.id_despacho,
                    p.producto,
                    p.present_producto,
                    rd.cant_despacho
                FROM table_alm_relacion_despacho rd
                JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                WHERE rd.id_despacho IN ($placeholders)
            ";
            $productos = $this->select_all($queryProductos, $ids);
            
            // Group products by despacho
            $productosByDespacho = [];
            foreach ($productos as $prod) {
                $productosByDespacho[$prod['id_despacho']][] = $prod;
            }
            
            // Add products to despachos
            foreach ($despachos as &$despacho) {
                $despacho['productos'] = $productosByDespacho[$despacho['id_despacho']] ?? [];
            }
        }
        
        return $despachos;
    }
    
    /**
     * Get ventas from estación for public view
     */
    public function getVentasPublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                v.id_venta,
                v.id_user,
                v.id_tipo_pago,
                v.id_tipo_vehiculo,
                v.litros,
                v.monto,
                v.id_cierre_diario,
                v.fecha_venta,
                v.hora_venta,
                v.tasa_dia,
                v.id_rol,
                v.status_ticket,
                u.usuario_nick,
                tv.nombre as tipo_vehiculo,
                tp.nombre as tipo_pago
            FROM table_es_venta v
            LEFT JOIN table_usuarios u ON v.id_user = u.usuario_id
            LEFT JOIN table_es_tipos_vehiculo tv ON v.id_tipo_vehiculo = tv.id_tipo_vehiculo
            LEFT JOIN table_es_tipos_pago tp ON v.id_tipo_pago = tp.id_tipo_pago
            WHERE v.fecha_venta BETWEEN ? AND ?
            ORDER BY v.fecha_venta DESC, v.hora_venta DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get mantenimientos from flota for public view
     */
    public function getMantenimientosPublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                m.id_unidad_mantenimiento,
                m.id_flota,
                f.id_unidad,
                m.ruta_unidad,
                m.operardor_unidad,
                m.nomb_mecanico,
                m.km_unidad,
                m.tipo_mantenimiento,
                m.diagnostico,
                m.recomendacion,
                m.obsOperador,
                m.obsSupervisor,
                m.obsSalida,
                m.fecha_entrada,
                m.fecha_salida,
                m.status_mantenimiento,
                m.usuario_id,
                u.usuario_nick
            FROM table_flota_mantenimiento m
            LEFT JOIN table_flota f ON m.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON m.usuario_id = u.usuario_id
            WHERE m.fecha_entrada BETWEEN ? AND ?
            ORDER BY m.fecha_entrada DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get compras/requisiciones for public view
     */
    public function getComprasPublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                r.id_requisicion,
                r.id_despacho_fk,
                r.id_flota,
                f.id_unidad,
                r.mecanico_cedula,
                r.tipo_orden,
                r.observacion,
                r.user_id_creador,
                r.fecha_creacion,
                r.status_requisicion,
                d.operador,
                d.despachador,
                d.estado_orden,
                u.usuario_nick as creador_nick
            FROM table_alm_requisicion r
            LEFT JOIN table_flota f ON r.id_flota = f.id_flota
            LEFT JOIN table_alm_despacho d ON r.id_despacho_fk = d.id_despacho
            LEFT JOIN table_usuarios u ON r.user_id_creador = u.usuario_id
            WHERE r.fecha_creacion BETWEEN ? AND ?
            ORDER BY r.fecha_creacion DESC
        ";
        $requisiciones = $this->select_all($query, [$fechaInicio, $fechaFin]);
        
        // Get products for each requisicion
        if (!empty($requisiciones)) {
            $ids = array_column($requisiciones, 'id_requisicion');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $queryProductos = "
                SELECT 
                    rd.id_requisicion_fk,
                    p.producto,
                    p.present_producto,
                    rd.cantidad_solicitada
                FROM table_alm_requisicion_detalle rd
                JOIN table_alm_producto p ON rd.id_producto = p.id_producto
                WHERE rd.id_requisicion_fk IN ($placeholders)
            ";
            $productos = $this->select_all($queryProductos, $ids);
            
            // Group products by requisicion
            $productosByReq = [];
            foreach ($productos as $prod) {
                $productosByReq[$prod['id_requisicion_fk']][] = $prod;
            }
            
            // Add products to requisiciones
            foreach ($requisiciones as &$req) {
                $req['productos'] = $productosByReq[$req['id_requisicion']] ?? [];
            }
        }
        
        return $requisiciones;
    }
    
    /**
     * Get cambios de aceite for public view
     */
    public function getCambiosAceitePublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                ah.id_aceite_historial,
                ah.id_flota,
                f.id_unidad,
                ah.fecha_cambio,
                ah.kilometraje_cambio,
                ah.kilometraje_anterior,
                ah.kilometraje_proximo_cambio,
                ah.usuario_id,
                ah.observaciones,
                u.usuario_nick
            FROM table_flota_aceite_historial ah
            LEFT JOIN table_flota f ON ah.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON ah.usuario_id = u.usuario_id
            WHERE ah.fecha_cambio BETWEEN ? AND ?
            ORDER BY ah.fecha_cambio DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get kilometraje updates for public view
     */
    public function getKilometrajePublic($fechaInicio, $fechaFin) {
        $query = "
            SELECT 
                k.id_kilometraje,
                k.id_flota,
                f.id_unidad,
                k.kilometraje_actual,
                k.fecha_actualizacion,
                k.usuario_id,
                u.usuario_nick
            FROM table_flota_kilometraje k
            LEFT JOIN table_flota f ON k.id_flota = f.id_flota
            LEFT JOIN table_usuarios u ON k.usuario_id = u.usuario_id
            WHERE DATE(k.fecha_actualizacion) BETWEEN ? AND ?
            ORDER BY k.fecha_actualizacion DESC
        ";
        return $this->select_all($query, [$fechaInicio, $fechaFin]);
    }
    
    /**
     * Get summary statistics for dashboard
     */
    public function getResumenPublic($fechaInicio, $fechaFin) {
        $resumen = [];
        
        // Total despachos
        $query = "SELECT COUNT(*) as total FROM table_alm_despacho WHERE fecha_despacho BETWEEN ? AND ?";
        $resumen['despachos'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total ventas
        $query = "SELECT COUNT(*) as total FROM table_es_venta WHERE fecha_venta BETWEEN ? AND ?";
        $resumen['ventas'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total mantenimientos
        $query = "SELECT COUNT(*) as total FROM table_flota_mantenimiento WHERE fecha_entrada BETWEEN ? AND ?";
        $resumen['mantenimientos'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total requisiciones
        $query = "SELECT COUNT(*) as total FROM table_alm_requisicion WHERE fecha_creacion BETWEEN ? AND ?";
        $resumen['compras'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total cambios aceite
        $query = "SELECT COUNT(*) as total FROM table_flota_aceite_historial WHERE fecha_cambio BETWEEN ? AND ?";
        $resumen['aceite'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        // Total kilometraje
        $query = "SELECT COUNT(*) as total FROM table_flota_kilometraje WHERE DATE(fecha_actualizacion) BETWEEN ? AND ?";
        $resumen['kilometraje'] = (int)$this->contar($query, [$fechaInicio, $fechaFin]);
        
        $resumen['total'] = array_sum($resumen);
        
        return $resumen;
    }
}
<?php
namespace App\Models;

use App\Core\Model;

class ModelDashboard extends Model
{
    public function notificaciones(int $limit = 10): array
    {
        $sql = "SELECT id_notificacion, tipo_notificacion, mensaje,
                       leido, fecha_creacion
                FROM table_notificaciones
                ORDER BY fecha_creacion DESC
                LIMIT {$limit}";
        try {
            return $this->fetchAll($sql);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function totales(): array
    {
        $data = [
            'usuarios' => 0,
            'flota'    => 0,
            'personal' => 0,
            'estaciones' => 0,
        ];
        try {
            $data['usuarios']   = (int)$this->fetchOne("SELECT COUNT(*) t FROM table_usuarios WHERE usuario_status = 1")['t'];
            $data['flota']      = (int)$this->fetchOne("SELECT COUNT(*) t FROM table_flota")['t'];
            $data['personal']   = (int)$this->fetchOne("SELECT COUNT(*) t FROM table_personal")['t'];
            $data['estaciones'] = (int)$this->fetchOne("SELECT COUNT(*) t FROM table_es_estacion")['t'];
        } catch (\Throwable $e) {
            // silencioso
        }
        return $data;
    }
}
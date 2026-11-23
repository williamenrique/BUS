<?php
namespace App\Models;

use App\Core\Model;

class ModelBackup extends Model
{
    /* ============================================================
       AUTOINSTALACIÓN (idempotente)
       ============================================================ */
    public function ensureTable(): void
    {
        $this->query("CREATE TABLE IF NOT EXISTS `table_backup_historial` (
            `id_backup`       INT(11) NOT NULL AUTO_INCREMENT,
            `archivo`         VARCHAR(255) NOT NULL,
            `tamanio_bytes`   BIGINT(20) NOT NULL DEFAULT 0,
            `checksum_sha256` VARCHAR(64) DEFAULT NULL,
            `tipo`            ENUM('manual','automatico','seguridad','subido') NOT NULL DEFAULT 'manual',
            `accion`          ENUM('backup','restore') NOT NULL DEFAULT 'backup',
            `estado`          ENUM('ok','error','en_progreso') NOT NULL DEFAULT 'ok',
            `mensaje`         TEXT DEFAULT NULL,
            `duracion_seg`    DECIMAL(10,2) DEFAULT NULL,
            `usuario_id`      INT(11) DEFAULT NULL,
            `usuario_nick`    VARCHAR(50) DEFAULT NULL,
            `ip_address`      VARCHAR(45) DEFAULT NULL,
            `fecha_creacion`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_proceso`   DATETIME DEFAULT NULL,
            PRIMARY KEY (`id_backup`),
            KEY `idx_backup_tipo`    (`tipo`),
            KEY `idx_backup_accion`  (`accion`),
            KEY `idx_backup_estado`  (`estado`),
            KEY `idx_backup_fecha`   (`fecha_creacion`),
            KEY `idx_backup_usuario` (`usuario_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci");
    }

    /* ============================================================
       FIND
       ============================================================ */
    public function find(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM table_backup_historial WHERE id_backup = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /* ============================================================
       CONTAR REGISTROS POR NOMBRE DE ARCHIVO
       (excluyendo opcionalmente un ID)
       ============================================================ */
    public function contarPorArchivo(string $archivo, ?int $excludeId = null): int
    {
        $sql = "SELECT COUNT(*) t FROM table_backup_historial WHERE archivo = :a";
        $params = ['a' => $archivo];

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= " AND id_backup <> :id";
            $params['id'] = $excludeId;
        }

        return (int)($this->fetchOne($sql, $params)['t'] ?? 0);
    }

    /* ============================================================
       LISTADO PAGINADO
       ============================================================ */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = []): array
    {
        $where = "1=1";
        $params = [];

        if ($q !== '') {
            $where .= " AND (archivo LIKE :q1 OR usuario_nick LIKE :q2 OR mensaje LIKE :q3)";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
        }
        if (!empty($filtros['tipo']))   { $where .= " AND tipo = :tipo";     $params['tipo'] = $filtros['tipo']; }
        if (!empty($filtros['accion'])) { $where .= " AND accion = :accion"; $params['accion'] = $filtros['accion']; }
        if (!empty($filtros['estado'])) { $where .= " AND estado = :estado"; $params['estado'] = $filtros['estado']; }

        $total = (int)($this->fetchOne("SELECT COUNT(*) t FROM table_backup_historial")['t'] ?? 0);
        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) t FROM table_backup_historial WHERE {$where}", $params
        )['t'] ?? 0);

        $sql = "SELECT * FROM table_backup_historial
                WHERE {$where}
                ORDER BY id_backup DESC
                LIMIT {$limit} OFFSET {$offset}";
        $data = $this->fetchAll($sql, $params);

        return [
            'data'           => $data,
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       INSERTAR
       ============================================================ */
    public function registrar(array $d): int
    {
        $this->query("INSERT INTO table_backup_historial
            (archivo, tamanio_bytes, checksum_sha256, tipo, accion, estado, mensaje,
             duracion_seg, usuario_id, usuario_nick, ip_address, fecha_proceso)
            VALUES
            (:archivo, :tam, :checksum, :tipo, :accion, :estado, :mensaje,
             :duracion, :uid, :nick, :ip, NOW())",
            [
                'archivo'  => $d['archivo'],
                'tam'      => (int)($d['tamanio_bytes'] ?? 0),
                'checksum' => $d['checksum_sha256'] ?? null,
                'tipo'     => $d['tipo']    ?? 'manual',
                'accion'   => $d['accion']  ?? 'backup',
                'estado'   => $d['estado']  ?? 'ok',
                'mensaje'  => $d['mensaje'] ?? null,
                'duracion' => $d['duracion_seg'] ?? null,
                'uid'      => $d['usuario_id']   ?? null,
                'nick'     => $d['usuario_nick'] ?? null,
                'ip'       => $d['ip_address']   ?? ($_SERVER['REMOTE_ADDR'] ?? null),
            ]
        );
        return (int)$this->lastInsertId();
    }

    /* ============================================================
       ELIMINAR REGISTRO POR ID
       ============================================================ */
    public function eliminarPorId(int $id): bool
    {
        return $this->execute(
            "DELETE FROM table_backup_historial WHERE id_backup = :id",
            ['id' => $id]
        );
    }

    /* ============================================================
       STATS
       ============================================================ */
    public function stats(): array
    {
        $ultimo = $this->fetchOne(
            "SELECT archivo, tamanio_bytes, fecha_creacion
             FROM table_backup_historial
             WHERE accion='backup' AND estado='ok'
             ORDER BY id_backup DESC LIMIT 1"
        );
        return [
            'ultimo'         => $ultimo,
            'total_registros'=> (int)($this->fetchOne("SELECT COUNT(*) t FROM table_backup_historial")['t'] ?? 0),
            'restores_ok'    => (int)($this->fetchOne("SELECT COUNT(*) t FROM table_backup_historial WHERE accion='restore' AND estado='ok'")['t'] ?? 0),
            'errores'        => (int)($this->fetchOne("SELECT COUNT(*) t FROM table_backup_historial WHERE estado='error'")['t'] ?? 0),
        ];
    }
}
<?php
namespace App\Models;

use App\Core\Model;

class ModelUsuario extends Model
{
    public function findByNick(string $nick): ?array
    {
        $sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_password,
                       u.usuario_rol_id, u.usuario_departamento_id,
                       u.usuario_estacion_id, u.usuario_status,
                       u.usuario_imagen, u.usuario_id_personal,
                       r.rol_nombre, d.departamento_nombre,
                       p.personal_nombre, p.personal_apellido,
                       p.personal_email, p.personal_cedula
                FROM table_usuarios u
                INNER JOIN table_per_roles r ON r.rol_id = u.usuario_rol_id
                INNER JOIN table_departamentos d ON d.departamento_id = u.usuario_departamento_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE u.usuario_nick = :nick
                  AND u.usuario_status = 1
                LIMIT 1";
        return $this->fetchOne($sql, ['nick' => $nick]);
    }

    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_status,
                       p.personal_email, p.personal_nombre, p.personal_apellido
                FROM table_usuarios u
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE p.personal_email = :email
                  AND u.usuario_status = 1
                LIMIT 1";
        return $this->fetchOne($sql, ['email' => $email]);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT u.*, r.rol_nombre, d.departamento_nombre,
                       p.personal_nombre, p.personal_apellido, p.personal_email
                FROM table_usuarios u
                INNER JOIN table_per_roles r ON r.rol_id = u.usuario_rol_id
                INNER JOIN table_departamentos d ON d.departamento_id = u.usuario_departamento_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE u.usuario_id = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    public function registrarRecuperacion(int $userId, string $identificador): bool
    {
        $sql = "INSERT INTO table_recovery_requests (user_id, identifier_provided)
                VALUES (:uid, :ident)";
        return $this->execute($sql, ['uid' => $userId, 'ident' => $identificador]);
    }

    public function registrarSesion(string $sessionId, int $userId, string $ip, string $ua): bool
    {
        $sql = "INSERT INTO table_usuario_sessions
                (session_id, usuario_id, ip_address, usuario_agent, created_at, last_activity)
                VALUES (:sid, :uid, :ip, :ua, NOW(), NOW())";
        return $this->execute($sql, [
            'sid' => $sessionId,
            'uid' => $userId,
            'ip'  => $ip,
            'ua'  => $ua,
        ]);
    }

    public function getMenuUsuario(int $userId, int $departamentoId): array
    {
        $sql = "SELECT DISTINCT m.menu_id, m.menu_nombre, m.menu_icono,
                       m.menu_ruta, m.menu_orden, m.menu_padre_id,
                       m.menu_es_desplegable, m.menu_scope
                FROM table_men_menu m
                LEFT JOIN table_men_usuario_menu um ON um.menu_id = m.menu_id
                LEFT JOIN table_men_departamento_menu dm ON dm.menu_id = m.menu_id
                WHERE m.menu_estado = 1
                  AND (um.usuario_id = :uid OR dm.departamento_id = :dept)
                ORDER BY m.menu_orden ASC";
        return $this->fetchAll($sql, ['uid' => $userId, 'dept' => $departamentoId]);
    }

    public function actualizarPassword(int $userId, string $newHash): bool{
        $sql = "UPDATE table_usuarios
                SET usuario_password = :hash
                WHERE usuario_id = :id";
        return $this->execute($sql, ['hash' => $newHash, 'id' => $userId]);
    }

    /**
     * Elimina TODAS las sesiones activas de un usuario.
     * Se usa al hacer login (para matar la anterior) y al hacer logout.
     */
    public function eliminarSesiones(int $userId): bool
    {
        $sql = "DELETE FROM table_usuario_sessions WHERE usuario_id = :uid";
        return $this->execute($sql, ['uid' => $userId]);
    }

    /**
     * Elimina la sesión específica por session_id.
     */
    public function eliminarSesionPorId(string $sessionId): bool
    {
        $sql = "DELETE FROM table_usuario_sessions WHERE session_id = :sid";
        return $this->execute($sql, ['sid' => $sessionId]);
    }

    /**
     * Devuelve el session_id activo del usuario (el último registrado).
     * Retorna null si no tiene sesiones.
     */
    public function obtenerSesionActiva(int $userId): ?string
    {
        $sql = "SELECT session_id
                FROM table_usuario_sessions
                WHERE usuario_id = :uid
                ORDER BY id DESC
                LIMIT 1";
        $row = $this->fetchOne($sql, ['uid' => $userId]);
        return $row['session_id'] ?? null;
    }

    /**
     * Verifica que el session_id actual coincida con el registrado.
     */
    public function sesionEsValida(int $userId, string $sessionId): bool
    {
        $sql = "SELECT 1
                FROM table_usuario_sessions
                WHERE usuario_id = :uid AND session_id = :sid
                LIMIT 1";
        return (bool)$this->fetchOne($sql, ['uid' => $userId, 'sid' => $sessionId]);
    }

    /**
     * Actualiza last_activity de la sesión actual (heartbeat).
     */
    public function tocarSesion(string $sessionId): void
    {
        $sql = "UPDATE table_usuario_sessions
                SET last_activity = NOW()
                WHERE session_id = :sid";
        $this->execute($sql, ['sid' => $sessionId]);
    }

    /**
     * Elimina sesiones inactivas más viejas que $minutes minutos.
     * Se ejecuta al hacer login para mantener la tabla limpia.
     */
    public function limpiarSesionesExpiradas(int $minutes = 480): void
    {
        $sql = "DELETE FROM table_usuario_sessions
                WHERE last_activity < (NOW() - INTERVAL :min MINUTE)";
        $this->execute($sql, ['min' => $minutes]);
    }

    /**
     * Cierra la sesión activa del usuario excepto la actual.
     * (Utilidad extra por si se necesita forzar desde admin.)
     */
    public function cerrarOtrasSesiones(int $userId, string $keepSessionId): void
    {
        $sql = "DELETE FROM table_usuario_sessions
                WHERE usuario_id = :uid AND session_id <> :sid";
        $this->execute($sql, ['uid' => $userId, 'sid' => $keepSessionId]);
    }

    /**
     * Devuelve el menú del usuario en estructura jerárquica (padres con hijos).
     * Si el usuario no tiene menús asignados, devuelve los del departamento.
     */
    public function getMenuJerarquico(int $userId, int $departamentoId): array
    {
        $sql = "SELECT DISTINCT m.menu_id, m.menu_nombre, m.menu_icono,
                    m.menu_ruta, m.menu_orden, m.menu_padre_id,
                    m.menu_es_desplegable, m.menu_scope
                FROM table_men_menu m
                LEFT JOIN table_men_usuario_menu um ON um.menu_id = m.menu_id
                LEFT JOIN table_men_departamento_menu dm ON dm.menu_id = m.menu_id
                WHERE m.menu_estado = 1
                AND (um.usuario_id = :uid OR dm.departamento_id = :dept)
                ORDER BY m.menu_orden ASC, m.menu_id ASC";

        $flat = $this->fetchAll($sql, ['uid' => $userId, 'dept' => $departamentoId]);

        // Construir árbol
        $byId = [];
        foreach ($flat as $item) {
            $item['children'] = [];
            $byId[$item['menu_id']] = $item;
        }

        $tree = [];
        foreach ($byId as $id => &$item) {
            $parent = $item['menu_padre_id'];
            if ($parent && isset($byId[$parent])) {
                $byId[$parent]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        unset($item);

        return $tree;
    }
}
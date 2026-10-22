<?php
namespace App\Models;

use App\Core\Model;

class ModelUsuario extends Model{

    /* ============================================================
       CONSULTAS BÁSICAS
       ============================================================ */

    /**
     * Busca un usuario por nick (case-insensitive para soportar
     * usuarios viejos en minúscula y nuevos en MAYÚSCULA).
     */
    public function findByNick(string $nick): ?array{
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
                WHERE LOWER(u.usuario_nick) = LOWER(:nick)
                  AND u.usuario_status = 1
                LIMIT 1";
        return $this->fetchOne($sql, ['nick' => $nick]);
    }

    public function findByEmail(string $email): ?array{
        $sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_status,
                       p.personal_email, p.personal_nombre, p.personal_apellido
                FROM table_usuarios u
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE p.personal_email = :email
                  AND u.usuario_status = 1
                LIMIT 1";
        return $this->fetchOne($sql, ['email' => $email]);
    }
    /**
     * Busca un usuario por cédula del personal vinculado.
     * Acepta cualquier formato: "19423408", "V-19423408", "V19423408", "v-19423408".
     * Compara solo los dígitos (ignora prefijo V-, E-, J-, G-, P-).
     */
    public function findByCedula(string $cedula): ?array{
        // Extraer solo dígitos de la entrada
        $soloDigitos = preg_replace('/\D/', '', $cedula);
        if ($soloDigitos === '') return null;

        $sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_status,
                       p.personal_nombre, p.personal_apellido, p.personal_email,
                       p.personal_cedula
                FROM table_usuarios u
                INNER JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
                        p.personal_cedula, 'V-', ''), 'E-', ''), 'J-', ''), 'G-', ''), 'P-', '')
                      = :ced
                  AND u.usuario_status = 1
                LIMIT 1";

        return $this->fetchOne($sql, ['ced' => $soloDigitos]);
    }

    public function findById(int $id): ?array{
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

    /**
     * Consulta completa para el formulario de edición.
     */
    public function find(int $id): ?array{
        $sql = "SELECT u.*, r.rol_nombre, d.departamento_nombre,
                       e.estacion AS estacion_nombre,
                       p.personal_nombre, p.personal_apellido
                FROM table_usuarios u
                LEFT JOIN table_per_roles r  ON r.rol_id = u.usuario_rol_id
                LEFT JOIN table_departamentos d ON d.departamento_id = u.usuario_departamento_id
                LEFT JOIN table_es_estacion e ON e.id_estacion = u.usuario_estacion_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE u.usuario_id = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    /* ============================================================
       LISTADO PAGINADO
       ============================================================ */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = []): array{
        $where  = "1=1";
        $params = [];

        if ($q !== ''){
            $where .= " AND (
                            u.usuario_nick         LIKE :q1
                         OR p.personal_nombre      LIKE :q2
                         OR p.personal_apellido    LIKE :q3
                         OR p.personal_email       LIKE :q4
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
        }

        if (isset($filtros['status']) && $filtros['status'] !== ''){
            $where .= " AND u.usuario_status = :status";
            $params['status'] = (int)$filtros['status'];
        }
        if (!empty($filtros['rol'])){
            $where .= " AND u.usuario_rol_id = :rol";
            $params['rol'] = (int)$filtros['rol'];
        }
        if (!empty($filtros['departamento'])){
            $where .= " AND u.usuario_departamento_id = :depto";
            $params['depto'] = (int)$filtros['departamento'];
        }

        $total = (int)($this->fetchOne("SELECT COUNT(*) AS t FROM table_usuarios")['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t
             FROM table_usuarios u
             LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
             WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT u.usuario_id, u.usuario_nick, u.usuario_rol_id,
                       u.usuario_departamento_id, u.usuario_estacion_id,
                       u.usuario_imagen, u.usuario_status, u.usuario_id_personal,
                       u.usuario_creado,
                       r.rol_nombre, d.departamento_nombre,
                       e.estacion AS estacion_nombre,
                       p.personal_nombre, p.personal_apellido, p.personal_cedula
                FROM table_usuarios u
                LEFT JOIN table_per_roles r  ON r.rol_id = u.usuario_rol_id
                LEFT JOIN table_departamentos d ON d.departamento_id = u.usuario_departamento_id
                LEFT JOIN table_es_estacion e ON e.id_estacion = u.usuario_estacion_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE {$where}
                ORDER BY u.usuario_id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'           => $data,
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }

    /* ============================================================
       CRUD
       ============================================================ */

    /**
     * Crea un usuario.
     * - Nick en MAYÚSCULAS
     * - Estación opcional → 0
     * - Imagen siempre string (default o subida)
     */
    public function create(array $d): int{
        $sql = "INSERT INTO table_usuarios
                    (usuario_nick, usuario_password, usuario_rol_id,
                     usuario_departamento_id, usuario_estacion_id,
                     usuario_imagen, usuario_status, usuario_id_personal)
                VALUES
                    (:nick, :pass, :rol,
                     :depto, :estacion,
                     :imagen, :status, :personal)";
        $this->query($sql, [
            'nick'     => strtoupper($d['usuario_nick']),
            'pass'     => $d['usuario_password'],
            'rol'      => (int)$d['usuario_rol_id'],
            'depto'    => (int)$d['usuario_departamento_id'],
            'estacion' => isset($d['usuario_estacion_id']) && $d['usuario_estacion_id'] !== ''
                            ? (int)$d['usuario_estacion_id']
                            : 0,
            'imagen'   => $d['usuario_imagen'] ?? null,
            'status'   => (int)($d['usuario_status'] ?? 1),
            'personal' => !empty($d['usuario_id_personal']) ? (int)$d['usuario_id_personal'] : null,
        ]);
        return (int)$this->lastInsertId();
    }

    /**
     * Actualiza un usuario. No toca nick ni contraseña.
     * Estación → 0 si viene vacía.
     */
    public function update(int $id, array $d): bool{
        $sql = "UPDATE table_usuarios SET
                    usuario_rol_id          = :rol,
                    usuario_departamento_id = :depto,
                    usuario_estacion_id     = :estacion,
                    usuario_status          = :status,
                    usuario_id_personal     = :personal";

        $params = [
            'rol'      => (int)$d['usuario_rol_id'],
            'depto'    => (int)$d['usuario_departamento_id'],
            'estacion' => isset($d['usuario_estacion_id']) && $d['usuario_estacion_id'] !== ''
                            ? (int)$d['usuario_estacion_id']
                            : 0,
            'status'   => (int)($d['usuario_status'] ?? 1),
            'personal' => !empty($d['usuario_id_personal']) ? (int)$d['usuario_id_personal'] : null,
            'id'       => $id,
        ];

        if (!empty($d['usuario_imagen'])){
            $sql .= ", usuario_imagen = :imagen";
            $params['imagen'] = $d['usuario_imagen'];
        }

        $sql .= " WHERE usuario_id = :id";

        return $this->execute($sql, $params);
    }

    /**
     * Actualiza únicamente la imagen de un usuario.
     */
    public function updateImagen(int $id, ?string $path): bool{
        return $this->execute(
            "UPDATE table_usuarios SET usuario_imagen = :img WHERE usuario_id = :id",
            ['img' => $path, 'id' => $id]
        );
    }

    /**
     * Actualiza la contraseña (ya hasheada).
     */
    public function updatePassword(int $id, string $hash): bool{
        return $this->execute(
            "UPDATE table_usuarios SET usuario_password = :h WHERE usuario_id = :id",
            ['h' => $hash, 'id' => $id]
        );
    }

    public function delete(int $id): bool{
        return $this->execute("DELETE FROM table_usuarios WHERE usuario_id = :id", ['id' => $id]);
    }

    public function cambiarStatus(int $id, int $status): bool{
        return $this->execute(
            "UPDATE table_usuarios SET usuario_status = :s WHERE usuario_id = :id",
            ['s' => $status, 'id' => $id]
        );
    }

    /**
     * Verifica existencia de nick (case-insensitive).
     */
    public function existeNick(string $nick, ?int $excludeId = null): bool{
        $sql = "SELECT 1 FROM table_usuarios WHERE LOWER(usuario_nick) = LOWER(:n)";
        $params = ['n' => $nick];
        if ($excludeId){ $sql .= " AND usuario_id <> :id"; $params['id'] = $excludeId; }
        $sql .= " LIMIT 1";
        return (bool)$this->fetchOne($sql, $params);
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function roles(): array{
        return $this->fetchAll(
            "SELECT rol_id, rol_nombre FROM table_per_roles
             WHERE rol_status = 1
             ORDER BY rol_nombre ASC"
        );
    }

    public function departamentosCombo(): array{
        return $this->fetchAll(
            "SELECT departamento_id, departamento_nombre FROM table_departamentos
             WHERE departamento_status = 1
             ORDER BY departamento_nombre ASC"
        );
    }

    public function estacionesCombo(): array{
        return $this->fetchAll(
            "SELECT id_estacion, estacion FROM table_es_estacion
             WHERE status_estacion = 1 OR status_estacion IS NULL
             ORDER BY estacion ASC"
        );
    }

    /**
     * Personal disponible (sin usuario asignado) + el actual si editamos.
     */
    public function personalDisponible(?int $usuarioActualId = null): array{
        $sql = "SELECT p.id_personal, p.personal_nombre, p.personal_apellido, p.personal_cedula
                FROM table_personal p
                WHERE p.personal_status = 1
                  AND (
                        p.id_personal NOT IN (
                            SELECT usuario_id_personal FROM table_usuarios
                            WHERE usuario_id_personal IS NOT NULL
                        )
                        " . ($usuarioActualId ? " OR p.id_personal = (SELECT usuario_id_personal FROM table_usuarios WHERE usuario_id = :uid) " : "") . "
                      )
                ORDER BY p.personal_apellido ASC, p.personal_nombre ASC";

        $params = $usuarioActualId ? ['uid' => $usuarioActualId] : [];
        return $this->fetchAll($sql, $params);
    }

    /* ============================================================
       AUTOGENERACIÓN DE NICK (MAYÚSCULAS)
       ============================================================ */

    /**
     * Devuelve el siguiente nick en MAYÚSCULAS con secuencia GLOBAL.
     * El sufijo es el máximo de TODOS los usuarios + 1.
     *
     * Ej (con máx existente en 23):
     *   "MR" → "MR-24"
     *   "A0" → "A0-25"
     *   "JP" → "JP-26"
     */
    public function siguienteNick(string $prefijo): string{
        $prefijo = strtoupper(trim($prefijo));

        // Busca el número más alto considerando SOLO formatos PREFIX-NNN válidos
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(usuario_nick, '-', -1) AS UNSIGNED)) AS maxnum
                FROM table_usuarios
                WHERE usuario_nick REGEXP '^[A-Za-z0-9]+-[0-9]+$'";
        $row = $this->fetchOne($sql);

        $siguiente = 1;
        if ($row && $row['maxnum'] !== null){
            $siguiente = (int)$row['maxnum'] + 1;
        }

        // Padding a 2 dígitos (03, 24, 99, 100, 101...)
        return $prefijo . '-' . str_pad((string)$siguiente, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Genera el nick sugerido a partir de un personal (MAYÚSCULAS).
     * Devuelve null si no existe.
     */
    public function generarNickDesdePersonal(int $personalId): ?string{
        $sql = "SELECT personal_nombre, personal_apellido
                FROM table_personal
                WHERE id_personal = :id
                LIMIT 1";
        $p = $this->fetchOne($sql, ['id' => $personalId]);
        if (!$p) return null;

        $n = mb_substr(trim((string)$p['personal_nombre']),   0, 1, 'UTF-8');
        $a = mb_substr(trim((string)$p['personal_apellido']), 0, 1, 'UTF-8');

        $prefijo = strtoupper($n . $a);
        $prefijo = preg_replace('/[^A-Z0-9]/', '', $prefijo);
        if ($prefijo === '') $prefijo = 'US';

        return $this->siguienteNick($prefijo);
    }

    /* ============================================================
       RECUPERACIÓN DE CONTRASEÑA
       ============================================================ */
    public function registrarRecuperacion(int $userId, string $identificador): bool{
        $sql = "INSERT INTO table_recovery_requests (user_id, identifier_provided)
                VALUES (:uid, :ident)";
        return $this->execute($sql, ['uid' => $userId, 'ident' => $identificador]);
    }

    /* ============================================================
       SESIONES ÚNICAS
       ============================================================ */
    public function registrarSesion(string $sessionId, int $userId, string $ip, string $ua): bool{
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

    public function eliminarSesiones(int $userId): bool{
        return $this->execute("DELETE FROM table_usuario_sessions WHERE usuario_id = :uid", ['uid' => $userId]);
    }

    public function eliminarSesionPorId(string $sessionId): bool{
        return $this->execute("DELETE FROM table_usuario_sessions WHERE session_id = :sid", ['sid' => $sessionId]);
    }

    public function obtenerSesionActiva(int $userId): ?string{
        $sql = "SELECT session_id FROM table_usuario_sessions
                WHERE usuario_id = :uid ORDER BY id DESC LIMIT 1";
        $row = $this->fetchOne($sql, ['uid' => $userId]);
        return $row['session_id'] ?? null;
    }

    public function sesionEsValida(int $userId, string $sessionId): bool{
        $sql = "SELECT 1 FROM table_usuario_sessions
                WHERE usuario_id = :uid AND session_id = :sid LIMIT 1";
        return (bool)$this->fetchOne($sql, ['uid' => $userId, 'sid' => $sessionId]);
    }

    public function tocarSesion(string $sessionId): void{
        $this->execute(
            "UPDATE table_usuario_sessions SET last_activity = NOW() WHERE session_id = :sid",
            ['sid' => $sessionId]
        );
    }

    public function limpiarSesionesExpiradas(int $minutes = 480): void{
        $this->execute(
            "DELETE FROM table_usuario_sessions WHERE last_activity < (NOW() - INTERVAL :min MINUTE)",
            ['min' => $minutes]
        );
    }

    public function cerrarOtrasSesiones(int $userId, string $keepSessionId): void{
        $this->execute(
            "DELETE FROM table_usuario_sessions WHERE usuario_id = :uid AND session_id <> :sid",
            ['uid' => $userId, 'sid' => $keepSessionId]
        );
    }

    /* ============================================================
       MENÚ
       ============================================================ */
    public function getMenuUsuario(int $userId, int $departamentoId): array{
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

    public function getMenuJerarquico(int $userId, int $departamentoId): array{
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

        $byId = [];
        foreach ($flat as $item){
            $item['children'] = [];
            $byId[$item['menu_id']] = $item;
        }

        $tree = [];
        foreach ($byId as $id => &$item){
            $parent = $item['menu_padre_id'];
            if ($parent && isset($byId[$parent])){
                $byId[$parent]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        unset($item);

        return $tree;
    }

    /**
     * Actualiza solo la contraseña (usada por el flujo legacy de rehash).
     */
    public function actualizarPassword(int $userId, string $newHash): bool{
        return $this->execute(
            "UPDATE table_usuarios SET usuario_password = :hash WHERE usuario_id = :id",
            ['hash' => $newHash, 'id' => $userId]
        );
    }
        /* ============================================================
       PERFIL · Actualizar datos del personal vinculado
       ============================================================ */

    /**
     * Actualiza los campos editables del personal asociado al usuario
     * (nombre, apellido, email, teléfono). No toca cédula, cargo,
     * dirección ni estatus (esos los maneja el módulo Personal).
     */
    public function actualizarPersonal(int $personalId, array $d): bool{
        $sql = "UPDATE table_personal SET
                    personal_nombre   = :nombre,
                    personal_apellido = :apellido,
                    personal_email    = :email,
                    personal_tlf      = :tlf
                WHERE id_personal = :id";
        return $this->execute($sql, [
            'nombre'   => $d['personal_nombre']   ?? '',
            'apellido' => $d['personal_apellido'] ?? '',
            'email'    => $d['personal_email']    ?? '',
            'tlf'      => $d['personal_tlf']      ?? '',
            'id'       => $personalId,
        ]);
    }
}
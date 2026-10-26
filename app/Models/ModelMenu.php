<?php
namespace App\Models;

use App\Core\Model;

class ModelMenu extends Model{

    /* ============================================================
       ÁRBOL DE MENÚS
       ============================================================ */
    public function arbol(): array{
        $sql = "SELECT m.menu_id, m.menu_padre_id, m.menu_nombre, m.menu_icono,
                       m.menu_ruta, m.menu_orden, m.menu_estado, m.menu_scope,
                       m.menu_es_desplegable,
                       (SELECT COUNT(*) FROM table_men_usuario_menu um
                        WHERE um.menu_id = m.menu_id) AS usuarios_count,
                       (SELECT COUNT(*) FROM table_men_departamento_menu dm
                        WHERE dm.menu_id = m.menu_id) AS deptos_count,
                       (SELECT COUNT(*) FROM table_men_rutas r
                        WHERE r.menu_id = m.menu_id) AS rutas_count
                FROM table_men_menu m
                ORDER BY m.menu_orden ASC, m.menu_id ASC";

        $flat = $this->fetchAll($sql);

        // Construir árbol
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

    /* ============================================================
       FIND
       ============================================================ */
    public function find(int $id): ?array{
        return $this->fetchOne(
            "SELECT * FROM table_men_menu WHERE menu_id = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /* ============================================================
       CREATE
       ============================================================ */
    public function create(array $d): int{
        $sql = "INSERT INTO table_men_menu
                    (menu_padre_id, menu_nombre, menu_icono, menu_ruta,
                     menu_orden, menu_estado, menu_scope, menu_es_desplegable)
                VALUES
                    (:padre, :nombre, :icono, :ruta,
                     :orden, :estado, :scope, :desplegable)";

        $this->query($sql, [
            'padre'       => !empty($d['menu_padre_id']) ? (int)$d['menu_padre_id'] : null,
            'nombre'      => $d['menu_nombre'],
            'icono'       => $d['menu_icono'] ?? '',
            'ruta'        => $d['menu_ruta'] ?? '',
            'orden'       => (int)($d['menu_orden'] ?? 0),
            'estado'      => (int)($d['menu_estado'] ?? 1),
            'scope'       => $d['menu_scope'] ?? 'general',
            'desplegable' => (int)($d['menu_es_desplegable'] ?? 0),
        ]);

        return (int)$this->lastInsertId();
    }

    /* ============================================================
       UPDATE
       ============================================================ */
    public function update(int $id, array $d): bool{
        $sql = "UPDATE table_men_menu SET
                    menu_padre_id       = :padre,
                    menu_nombre         = :nombre,
                    menu_icono          = :icono,
                    menu_ruta           = :ruta,
                    menu_orden          = :orden,
                    menu_estado         = :estado,
                    menu_scope          = :scope,
                    menu_es_desplegable = :desplegable
                WHERE menu_id = :id";

        return $this->execute($sql, [
            'padre'       => !empty($d['menu_padre_id']) ? (int)$d['menu_padre_id'] : null,
            'nombre'      => $d['menu_nombre'],
            'icono'       => $d['menu_icono'] ?? '',
            'ruta'        => $d['menu_ruta'] ?? '',
            'orden'       => (int)($d['menu_orden'] ?? 0),
            'estado'      => (int)($d['menu_estado'] ?? 1),
            'scope'       => $d['menu_scope'] ?? 'general',
            'desplegable' => (int)($d['menu_es_desplegable'] ?? 0),
            'id'          => $id,
        ]);
    }

    /* ============================================================
       DELETE
       ============================================================ */
    public function delete(int $id): bool{
        return $this->execute("DELETE FROM table_men_menu WHERE menu_id = :id", ['id' => $id]);
    }

    /* ============================================================
       ESTADO
       ============================================================ */
    public function cambiarEstado(int $id, int $estado): bool{
        return $this->execute(
            "UPDATE table_men_menu SET menu_estado = :e WHERE menu_id = :id",
            ['e' => $estado, 'id' => $id]
        );
    }

    /* ============================================================
       COMBOS
       ============================================================ */
    public function menusPadre(): array{
        return $this->fetchAll(
            "SELECT menu_id, menu_nombre FROM table_men_menu
             WHERE menu_padre_id IS NULL
             ORDER BY menu_orden ASC"
        );
    }

    /* ============================================================
       RUTAS
       ============================================================ */
    public function rutasDe(int $menuId): array{
        return $this->fetchAll(
            "SELECT ruta_id, menu_id, patron, activa
             FROM table_men_rutas
             WHERE menu_id = :id
             ORDER BY ruta_id ASC",
            ['id' => $menuId]
        );
    }

    public function crearRuta(int $menuId, string $patron): bool{
        return $this->execute(
            "INSERT INTO table_men_rutas (menu_id, patron, activa)
             VALUES (:m, :p, 1)",
            ['m' => $menuId, 'p' => $patron]
        );
    }

    public function eliminarRuta(int $rutaId): bool{
        return $this->execute(
            "DELETE FROM table_men_rutas WHERE ruta_id = :id",
            ['id' => $rutaId]
        );
    }

    /* ============================================================
       USUARIOS ASIGNADOS
       ============================================================ */
    public function usuariosAsignados(int $menuId): array{
        $sql = "SELECT um.id, u.usuario_id, u.usuario_nick, u.usuario_imagen,
                       p.personal_nombre, p.personal_apellido
                FROM table_men_usuario_menu um
                INNER JOIN table_usuarios u ON u.usuario_id = um.usuario_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE um.menu_id = :id
                ORDER BY u.usuario_nick ASC";
        return $this->fetchAll($sql, ['id' => $menuId]);
    }

    public function asignarUsuario(int $menuId, int $userId): bool{
        // Evitar duplicado
        $existe = $this->fetchOne(
            "SELECT 1 FROM table_men_usuario_menu WHERE menu_id = :m AND usuario_id = :u LIMIT 1",
            ['m' => $menuId, 'u' => $userId]
        );
        if ($existe) return false;

        return $this->execute(
            "INSERT INTO table_men_usuario_menu (menu_id, usuario_id) VALUES (:m, :u)",
            ['m' => $menuId, 'u' => $userId]
        );
    }

    public function quitarUsuario(int $menuId, int $userId): bool{
        return $this->execute(
            "DELETE FROM table_men_usuario_menu WHERE menu_id = :m AND usuario_id = :u",
            ['m' => $menuId, 'u' => $userId]
        );
    }

    /* ============================================================
       DEPARTAMENTOS ASIGNADOS
       ============================================================ */
    public function departamentosAsignados(int $menuId): array{
        $sql = "SELECT dm.id, d.departamento_id, d.departamento_nombre
                FROM table_men_departamento_menu dm
                INNER JOIN table_departamentos d ON d.departamento_id = dm.departamento_id
                WHERE dm.menu_id = :id
                ORDER BY d.departamento_nombre ASC";
        return $this->fetchAll($sql, ['id' => $menuId]);
    }

    public function asignarDepartamento(int $menuId, int $deptoId): bool{
        $existe = $this->fetchOne(
            "SELECT 1 FROM table_men_departamento_menu WHERE menu_id = :m AND departamento_id = :d LIMIT 1",
            ['m' => $menuId, 'd' => $deptoId]
        );
        if ($existe) return false;

        return $this->execute(
            "INSERT INTO table_men_departamento_menu (menu_id, departamento_id) VALUES (:m, :d)",
            ['m' => $menuId, 'd' => $deptoId]
        );
    }

    public function quitarDepartamento(int $menuId, int $deptoId): bool{
        return $this->execute(
            "DELETE FROM table_men_departamento_menu WHERE menu_id = :m AND departamento_id = :d",
            ['m' => $menuId, 'd' => $deptoId]
        );
    }

    /* ============================================================
       LISTA DE USUARIOS Y DEPARTAMENTOS (para combos)
       ============================================================ */
    public function todosUsuarios(): array{
        $sql = "SELECT u.usuario_id, u.usuario_nick,
                       p.personal_nombre, p.personal_apellido
                FROM table_usuarios u
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE u.usuario_status = 1
                ORDER BY u.usuario_nick ASC";
        return $this->fetchAll($sql);
    }

    public function todosDepartamentos(): array{
        return $this->fetchAll(
            "SELECT departamento_id, departamento_nombre
             FROM table_departamentos
             WHERE departamento_status = 1
             ORDER BY departamento_nombre ASC"
        );
    }
}
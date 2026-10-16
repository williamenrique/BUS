<?php
namespace App\Models;

use App\Core\Model;

class ModelPersonal extends Model
{
        /**
     * Listado paginado con búsqueda y filtros opcionales.
     * Busca por cédula, nombre, apellido, email o teléfono.
     */
    public function listarPaginado(string $q = '', int $limit = 10, int $offset = 0, array $filtros = []): array{
        $where  = "1=1";
        $params = [];

        if ($q !== ''){
            $where .= " AND (
                            p.personal_cedula   LIKE :q1
                         OR p.personal_nombre   LIKE :q2
                         OR p.personal_apellido LIKE :q3
                         OR p.personal_email    LIKE :q4
                         OR p.personal_tlf      LIKE :q5
                       )";
            $like = "%{$q}%";
            $params['q1'] = $like;
            $params['q2'] = $like;
            $params['q3'] = $like;
            $params['q4'] = $like;
            $params['q5'] = $like;
        }

        if (isset($filtros['status']) && $filtros['status'] !== ''){
            $where .= " AND p.personal_status = :status";
            $params['status'] = (int)$filtros['status'];
        }

        if (!empty($filtros['departamento'])){
            $where .= " AND p.personal_departamento = :depto";
            $params['depto'] = (int)$filtros['departamento'];
        }

        $total = (int)($this->fetchOne("SELECT COUNT(*) AS t FROM table_personal")['t'] ?? 0);

        $totalFiltrados = (int)($this->fetchOne(
            "SELECT COUNT(*) AS t FROM table_personal p WHERE {$where}",
            $params
        )['t'] ?? 0);

        $sql = "SELECT p.*, c.cargo AS cargo_nombre,
                       d.departamento_nombre
                FROM table_personal p
                LEFT JOIN table_per_cargo c ON c.id_cargo = p.personal_cargo
                LEFT JOIN table_departamentos d ON d.departamento_id = p.personal_departamento
                WHERE {$where}
                ORDER BY p.personal_apellido ASC, p.personal_nombre ASC
                LIMIT {$limit} OFFSET {$offset}";

        $data = $this->fetchAll($sql, $params);

        return [
            'data'           => $data,
            'total'          => $total,
            'totalFiltrados' => $totalFiltrados,
        ];
    }
    public function all(): array
    {
        $sql = "SELECT p.*, c.cargo AS cargo_nombre,
                       d.departamento_nombre
                FROM table_personal p
                LEFT JOIN table_per_cargo c ON c.id_cargo = p.personal_cargo
                LEFT JOIN table_departamentos d ON d.departamento_id = p.personal_departamento
                ORDER BY p.personal_apellido ASC, p.personal_nombre ASC";
        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT p.*, c.cargo AS cargo_nombre,
                       d.departamento_nombre
                FROM table_personal p
                LEFT JOIN table_per_cargo c ON c.id_cargo = p.personal_cargo
                LEFT JOIN table_departamentos d ON d.departamento_id = p.personal_departamento
                WHERE p.id_personal = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

    public function create(array $d): int
    {
        $sql = "INSERT INTO table_personal
                    (personal_cedula, personal_nombre, personal_apellido,
                     personal_cargo, personal_tlf, personal_direccion,
                     personal_email, personal_tag, personal_departamento,
                     personal_status)
                VALUES
                    (:cedula, :nombre, :apellido,
                     :cargo, :tlf, :direccion,
                     :email, :tag, :departamento,
                     :status)";
        $this->query($sql, [
            'cedula'       => $d['personal_cedula'],
            'nombre'       => $d['personal_nombre'],
            'apellido'     => $d['personal_apellido'],
            'cargo'        => (int)$d['personal_cargo'],
            'tlf'          => $d['personal_tlf'] ?? null,
            'direccion'    => $d['personal_direccion'] ?? '',
            'email'        => $d['personal_email'] ?? '',
            'tag'          => (int)($d['personal_tag'] ?? 1),
            'departamento' => !empty($d['personal_departamento']) ? (int)$d['personal_departamento'] : null,
            'status'       => (int)($d['personal_status'] ?? 1),
        ]);
        return (int)$this->lastInsertId();
    }

    public function update(int $id, array $d): bool
    {
        $sql = "UPDATE table_personal SET
                    personal_nombre       = :nombre,
                    personal_apellido     = :apellido,
                    personal_cargo        = :cargo,
                    personal_tlf          = :tlf,
                    personal_direccion    = :direccion,
                    personal_email        = :email,
                    personal_tag          = :tag,
                    personal_departamento = :departamento,
                    personal_status       = :status
                WHERE id_personal = :id";

        return $this->execute($sql, [
            'nombre'       => $d['personal_nombre'],
            'apellido'     => $d['personal_apellido'],
            'cargo'        => (int)$d['personal_cargo'],
            'tlf'          => $d['personal_tlf']          ?? null,
            'direccion'    => $d['personal_direccion']    ?? '',
            'email'        => $d['personal_email']        ?? '',
            'tag'          => (int)($d['personal_tag']    ?? 0),
            'departamento' => !empty($d['personal_departamento']) ? (int)$d['personal_departamento'] : null,
            'status'       => (int)($d['personal_status'] ?? 1),
            'id'           => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        return $this->execute("DELETE FROM table_personal WHERE id_personal = :id", ['id' => $id]);
    }

    public function cambiarStatus(int $id, int $status): bool
    {
        return $this->execute(
            "UPDATE table_personal SET personal_status = :s WHERE id_personal = :id",
            ['s' => $status, 'id' => $id]
        );
    }

    public function existeCedula(string $cedula, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM table_personal WHERE personal_cedula = :c";
        $params = ['c' => $cedula];
        if ($excludeId) { $sql .= " AND id_personal <> :id"; $params['id'] = $excludeId; }
        $sql .= " LIMIT 1";
        return (bool)$this->fetchOne($sql, $params);
    }

    public function existeEmail(string $email, ?int $excludeId = null): bool
    {
        if ($email === '') return false;
        $sql = "SELECT 1 FROM table_personal WHERE personal_email = :e";
        $params = ['e' => $email];
        if ($excludeId) { $sql .= " AND id_personal <> :id"; $params['id'] = $excludeId; }
        $sql .= " LIMIT 1";
        return (bool)$this->fetchOne($sql, $params);
    }

    public function cargos(): array
    {
        return $this->fetchAll(
            "SELECT id_cargo, cargo FROM table_per_cargo
             WHERE status_cargo = 1 OR status_cargo IS NULL
             ORDER BY cargo ASC"
        );
    }

    public function departamentos(): array
    {
        return $this->fetchAll(
            "SELECT departamento_id, departamento_nombre FROM table_departamentos
             WHERE departamento_status = 1
             ORDER BY departamento_nombre ASC"
        );
    }
}
<?php
namespace App\Models;

use App\Core\Model;

class ModelRecuperar extends Model{

        /**
     * Cantidad de solicitudes pendientes.
     * Como ya no guardamos status=1 (se eliminan al resolver), contamos todo.
     */
    public function contarPendientes(): int{
        $row = $this->fetchOne("SELECT COUNT(*) AS t FROM table_recovery_requests");
        return (int)($row['t'] ?? 0);
    }

    /**
     * Lista todas las solicitudes con datos del usuario vinculado.
     */
    public function listarPendientes(): array{
        $sql = "SELECT r.id, r.user_id, r.identifier_provided, r.request_date,
                       u.usuario_nick, u.usuario_imagen, u.usuario_departamento_id,
                       p.personal_nombre, p.personal_apellido, p.personal_cedula,
                       p.personal_email, p.personal_tlf,
                       d.departamento_nombre
                FROM table_recovery_requests r
                INNER JOIN table_usuarios u ON u.usuario_id = r.user_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                LEFT JOIN table_departamentos d ON d.departamento_id = u.usuario_departamento_id
                ORDER BY r.request_date DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Verifica si el usuario ya tiene una solicitud pendiente.
     */
    public function tienePendiente(int $userId): bool{
        $sql = "SELECT 1 FROM table_recovery_requests
                WHERE user_id = :uid LIMIT 1";
        return (bool)$this->fetchOne($sql, ['uid' => $userId]);
    }

    /**
     * Crea una nueva solicitud.
     * Devuelve true si insertó, false si ya existía una del mismo usuario.
     */
    public function crear(int $userId, string $identificador): bool{
        // Si ya tiene una pendiente, no duplicar
        $sql = "SELECT 1 FROM table_recovery_requests
                WHERE user_id = :uid LIMIT 1";
        if ($this->fetchOne($sql, ['uid' => $userId])){
            return false;
        }

        $sql = "INSERT INTO table_recovery_requests
                    (user_id, identifier_provided, request_date)
                VALUES
                    (:uid, :ident, NOW())";

        $stmt = $this->query($sql, [
            'uid'   => $userId,
            'ident' => $identificador
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Obtiene una solicitud por ID.
     */
    public function find(int $id): ?array{
        $sql = "SELECT r.*, u.usuario_nick, u.usuario_imagen,
                       p.personal_nombre, p.personal_apellido,
                       p.personal_cedula, p.personal_email, p.personal_tlf
                FROM table_recovery_requests r
                INNER JOIN table_usuarios u ON u.usuario_id = r.user_id
                LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                WHERE r.id = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }

        /**
     * Elimina la solicitud resuelta (no acumulamos basura).
     */
    public function marcarResuelto(int $id): bool{
        return $this->execute(
            "DELETE FROM table_recovery_requests WHERE id = :id",
            ['id' => $id]
        );
    }

}
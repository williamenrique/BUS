<?php
namespace App\Models;

use App\Core\Model;

class ModelPersonal extends Model
{
    public function find(int $id): ?array
    {
        $sql = "SELECT p.*, c.cargo
                FROM table_personal p
                LEFT JOIN table_per_cargo c ON c.id_cargo = p.personal_cargo
                WHERE p.id_personal = :id
                LIMIT 1";
        return $this->fetchOne($sql, ['id' => $id]);
    }
}
<?php
namespace App\Middleware;

class RoleGuard
{
    public static function allow(array $roles): void
    {
        $user = $_SESSION['user'] ?? null;
        $rol  = $user['rol_nombre'] ?? '';
        if (!in_array($rol, $roles, true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }

    public static function allowDepartamento(array $departamentos): void
    {
        $user = $_SESSION['user'] ?? null;
        $dep  = (int)($user['departamento_id'] ?? 0);
        if (!in_array($dep, $departamentos, true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }
}
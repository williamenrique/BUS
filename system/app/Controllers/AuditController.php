<?php
/**
 * Controlador AuditController
 * Ubicación: system/app/Controllers/AuditController.php
 * Maneja las peticiones de auditoría enviadas desde el frontend.
 */
class Audit extends Controllers {

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_write_close(); // Liberamos el bloqueo de sesión para que otras peticiones (como notificaciones) no se detengan
        parent::__construct();
    }

    /**
     * Endpoint para recibir acciones de auditoría desde el frontend vía AJAX.
     * Se accede mediante: base_url/audit/log_action
     */
    public function log_action() {
        // Obtener el input JSON enviado por fetch()
        $input = json_decode(file_get_contents('php://input'), true);

        $action_type = $input['action_type'] ?? 'UNKNOWN';
        $module = $input['module'] ?? 'Frontend';
        $description = $input['description'] ?? 'Acción registrada desde JS.';
        $reference_id = $input['reference_id'] ?? null;

        // Usar la función global definida en Helpers.php
        $logged = log_frontend_action($action_type, $module, $description, $reference_id);

        header('Content-Type: application/json');
        echo json_encode(['status' => $logged, 'message' => $logged ? 'Bitácora registrada.' : 'Error al registrar bitácora.']);
        exit();
    }
}
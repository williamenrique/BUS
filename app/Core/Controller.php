<?php
namespace App\Core;

abstract class Controller{

    /* ============================================================
       VISTAS
       ============================================================ */
    protected function view(string $view, array $data = [], bool $withLayout = true): void{
        extract($data, EXTR_SKIP);
        $viewFile = VIEWS_PATH . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)){
            throw new AppException("Vista no encontrada: {$view}");
        }

        if ($withLayout){
            require VIEWS_PATH . 'inc/header.php';
            require $viewFile;
            require VIEWS_PATH . 'inc/footer.php';
        } else {
            require $viewFile;
        }
    }

    /* ============================================================
       RESPUESTAS JSON
       ============================================================ */
    protected function json(array $data, int $code = 200): void{
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Respuesta JSON de error normalizada.
     */
    protected function jsonError(string $message, int $code = 500, array $extra = []): void{
        $this->json(array_merge([
            'ok'      => false,
            'message' => $message,
        ], $extra), $code);
    }

    /**
     * Ejecuta un callable y captura DatabaseException / Throwable.
     * Devuelve JSON de error automáticamente.
     *
     * Uso:
     *   $this->safe(function(){
     *       $m = new ModelFlota();
     *       $data = $m->listarPaginado(...);
     *       $this->json(['ok' => true, 'data' => $data]);
     *   });
     */
    protected function safe(callable $fn): void{
        try{
            $fn();
        } catch (DatabaseException $e){
            $this->jsonError($e->getMessage(), 500, ['type' => 'database']);
        } catch (AppException $e){
            $this->jsonError($e->getMessage(), 500, ['type' => 'app']);
        } catch (\Throwable $e){
            Database::logError('CONTROLLER', $e);
            $msg = ENVIRONMENT === 'development'
                ? $e->getMessage()
                : 'Error inesperado en el servidor.';
            $this->jsonError($msg, 500, ['type' => 'fatal']);
        }
    }

    /* ============================================================
       NAVEGACIÓN
       ============================================================ */
    protected function redirect(string $path): void{
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    /* ============================================================
       INPUT
       ============================================================ */
    protected function isPost(): bool{
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function post(string $key, $default = null){
        return $_POST[$key] ?? $default;
    }

    protected function input(): array{
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }
}
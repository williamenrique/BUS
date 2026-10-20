<?php
namespace App\Core;

class App{

    private array $routes = [];

    public function __construct(){
        $this->routes = require APP_PATH . 'Config/routes.php';
    }

    public function run(): void{
        $uri    = $this->getUri();
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route){
            $pattern = $this->buildPattern($route['path']);
            if (preg_match($pattern, $uri, $matches) && in_array($method, (array)$route['method'])){
                array_shift($matches);
                [$controller, $action] = explode('@', $route['handler']);
                $controllerClass = "App\\Controllers\\{$controller}";

                if (!class_exists($controllerClass)){
                    $this->abort404();
                    return;
                }

                $instance = new $controllerClass();
                if (!method_exists($instance, $action)){
                    $this->abort404();
                    return;
                }

                try{
                    call_user_func_array([$instance, $action], $matches);
                } catch (DatabaseException $e){
                    Database::logError('APP', $e);
                    $this->respondError($e->getMessage(), 500);
                } catch (AppException $e){
                    Database::logError('APP', $e);
                    $this->respondError($e->getMessage(), 500);
                } catch (\Throwable $e){
                    Database::logError('APP', $e);
                    $msg = ENVIRONMENT === 'development'
                        ? $e->getMessage()
                        : 'Error inesperado en el servidor.';
                    $this->respondError($msg, 500);
                }
                return;
            }
        }
        $this->abort404();
    }

    /**
     * Responde de forma unificada (JSON si es AJAX, HTML si es navegación).
     */
    private function respondError(string $message, int $code = 500): void{
        http_response_code($code);

        if ($this->esAjax()){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'      => false,
                'message' => $message,
                'type'    => 'database',
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $msg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error del sistema</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f1f5f9; margin: 0; padding: 3rem 1rem; }
        .box { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 12px;
               box-shadow: 0 10px 25px rgba(15,23,42,.1); padding: 2rem; text-align: center; }
        h1 { color: #dc2626; font-size: 1.4rem; margin: 0 0 .5rem; }
        p { color: #475569; margin: 0 0 1.5rem; line-height: 1.5; }
        a { display: inline-block; padding: .65rem 1.25rem; background: #2563eb; color: #fff;
            text-decoration: none; border-radius: 8px; font-weight: 500; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="box">
        <h1>⚠ Ocurrió un error</h1>
        <p>{$msg}</p>
        <a href="javascript:history.back()">Volver</a>
    </div>
</body>
</html>
HTML;
    }

    private function esAjax(): bool{
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    private function getUri(): string{
        $uri = $_GET['url'] ?? '';
        if ($uri === ''){
            $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $base = parse_url(URLROOT, PHP_URL_PATH);
            if ($base && $base !== '/' && strpos($uri, $base) === 0){
                $uri = substr($uri, strlen($base));
            }
        }
        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    /**
     * Convierte '/flota/i/{inst}/historial/{id}' en una regex:
     *   - {id} y {inst} → solo dígitos
     *   - cualquier otro {param} → cualquier cosa sin '/'
     */
    private function buildPattern(string $path): string{
        // Escapamos todo primero
        $path = preg_quote($path, '#');

        // Reemplazamos {id} y {inst} por solo dígitos
        $path = str_replace(['\{id\}', '\{inst\}'], '(\d+)', $path);

        // Cualquier otro {param} dinámico → cualquier cosa sin '/'
        $path = preg_replace('/\\\\\{[a-zA-Z_][a-zA-Z0-9_]*\\\\\}/', '([^/]+)', $path);

        return '#^' . $path . '$#';
    }

    private function abort404(): void{
        http_response_code(404);
        require VIEWS_PATH . 'errores/404.php';
    }
}
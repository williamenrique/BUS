<?php
namespace App\Core;

class App
{
    private array $routes = [];

    public function __construct()
    {
        $this->routes = require APP_PATH . 'Config/routes.php';
    }

    public function run(): void    {
        $uri    = $this->getUri();
        $method = $_SERVER['REQUEST_METHOD'];

        foreach ($this->routes as $route) {
            $pattern = $this->buildPattern($route['path']);
            if (preg_match($pattern, $uri, $matches) && in_array($method, (array)$route['method'])) {
                array_shift($matches);
                [$controller, $action] = explode('@', $route['handler']);
                $controllerClass = "App\\Controllers\\{$controller}";

                if (!class_exists($controllerClass)) {
                    $this->abort404();
                    return;
                }

                $instance = new $controllerClass();
                if (!method_exists($instance, $action)) {
                    $this->abort404();
                    return;
                }

                try {
                    call_user_func_array([$instance, $action], $matches);
                } catch (AppException $e) {
                    http_response_code(500);
                    echo '<pre>Error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
                }
                return;
            }
        }
        $this->abort404();
    }

    private function getUri(): string
    {
        // Patrón multiservicio: .htaccess manda ?url=ruta/pedida
        $uri = $_GET['url'] ?? '';

        // Fallback (acceso directo sin rewrite)
        if ($uri === '') {
            $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $base = parse_url(URLROOT, PHP_URL_PATH);
            if ($base && $base !== '/' && strpos($uri, $base) === 0) {
                $uri = substr($uri, strlen($base));
            }
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '/' ? '/' : rtrim($uri, '/');
    }

    private function buildPattern(string $path): string
    {
        $path = preg_quote($path, '#');
        $path = str_replace(['\{id\}', '\{slug\}'], '([^/]+)', $path);
        return '#^' . $path . '$#';
    }

    private function abort404(): void
    {
        http_response_code(404);
        require VIEWS_PATH . 'errores/404.php';
    }
}
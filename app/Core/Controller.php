<?php
namespace App\Core;

abstract class Controller
{
    protected function view(string $view, array $data = [], bool $withLayout = true): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = VIEWS_PATH . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new AppException("Vista no encontrada: {$view}");
        }

        if ($withLayout) {
            require VIEWS_PATH . 'inc/header.php';
            require $viewFile;
            require VIEWS_PATH . 'inc/footer.php';
        } else {
            require $viewFile;
        }
    }

    protected function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    protected function input(): array
    {
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);
        return is_array($json) ? $json : $_POST;
    }
}
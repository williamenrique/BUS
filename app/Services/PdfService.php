<?php
namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Core\Database;

class PdfService
{
    private $dompdf;

    public function __construct()
    {
        $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            throw new \RuntimeException(
                'No se encontró vendor/autoload.php. Ejecuta "composer install" en la raíz del proyecto.'
            );
        }
        require_once $autoload;

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('chroot', dirname(__DIR__, 2));
        $options->set('defaultFont', 'DejaVu Sans');

        $tmpDir = dirname(__DIR__, 2) . '/assets/uploads/tmp_pdf/';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }
        if (is_dir($tmpDir) && is_writable($tmpDir)) {
            $options->set('tempDir', $tmpDir);
        }

        $this->dompdf = new Dompdf($options);
    }

    private function prepararDocumento(string $view, array $data = [], array $meta = []): void
    {
        $viewPath = dirname(__DIR__) . '/Views/Pdf/templates/' . $view . '.php';
        if (!file_exists($viewPath)) {
            throw new \RuntimeException("Template PDF no encontrado: {$view}");
        }

        $institucion = $this->obtenerInstitucion($meta['institucion_id'] ?? null);

        $meta = array_merge([
            'institucion'        => $institucion,
            'titulo_documento'   => 'DOCUMENTO',
            'subtitulo'          => '',
            'doc_numero'         => '',
            'doc_color'          => '#1e40af',
            'extra_header'       => '',
            'mostrar_header'     => true,
            'mostrar_footer'     => true,
            'paper'              => 'A4',
            'orientation'        => 'portrait',
        ], $meta);

        // Extraer variables para el template
        extract($data);
        extract(['meta' => $meta]);

        // Capturar warnings de PHP y convertirlos en excepciones
        $errores = [];
        set_error_handler(function ($severity, $message, $file, $line) use (&$errores) {
            $errores[] = "{$message} en {$file}:{$line}";
            return true;
        });

        ob_start();

        try {
            require dirname(__DIR__) . '/Views/Pdf/inc/pdf.css.php';

            if (!empty($meta['mostrar_header'])) {
                require dirname(__DIR__) . '/Views/Pdf/inc/header.php';
            }

            require $viewPath;

            if (!empty($meta['mostrar_footer'])) {
                require dirname(__DIR__) . '/Views/Pdf/inc/footer.php';
            }

            $html = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            restore_error_handler();
            throw $e;
        }

        restore_error_handler();

        if (!empty($errores)) {
            throw new \RuntimeException(
                "Errores en template '{$view}':\n" . implode("\n", $errores)
            );
        }

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper($meta['paper'], $meta['orientation']);
        $this->dompdf->render();
    }

    public function generarDocumento(
        string $view,
        array $data = [],
        string $filename = 'documento.pdf',
        bool $attachment = false,
        array $meta = []
    ): void {
        $this->prepararDocumento($view, $data, $meta);
        $this->dompdf->stream($filename, ['Attachment' => $attachment]);
        exit;
    }

    public function generarBinario(string $view, array $data = [], array $meta = []): string
    {
        $this->prepararDocumento($view, $data, $meta);
        return $this->dompdf->output();
    }

    private function obtenerInstitucion(?int $id = null): array
    {
        $fallback = [
            'id_institucion' => 1,
            'nombre'         => 'INSTITUCIÓN',
            'rif'            => '',
        ];

        try {
            $db = Database::getConnection();

            if ($id === null) {
                if (function_exists('auth_user')) {
                    $u = auth_user();
                    $id = (int)($u['institucion_id'] ?? 0);
                }
                if (!$id) {
                    $id = (int)($_SESSION['user']['institucion_id'] ?? 0);
                }
            }

            if ($id > 0) {
                $stmt = $db->prepare("SELECT id_institucion, nombre, rif FROM table_instituciones WHERE id_institucion = :id LIMIT 1");
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row) return $row;
            }

            $stmt = $db->query("SELECT id_institucion, nombre, rif FROM table_instituciones WHERE status = 1 ORDER BY id_institucion ASC LIMIT 1");
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row) return $row;
        } catch (\Throwable $e) {
            error_log('PdfService::obtenerInstitucion → ' . $e->getMessage());
        }

        return $fallback;
    }
}
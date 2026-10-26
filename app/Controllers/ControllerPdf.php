<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Services\PdfService;
use App\Middleware\AuthGuard;

class ControllerPdf extends Controller
{
    private const TOKEN_TTL = 1800;
    private const TOKEN_DIR = '/storage/pdf_tokens/';

    public function generar(): void
    {
        AuthGuard::check();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!is_array($payload)) {
            $this->json(['ok' => false, 'message' => 'Payload inválido.'], 422);
            return;
        }

        $view     = (string)($payload['view'] ?? '');
        $data     = $payload['data'] ?? [];
        $meta     = $payload['meta'] ?? [];
        $filename = (string)($payload['filename'] ?? 'documento.pdf');

        if ($view === '') {
            $this->json(['ok' => false, 'message' => 'Falta "view".'], 422);
            return;
        }

        $permitidos = [
            'despachos_orden', 'despachos_orden_lote',
            'compras_por_unidad', 'compras_orden_costeada', 'compras_costeadas_filtrado',
            'productos_inventario', 'productos_historial',
            'bienes_reporte',
            'flota_hoja_vida', 'flota_operatividad', 'flota_aceite',
            'estacion_reporte_dia', 'estacion_litros',
            'publico_movimientos', 'publico_ventas_estacion', 'publico_aceite',
        ];
        if (!in_array($view, $permitidos, true)) {
            $this->json(['ok' => false, 'message' => 'Template no permitido.'], 403);
            return;
        }

        $filename = preg_replace('/[^A-Za-z0-9._\-]/', '_', $filename);
        if (!preg_match('/\.pdf$/i', $filename)) {
            $filename .= '.pdf';
        }

        try {
            $token = bin2hex(random_bytes(16));

            $dir = dirname(__DIR__, 2) . self::TOKEN_DIR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $tokenData = [
                'view'     => $view,
                'data'     => $data,
                'meta'     => $meta,
                'filename' => $filename,
                'expira'   => time() + self::TOKEN_TTL,
                'user_id'  => (int)($_SESSION['user']['id'] ?? 0),
            ];

            $tokenFile = $dir . $token . '.json';
            file_put_contents($tokenFile, json_encode($tokenData, JSON_UNESCAPED_UNICODE));

            $this->limpiarTokensExpirados($dir);

            $this->json([
                'ok'       => true,
                'url'      => base_url('pdf/ver?t=' . $token),
                'filename' => $filename,
            ]);
        } catch (\Throwable $e) {
            error_log('ControllerPdf::generar → ' . $e->getMessage());
            $this->json(['ok' => false, 'message' => 'Error al preparar el PDF: ' . $e->getMessage()], 500);
        }
    }

    public function ver(): void {
        AuthGuard::check();

        $token = (string)($_GET['t'] ?? '');

        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Token inválido.';
            exit;
        }

        $dir  = dirname(__DIR__, 2) . self::TOKEN_DIR;
        $file = $dir . $token . '.json';

        if (!file_exists($file)) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'El PDF expiró o no existe. Genera uno nuevo.';
            exit;
        }

        $tokenData = json_decode(file_get_contents($file), true);
        if (!is_array($tokenData)) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Error al leer el token.';
            exit;
        }

        if (($tokenData['expira'] ?? 0) < time()) {
            @unlink($file);
            http_response_code(410);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'El PDF expiró. Genera uno nuevo.';
            exit;
        }

        $uid = (int)($_SESSION['user']['id'] ?? 0);
        if ((int)($tokenData['user_id'] ?? 0) !== $uid) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'No autorizado.';
            exit;
        }

        try {
            @unlink($file);

            // Limpiar cualquier output previo
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            if (headers_sent($f, $l)) {
                error_log("PDF: headers ya enviados en {$f}:{$l}");
            }

            // Forzar headers ANTES de generar
            $filename = $tokenData['filename'] ?? 'documento.pdf';
            if (!preg_match('/\.pdf$/i', $filename)) {
                $filename .= '.pdf';
            }

            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $filename . '"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');

            $pdf = new PdfService();
            $pdf->generarDocumento(
                $tokenData['view'],
                is_array($tokenData['data'] ?? null) ? $tokenData['data'] : [],
                $filename,
                false,
                is_array($tokenData['meta'] ?? null) ? $tokenData['meta'] : []
            );
        } catch (\Throwable $e) {
            error_log('ControllerPdf::ver → ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Error al generar el PDF: ' . $e->getMessage();
            exit;
        }
    }

    private function limpiarTokensExpirados(string $dir): void
    {
        if (!is_dir($dir)) return;
        $files = glob($dir . '*.json');
        if (!$files) return;

        $ahora = time();
        foreach ($files as $f) {
            $data = @json_decode(@file_get_contents($f), true);
            if (!is_array($data) || ($data['expira'] ?? 0) < $ahora) {
                @unlink($f);
            }
        }
    }
}
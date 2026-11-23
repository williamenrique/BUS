<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Logger;
use App\Middleware\AuthGuard;
use App\Middleware\MenuGuard;
use App\Models\ModelBackup;
use App\Models\ModelUsuario;
use App\Services\BackupService;

class ControllerBackup extends Controller
{
    private function esSistema(): bool
    {
        $u = auth_user();
        return (int)($u['departamento_id'] ?? 0) === (int)DEPARTAMENTO_SISTEMA;
    }

    private function guard(): void
    {
        AuthGuard::check();
        MenuGuard::check();
        if (!$this->esSistema()) {
            \App\Middleware\MenuGuard::denegar();
        }
    }

    /* ============================================================
       VISTA
       ============================================================ */
    public function index(): void
    {
        $this->guard();
        $user  = auth_user();
        $mUser = new ModelUsuario();
        $mBk   = new ModelBackup();
        $mBk->ensureTable();
        $svc   = new BackupService();

        $this->view('backup.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Backup y Restauración',
            'scripts'   => ['backup.js'],
            'stats'     => [
                'archivos' => $svc->contarArchivos(),
                'tamano'   => $svc->tamanoTotal(),
                'bd'       => $mBk->stats(),
            ],
        ]);
    }

    /* ============================================================
       LISTAR
       ============================================================ */
    public function listar(): void
    {
        $this->guard();
        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $filtros = [
            'tipo'   => $_GET['tipo']   ?? '',
            'accion' => $_GET['accion'] ?? '',
            'estado' => $_GET['estado'] ?? '',
        ];

        $this->safe(function () use ($q, $limit, $offset, $filtros) {
            $mBk = new ModelBackup();
            $mBk->ensureTable();
            $r = $mBk->listarPaginado($q, $limit, $offset, $filtros);
            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
                'page'           => (int)floor($offset / $limit) + 1,
                'limit'          => $limit,
            ]);
        });
    }

    /* ============================================================
       STATS
       ============================================================ */
    public function stats(): void
    {
        $this->guard();
        $this->safe(function () {
            $svc = new BackupService();
            $mBk = new ModelBackup();
            $mBk->ensureTable();
            $this->json([
                'ok'   => true,
                'data' => [
                    'archivos'     => $svc->contarArchivos(),
                    'tamano_bytes' => $svc->tamanoTotal(),
                    'tamano_fmt'   => $this->fmtBytes($svc->tamanoTotal()),
                    'bd'           => $mBk->stats(),
                ],
            ]);
        });
    }

    /* ============================================================
       CREAR
       ============================================================ */
    public function crear(): void
    {
        $this->guard();
        $u = auth_user();

        $this->safe(function () use ($u) {
            $svc = new BackupService();
            $mBk = new ModelBackup();
            $mBk->ensureTable();

            $r = $svc->crear('manual', 'backup');

            $mBk->registrar([
                'archivo'        => $r['archivo'] ?? '',
                'tamanio_bytes'  => $r['tamanio_bytes'] ?? 0,
                'checksum_sha256'=> $r['checksum'] ?? null,
                'tipo'           => 'manual',
                'accion'         => 'backup',
                'estado'         => $r['ok'] ? 'ok' : 'error',
                'mensaje'        => $r['mensaje'] ?? null,
                'duracion_seg'   => $r['duracion_seg'] ?? null,
                'usuario_id'     => (int)$u['id'],
                'usuario_nick'   => $u['nick'] ?? null,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $this->json([
                'ok'            => $r['ok'],
                'message'       => $r['mensaje'],
                'archivo'       => $r['archivo'] ?? null,
                'tamanio_bytes' => $r['tamanio_bytes'] ?? 0,
                'duracion_seg'  => $r['duracion_seg'] ?? null,
            ], $r['ok'] ? 200 : 500);
        });
    }

    /* ============================================================
       DESCARGAR
       ============================================================ */
    public function descargar(string $archivo): void
    {
        $this->guard();
        $archivo = urldecode($archivo);
        $svc = new BackupService();

        if (!$svc->esArchivoValido($archivo)) {
            http_response_code(400);
            echo 'Nombre inválido.';
            exit;
        }
        $ruta = $svc->rutaDescarga($archivo);
        if (!$ruta) {
            http_response_code(404);
            echo 'No encontrado.';
            exit;
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta));
        header('Cache-Control: no-store');
        readfile($ruta);
        exit;
    }

    /* ============================================================
       ELIMINAR — borra registro + archivo físico
       POST /backup/eliminar/{id}
       ============================================================ */
    public function eliminar(string $id): void
    {
        $this->guard();
        $id = (int)$id;

        if ($id <= 0) {
            $this->json(['ok' => false, 'message' => 'ID inválido.'], 422);
            return;
        }

        $this->safe(function () use ($id) {
            $mBk = new ModelBackup();
            $mBk->ensureTable();

            $reg = $mBk->find($id);
            if (!$reg) {
                $this->json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
                return;
            }

            // 1) Borrar archivo físico si es un backup (no restore) y existe
            $archivo     = (string)$reg['archivo'];
            $archivoFisicoBorrado = false;

            if ($reg['accion'] === 'backup' && $archivo !== '') {
                $svc = new BackupService();

                // Verificar que ningún otro registro apunte al mismo archivo
                // (usamos método público del modelo, NO fetchOne directo)
                $usadoPorOtros = $mBk->contarPorArchivo($archivo, $id) > 0;

                if (!$usadoPorOtros && $svc->esArchivoValido($archivo)) {
                    $archivoFisicoBorrado = $svc->eliminar($archivo);
                }
            }

            // 2) Borrar registro
            $ok = $mBk->eliminarPorId($id);

            if (!$ok) {
                $this->json(['ok' => false, 'message' => 'No se pudo eliminar el registro.'], 500);
                return;
            }

            $msg = 'Registro eliminado.';
            if ($archivoFisicoBorrado) $msg .= ' Archivo .sql borrado del disco.';
            elseif ($reg['accion'] === 'backup' && $archivo !== '') $msg .= ' (El archivo no existía en disco.)';

            $this->json([
                'ok'              => true,
                'message'         => $msg,
                'id'              => $id,
                'archivo_borrado' => $archivoFisicoBorrado,
            ]);
        });
    }

    /* ============================================================
       RESTAURAR
       ============================================================ */
    public function restaurar(): void
    {
        $this->guard();
        $u = auth_user();
        $d = $this->input();
        $archivo = (string)($d['archivo'] ?? '');
        if ($archivo === '') {
            $this->json(['ok' => false, 'message' => 'Archivo no especificado.'], 422);
            return;
        }

        $this->safe(function () use ($u, $archivo) {
            $svc = new BackupService();
            $mBk = new ModelBackup();
            $mBk->ensureTable();

            // 1) Backup de seguridad
            $seg = $svc->crear('seguridad', 'seguridad_pre_restore');
            $mBk->registrar([
                'archivo'        => $seg['archivo'] ?? '',
                'tamanio_bytes'  => $seg['tamanio_bytes'] ?? 0,
                'checksum_sha256'=> $seg['checksum'] ?? null,
                'tipo'           => 'seguridad',
                'accion'         => 'backup',
                'estado'         => $seg['ok'] ? 'ok' : 'error',
                'mensaje'        => 'Backup de seguridad previo a restauración: ' . ($seg['mensaje'] ?? ''),
                'duracion_seg'   => $seg['duracion_seg'] ?? null,
                'usuario_id'     => (int)$u['id'],
                'usuario_nick'   => $u['nick'] ?? null,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            if (empty($seg['ok'])) {
                $this->json([
                    'ok' => false,
                    'message' => 'No se pudo crear el backup de seguridad, restauración cancelada. ' . ($seg['mensaje'] ?? ''),
                ], 500);
                return;
            }

            // 2) Restaurar
            $r = $svc->restaurar($archivo);
            $mBk->registrar([
                'archivo'        => $archivo,
                'tamanio_bytes'  => (int)@filesize(BACKUP_DIR . $archivo),
                'checksum_sha256'=> @hash_file('sha256', BACKUP_DIR . $archivo) ?: null,
                'tipo'           => 'manual',
                'accion'         => 'restore',
                'estado'         => $r['ok'] ? 'ok' : 'error',
                'mensaje'        => $r['mensaje'] ?? null,
                'duracion_seg'   => $r['duracion_seg'] ?? null,
                'usuario_id'     => (int)$u['id'],
                'usuario_nick'   => $u['nick'] ?? null,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $this->json([
                'ok'                => $r['ok'],
                'message'           => $r['mensaje'],
                'backup_seguridad'  => $seg['archivo'] ?? null,
                'duracion_seg'      => $r['duracion_seg'] ?? null,
            ], $r['ok'] ? 200 : 500);
        });
    }

    /* ============================================================
       SUBIR
       ============================================================ */
    public function subir(): void
    {
        $this->guard();
        $u = auth_user();

        if (empty($_FILES['archivo']['name'])) {
            $this->json(['ok' => false, 'message' => 'No se recibió ningún archivo.'], 422);
            return;
        }

        $this->safe(function () use ($u) {
            $f = $_FILES['archivo'];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $this->json(['ok' => false, 'message' => 'Error al subir el archivo (código ' . $f['error'] . ').'], 422);
                return;
            }
            if ($f['size'] > 200 * 1024 * 1024) {
                $this->json(['ok' => false, 'message' => 'El archivo supera los 200 MB.'], 422);
                return;
            }
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if ($ext !== 'sql') {
                $this->json(['ok' => false, 'message' => 'Solo se permiten archivos .sql.'], 422);
                return;
            }

            $svc = new BackupService();
            $nombre = 'subido_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.sql';
            $dest = BACKUP_DIR . $nombre;
            if (!move_uploaded_file($f['tmp_name'], $dest)) {
                $this->json(['ok' => false, 'message' => 'No se pudo guardar el archivo en el servidor.'], 500);
                return;
            }

            $mBk = new ModelBackup();
            $mBk->ensureTable();
            $mBk->registrar([
                'archivo'        => $nombre,
                'tamanio_bytes'  => (int)filesize($dest),
                'checksum_sha256'=> hash_file('sha256', $dest) ?: null,
                'tipo'           => 'subido',
                'accion'         => 'backup',
                'estado'         => 'ok',
                'mensaje'        => 'Archivo subido manualmente por el usuario.',
                'duracion_seg'   => null,
                'usuario_id'     => (int)$u['id'],
                'usuario_nick'   => $u['nick'] ?? null,
                'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);

            $this->json(['ok' => true, 'message' => 'Archivo subido como ' . $nombre, 'archivo' => $nombre]);
        });
    }

    /* ============================================================
       HELPER
       ============================================================ */
    private function fmtBytes(int $b): string
    {
        if ($b < 1024) return $b . ' B';
        if ($b < 1048576) return round($b / 1024, 1) . ' KB';
        if ($b < 1073741824) return round($b / 1048576, 1) . ' MB';
        return round($b / 1073741824, 2) . ' GB';
    }
}
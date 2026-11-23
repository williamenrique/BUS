<?php
namespace App\Services;

use App\Core\Logger;

/**
 * BackupService · SITGO
 * Encapsula creación, restauración y gestión de backups de la BD.
 *
 * Estrategia:
 *   - Preferente: mysqldump / mysql (rápido y confiable).
 *   - Fallback:   PDO (cuando exec/proc_open están deshabilitados).
 *
 * Todos los archivos se guardan en storage/backup/*.sql
 * Los logs de restauración en storage/backup/logs/restore-YYYYMMDD.log
 */
class BackupService
{
    private string $dir;
    private string $logsDir;

    public function __construct()
    {
        $this->dir     = defined('BACKUP_DIR')      ? BACKUP_DIR      : (ROOT_PATH . 'storage/backup/');
        $this->logsDir = defined('BACKUP_LOGS_DIR') ? BACKUP_LOGS_DIR : ($this->dir . 'logs/');

        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0755, true);
        }
        if (!is_dir($this->logsDir)) {
            @mkdir($this->logsDir, 0755, true);
        }
    }

    /* ============================================================
       CREAR BACKUP
       ============================================================ */
    public function crear(string $tipo = 'manual', string $prefijo = 'backup'): array
    {
        $inicio  = microtime(true);
        $nombre  = $this->generarNombre($prefijo);
        $ruta    = $this->dir . $nombre;

        try {
            $bin = $this->detectarMysqldump();
            if ($bin) {
                $this->crearConCli($bin, $ruta);
            } else {
                $this->crearConPdo($ruta);
            }

            if (!is_file($ruta) || filesize($ruta) === 0) {
                throw new \RuntimeException('El archivo de backup quedó vacío o no se creó.');
            }

            $tam      = (int)filesize($ruta);
            $checksum = hash_file('sha256', $ruta);
            $duracion = round(microtime(true) - $inicio, 2);

            return [
                'ok'             => true,
                'archivo'        => $nombre,
                'ruta'           => $ruta,
                'tamanio_bytes'  => $tam,
                'checksum'       => $checksum,
                'duracion_seg'   => $duracion,
                'mensaje'        => "Backup creado ({$this->formatoTam($tam)}).",
            ];
        } catch (\Throwable $e) {
            if (is_file($ruta)) @unlink($ruta);
            Logger::exception('BACKUP_CREATE', $e);
            return [
                'ok'            => false,
                'archivo'       => $nombre,
                'tamanio_bytes' => 0,
                'duracion_seg'  => round(microtime(true) - $inicio, 2),
                'mensaje'       => 'Error al crear backup: ' . $e->getMessage(),
            ];
        }
    }

    /* ============================================================
       RESTAURAR BACKUP
       ============================================================ */
    public function restaurar(string $archivo): array
    {
        $inicio = microtime(true);
        $ruta   = $this->dir . basename($archivo);

        if (!$this->esArchivoValido($archivo)) {
            return ['ok' => false, 'mensaje' => 'Nombre de archivo inválido.', 'duracion_seg' => 0];
        }
        if (!is_file($ruta)) {
            return ['ok' => false, 'mensaje' => 'El archivo no existe.', 'duracion_seg' => 0];
        }

        try {
            $bin = $this->detectarMysql();
            if ($bin) {
                $this->restaurarConCli($bin, $ruta);
            } else {
                $this->restaurarConPdo($ruta);
            }

            $duracion = round(microtime(true) - $inicio, 2);
            $this->logRestore($archivo, 'OK', $duracion, '');

            return [
                'ok'           => true,
                'mensaje'      => "Restauración completada en {$duracion}s.",
                'duracion_seg' => $duracion,
            ];
        } catch (\Throwable $e) {
            $duracion = round(microtime(true) - $inicio, 2);
            $this->logRestore($archivo, 'ERROR', $duracion, $e->getMessage());
            Logger::exception('BACKUP_RESTORE', $e, ['archivo' => $archivo]);

            return [
                'ok'           => false,
                'mensaje'      => 'Error al restaurar: ' . $e->getMessage(),
                'duracion_seg' => $duracion,
            ];
        }
    }

    /* ============================================================
       LISTAR / ELIMINAR / DESCARGAR
       ============================================================ */
    public function listarArchivos(): array
    {
        $out = [];
        foreach (glob($this->dir . '*.sql') ?: [] as $f) {
            $out[] = [
                'archivo'       => basename($f),
                'tamanio'       => (int)filesize($f),
                'tamanio_fmt'   => $this->formatoTam((int)filesize($f)),
                'fecha'         => date('Y-m-d H:i:s', filemtime($f)),
                'fecha_ts'      => filemtime($f),
            ];
        }
        usort($out, fn($a, $b) => $b['fecha_ts'] <=> $a['fecha_ts']);
        return $out;
    }

    public function eliminar(string $archivo): bool
    {
        if (!$this->esArchivoValido($archivo)) return false;
        $ruta = $this->dir . $archivo;
        return is_file($ruta) && @unlink($ruta);
    }

    public function rutaDescarga(string $archivo): ?string
    {
        if (!$this->esArchivoValido($archivo)) return null;
        $ruta = $this->dir . $archivo;
        return is_file($ruta) ? $ruta : null;
    }

    public function tamanoTotal(): int
    {
        $t = 0;
        foreach (glob($this->dir . '*.sql') ?: [] as $f) {
            $t += (int)filesize($f);
        }
        return $t;
    }

    public function contarArchivos(): int
    {
        return count(glob($this->dir . '*.sql') ?: []);
    }

    /* ============================================================
       VALIDACIONES
       ============================================================ */
    public function esArchivoValido(string $archivo): bool
    {
        // Solo letras, números, guion, guion bajo, punto y debe terminar en .sql
        if (!preg_match('/^[A-Za-z0-9_\-\.]+\.sql$/', $archivo)) return false;
        if (str_contains($archivo, '..')) return false;
        return true;
    }

    /* ============================================================
       IMPLEMENTACIÓN CLI (mysqldump / mysql)
       ============================================================ */
    private function crearConCli(string $bin, string $ruta): void
    {
        $cmd = sprintf(
            '%s --host=%s --port=%s --user=%s %s --single-transaction --quick --routines --triggers --default-character-set=%s %s > %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg(DB_HOST),
            escapeshellarg((string)DB_PORT),
            escapeshellarg(DB_USER),
            DB_PASS !== '' ? '--password=' . escapeshellarg(DB_PASS) : '',
            escapeshellarg(DB_CHARSET),
            escapeshellarg(DB_NAME),
            escapeshellarg($ruta)
        );
        $this->exec($cmd);
    }

    private function restaurarConCli(string $bin, string $ruta): void
    {
        $cmd = sprintf(
            '%s --host=%s --port=%s --user=%s %s --default-character-set=%s %s < %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg(DB_HOST),
            escapeshellarg((string)DB_PORT),
            escapeshellarg(DB_USER),
            DB_PASS !== '' ? '--password=' . escapeshellarg(DB_PASS) : '',
            escapeshellarg(DB_CHARSET),
            escapeshellarg(DB_NAME),
            escapeshellarg($ruta)
        );
        $this->exec($cmd);
    }

    private function exec(string $cmd): void
    {
        if (!function_exists('exec')) {
            throw new \RuntimeException('exec() está deshabilitado en este servidor.');
        }
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);
        if ($code !== 0) {
            throw new \RuntimeException('Comando falló (código ' . $code . '): ' . implode(' | ', $out));
        }
    }

    /* ============================================================
       IMPLEMENTACIÓN PDO (fallback)
       ============================================================ */
    private function crearConPdo(string $ruta): void
    {
        $db = \App\Core\Database::getConnection();
        $fh = fopen($ruta, 'wb');
        if (!$fh) throw new \RuntimeException('No se pudo abrir el archivo para escritura.');

        try {
            fwrite($fh, "-- SITGO Backup (fallback PDO)\n");
            fwrite($fh, "-- Generado: " . date('Y-m-d H:i:s') . "\n");
            fwrite($fh, "-- Base de datos: " . DB_NAME . "\n\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
            fwrite($fh, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
            fwrite($fh, "SET NAMES utf8mb4;\n\n");

            $tablas = $db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

            foreach ($tablas as $tabla) {
                $create = $db->query("SHOW CREATE TABLE `{$tabla}`")->fetch(\PDO::FETCH_ASSOC);
                fwrite($fh, "\n-- --------------------------------------------------------\n");
                fwrite($fh, "-- Tabla: {$tabla}\n");
                fwrite($fh, "-- --------------------------------------------------------\n");
                fwrite($fh, "DROP TABLE IF EXISTS `{$tabla}`;\n");
                fwrite($fh, ($create['Create Table'] ?? '') . ";\n\n");

                $rows = $db->query("SELECT * FROM `{$tabla}`");
                $buffer = [];
                foreach ($rows as $row) {
                    $vals = array_map(function ($v) use ($db) {
                        if ($v === null) return 'NULL';
                        return $db->quote((string)$v);
                    }, array_values($row));
                    $buffer[] = '(' . implode(',', $vals) . ')';
                    if (count($buffer) >= 200) {
                        fwrite($fh, "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $buffer) . ";\n");
                        $buffer = [];
                    }
                }
                if ($buffer) {
                    fwrite($fh, "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $buffer) . ";\n");
                }
            }

            fwrite($fh, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        } finally {
            fclose($fh);
        }
    }

    private function restaurarConPdo(string $ruta): void
    {
        $db = \App\Core\Database::getConnection();
        $sql = file_get_contents($ruta);
        if ($sql === false || $sql === '') {
            throw new \RuntimeException('No se pudo leer el archivo SQL.');
        }

        // Ejecutar sentencia por sentencia (ignorando comentarios y vacías)
        $db->exec("SET FOREIGN_KEY_CHECKS=0");
        $sentencias = $this->splitSql($sql);
        foreach ($sentencias as $s) {
            $s = trim($s);
            if ($s === '' || str_starts_with($s, '--') || str_starts_with($s, '/*')) continue;
            $db->exec($s);
        }
        $db->exec("SET FOREIGN_KEY_CHECKS=1");
    }

    private function splitSql(string $sql): array
    {
        $out = [];
        $buf = '';
        $inStr = false;
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];
            if ($c === "'" && ($i === 0 || $sql[$i-1] !== '\\')) $inStr = !$inStr;
            if ($c === ';' && !$inStr) { $out[] = $buf; $buf = ''; continue; }
            $buf .= $c;
        }
        if (trim($buf) !== '') $out[] = $buf;
        return $out;
    }

    /* ============================================================
       DETECCIÓN DE BINARIOS
       ============================================================ */
    private function detectarMysqldump(): ?string
    {
        if (defined('BACKUP_MYSQLDUMP_PATH') && BACKUP_MYSQLDUMP_PATH && is_file(BACKUP_MYSQLDUMP_PATH)) {
            return BACKUP_MYSQLDUMP_PATH;
        }
        return $this->buscarBinario('mysqldump');
    }

    private function detectarMysql(): ?string
    {
        if (defined('BACKUP_MYSQL_PATH') && BACKUP_MYSQL_PATH && is_file(BACKUP_MYSQL_PATH)) {
            return BACKUP_MYSQL_PATH;
        }
        return $this->buscarBinario('mysql');
    }

    private function buscarBinario(string $nombre): ?string
    {
        if (!function_exists('exec')) return null;

        $cmd = stripos(PHP_OS_FAMILY, 'Windows') !== false
            ? "where {$nombre} 2>NUL"
            : "command -v {$nombre} 2>/dev/null";

        $out = [];
        $code = 0;
        @exec($cmd, $out, $code);
        if ($code !== 0 || empty($out[0])) return null;
        $bin = trim($out[0]);
        return is_file($bin) ? $bin : null;
    }

    /* ============================================================
       HELPERS
       ============================================================ */
    private function generarNombre(string $prefijo): string
    {
        $slug = preg_replace('/[^a-z0-9_\-]/i', '_', strtolower($prefijo));
        return $slug . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.sql';
    }

    private function formatoTam(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        if ($bytes < 1024 * 1024 * 1024) return round($bytes / (1024 * 1024), 1) . ' MB';
        return round($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }

    private function logRestore(string $archivo, string $estado, float $duracion, string $detalle): void
    {
        $line = sprintf("[%s] [%s] archivo=%s duracion=%ss %s\n",
            date('Y-m-d H:i:s'), $estado, $archivo, $duracion, $detalle);
        @file_put_contents($this->logsDir . 'restore-' . date('Ymd') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
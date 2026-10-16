<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerUsuario extends Controller{

    /**
     * Vista principal.
     */
    public function index(): void{
        AuthGuard::check();
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('usuarios.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Usuarios',
            'scripts'   => ['usuarios.js'],
        ]);
    }

    /**
     * Listado paginado con filtros.
     */
    public function listar(): void{
        AuthGuard::check();

        $q      = trim((string)($_GET['q'] ?? ''));
        $limit  = max(1, min(100, (int)($_GET['limit'] ?? 10)));
        $offset = max(0, (int)($_GET['offset'] ?? 0));

        $filtros = [
            'status'       => $_GET['status']       ?? '',
            'rol'          => $_GET['rol']          ?? '',
            'departamento' => $_GET['departamento'] ?? '',
        ];

        try{
            $m = new ModelUsuario();
            $r = $m->listarPaginado($q, $limit, $offset, $filtros);

            $this->json([
                'ok'             => true,
                'data'           => $r['data'],
                'total'          => $r['total'],
                'totalFiltrados' => $r['totalFiltrados'],
                'page'           => (int)floor($offset / $limit) + 1,
                'limit'          => $limit,
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Combos para el formulario.
     */
    public function combos(): void{
        AuthGuard::check();
        $m = new ModelUsuario();

        $personalId = isset($_GET['personal_id']) ? (int)$_GET['personal_id'] : null;

        $this->json([
            'ok'            => true,
            'roles'         => $m->roles(),
            'departamentos' => $m->departamentosCombo(),
            'estaciones'    => $m->estacionesCombo(),
            'personal'      => $m->personalDisponible($personalId),
        ]);
    }

    /**
     * Obtiene un usuario por ID (sin contraseña).
     */
    public function obtener(string $id): void{
        AuthGuard::check();
        $row = (new ModelUsuario())->find((int)$id);
        if (!$row) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        unset($row['usuario_password']);
        $this->json(['ok' => true, 'data' => $row]);
    }

    /**
     * Endpoint: devuelve el siguiente nick disponible para un personal.
     */
    public function siguienteNick(): void{
        AuthGuard::check();

        $personalId = (int)($_GET['personal_id'] ?? 0);
        if ($personalId <= 0){
            $this->json(['ok' => false, 'message' => 'Personal inválido.'], 422);
        }

        $m = new ModelUsuario();
        $nick = $m->generarNickDesdePersonal($personalId);

        if ($nick === null){
            $this->json(['ok' => false, 'message' => 'Personal no encontrado.'], 404);
        }

        $this->json(['ok' => true, 'nick' => $nick]);
    }

    /**
     * Crea un usuario.
     * - Nick autogenerado en MAYÚSCULAS
     * - Password por defecto '123456'
     * - Estación opcional → 0
     * - Imagen: si no suben una, se copia default.png a la carpeta del usuario
     */
    public function guardar(): void{
        AuthGuard::check();

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        // Personal obligatorio
        if (empty($d['usuario_id_personal'])){
            $this->json([
                'ok' => false,
                'errors' => ['usuario_id_personal' => 'Debes vincular un personal.']
            ], 422);
        }

        // Estación opcional → 0
        $d['usuario_estacion_id'] = !empty($d['usuario_estacion_id'])
            ? (int)$d['usuario_estacion_id']
            : 0;

        $m = new ModelUsuario();

        // Nick autogenerado (MAYÚSCULAS)
        $nick = $m->generarNickDesdePersonal((int)$d['usuario_id_personal']);
        if (!$nick){
            $this->json(['ok' => false, 'message' => 'No se pudo generar el usuario.'], 500);
        }
        $d['usuario_nick'] = $nick;

        // Validaciones
        $v = new Validator();
        $v->required('usuario_rol_id',          $d['usuario_rol_id'] ?? '', 'Rol')
          ->required('usuario_departamento_id', $d['usuario_departamento_id'] ?? '', 'Departamento');
        if ($v->fails()) $this->json(['ok' => false, 'errors' => $v->errors()], 422);

        if ($m->existeNick($d['usuario_nick'])){
            $this->json(['ok' => false, 'errors' => ['usuario_nick' => 'El usuario generado ya existe. Intenta de nuevo.']], 422);
        }

        // ============================================================
        // IMAGEN: subida o default.png
        // ============================================================
        if (!empty($_FILES['usuario_imagen']['name'])){
            $img = $this->subirImagen($_FILES['usuario_imagen'], $d['usuario_nick']);
            if (!$img['ok']) $this->json(['ok' => false, 'errors' => ['usuario_imagen' => $img['msg']]], 422);
            $d['usuario_imagen'] = $img['file'];
        } else {
            // Copiar default.png al folder del usuario
            $d['usuario_imagen'] = $this->copiarDefault($d['usuario_nick']);
        }

        // Password por defecto
        $d['usuario_password'] = hash_password('123456');

        try{
            $id = $m->create($d);
            $this->json([
                'ok'      => true,
                'message' => "Usuario \"{$d['usuario_nick']}\" creado. Contraseña por defecto: 123456",
                'id'      => $id,
                'nick'    => $d['usuario_nick'],
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza un usuario. No toca nick ni contraseña.
     * Si sube nueva imagen, borra la anterior.
     */
    public function actualizar(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        $d['usuario_estacion_id'] = !empty($d['usuario_estacion_id'])
            ? (int)$d['usuario_estacion_id']
            : 0;

        $v = new Validator();
        $v->required('usuario_rol_id',          $d['usuario_rol_id'] ?? '', 'Rol')
          ->required('usuario_departamento_id', $d['usuario_departamento_id'] ?? '', 'Departamento');
        if ($v->fails()) $this->json(['ok' => false, 'errors' => $v->errors()], 422);

        $m = new ModelUsuario();
        $existente = $m->find($id);
        if (!$existente) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        // Imagen nueva (opcional) → borra la anterior si no es la default
        if (!empty($_FILES['usuario_imagen']['name'])){
            $img = $this->subirImagen($_FILES['usuario_imagen'], $existente['usuario_nick']);
            if (!$img['ok']) $this->json(['ok' => false, 'errors' => ['usuario_imagen' => $img['msg']]], 422);
            $d['usuario_imagen'] = $img['file'];

            // Borrar imagen anterior (si no es _default.png)
            $old = $existente['usuario_imagen'] ?? '';
            if ($old && strpos($old, '_default.png') === false){
                $this->borrarArchivo($old);
            }
        }

        try{
            $m->update($id, $d);
            $this->json(['ok' => true, 'message' => 'Usuario actualizado correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina la imagen del usuario y restaura la default.
     */
    public function eliminarImagen(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        $m = new ModelUsuario();
        $u = $m->find($id);
        if (!$u) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        // Borrar archivo actual si no es _default.png
        $actual = $u['usuario_imagen'] ?? '';
        if ($actual && strpos($actual, '_default.png') === false){
            $this->borrarArchivo($actual);
        }

        // Copiar default
        $default = $this->copiarDefault($u['usuario_nick']);

        try{
            $m->updateImagen($id, $default);
            $this->json([
                'ok' => true,
                'message' => 'Imagen eliminada. Se restauró la predeterminada.',
                'imagen'  => $default,
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Restablece la contraseña.
     */
    public function resetPassword(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $nueva = trim((string)($d['nueva_password'] ?? ''));

        if ($nueva === '' || mb_strlen($nueva) < 4){
            $this->json(['ok' => false, 'message' => 'La contraseña debe tener al menos 4 caracteres.'], 422);
        }

        $m = new ModelUsuario();
        if (!$m->find($id)) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        try{
            $m->updatePassword($id, hash_password($nueva));
            $this->json(['ok' => true, 'message' => 'Contraseña restablecida correctamente.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Elimina un usuario (y su carpeta de imágenes).
     */
    public function eliminar(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        $current = auth_user();
        if ((int)$current['id'] === $id){
            $this->json(['ok' => false, 'message' => 'No puedes eliminar tu propio usuario.'], 422);
        }

        $m = new ModelUsuario();
        $u = $m->find($id);
        if (!$u) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        try{
            // Borrar carpeta completa del usuario
            $this->borrarCarpetaUsuario($u['usuario_nick']);

            $m->delete($id);
            $this->json(['ok' => true, 'message' => 'Usuario eliminado.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'No se pudo eliminar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Activa / desactiva un usuario.
     */
    public function cambiarStatus(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        $current = auth_user();
        if ((int)$current['id'] === $id){
            $this->json(['ok' => false, 'message' => 'No puedes cambiar tu propio estatus.'], 422);
        }

        $d = $this->input();
        $status = (int)($d['usuario_status'] ?? 1);

        $m = new ModelUsuario();
        if (!$m->find($id)) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        try{
            $m->cambiarStatus($id, $status);
            $this->json(['ok' => true, 'message' => 'Estatus actualizado.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       HELPERS PRIVADOS
       ============================================================ */

    /**
     * Normaliza el nick a MAYÚSCULAS.
     */
    private function normalizar(array $d): array{
        if (isset($d['usuario_nick'])){
            $d['usuario_nick'] = strtoupper(trim((string)$d['usuario_nick']));
        }
        return $d;
    }

    /**
     * Sanitiza el nick para usarlo como nombre de carpeta.
     */
    private function sanitizarCarpeta(string $nick): string{
        $carpeta = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper(trim($nick)));
        return $carpeta === '' ? 'USUARIO' : $carpeta;
    }

    /**
     * Devuelve la ruta física base de storage.
     */
    private function storageBase(): string{
        return PUBLIC_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;
    }

    /**
     * Copia public_html/img/default.png a la carpeta del usuario.
     * Devuelve la ruta relativa para guardar en BD ('NICK/NICK_default.png').
     */
    private function copiarDefault(string $nick): string{
        $carpeta    = $this->sanitizarCarpeta($nick);
        $baseDir    = $this->storageBase();
        $carpetaDir = $baseDir . $carpeta . DIRECTORY_SEPARATOR;

        if (!is_dir($carpetaDir)){
            if (!mkdir($carpetaDir, 0755, true)) return '';
        }

        $origen  = PUBLIC_PATH . 'img' . DIRECTORY_SEPARATOR . 'default.png';
        $nombre  = $carpeta . '_default.png';
        $destino = $carpetaDir . $nombre;

        if (!file_exists($origen)) return '';

        // Si ya existe, no copiar de nuevo
        if (!file_exists($destino)){
            if (!copy($origen, $destino)) return '';
        }

        return $carpeta . '/' . $nombre;
    }

    /**
     * Borra un archivo de la carpeta storage.
     */
    private function borrarArchivo(string $rutaRelativa): void{
        if ($rutaRelativa === '') return;
        $path = $this->storageBase() . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaRelativa);
        if (file_exists($path) && is_file($path)){
            @unlink($path);
        }
    }

    /**
     * Borra toda la carpeta de un usuario.
     */
    private function borrarCarpetaUsuario(string $nick): void{
        $carpeta = $this->sanitizarCarpeta($nick);
        $dir = $this->storageBase() . $carpeta . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) return;

        foreach (glob($dir . '*') as $f){
            if (is_file($f)) @unlink($f);
        }
        @rmdir($dir);
    }

    /**
     * Sube una imagen a public_html/uploads/storage/{NICK}/{archivo}.
     * Validaciones:
     *   - Máx 2MB
     *   - Solo JPG / PNG / WEBP
     *   - Verifica que sea imagen real (getimagesize)
     *   - Mínimo 50x50 px
     *   - Máximo 2000x2000 px
     */
    private function subirImagen(array $file, string $nick): array{
        $maxSize = 2 * 1024 * 1024; // 2MB
        $tipos   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if ($file['error'] !== UPLOAD_ERR_OK){
            return ['ok' => false, 'msg' => 'Error al subir el archivo.'];
        }
        if ($file['size'] > $maxSize){
            return ['ok' => false, 'msg' => 'La imagen no puede superar 2MB.'];
        }
        if ($file['size'] < 100){
            return ['ok' => false, 'msg' => 'El archivo es demasiado pequeño.'];
        }

        // MIME real (no confiar en el que envía el navegador)
        $mime = @mime_content_type($file['tmp_name']);
        if (!isset($tipos[$mime])){
            return ['ok' => false, 'msg' => 'Formato no permitido. Solo JPG, PNG o WEBP.'];
        }

        // Verificar que sea imagen real
        $info = @getimagesize($file['tmp_name']);
        if ($info === false){
            return ['ok' => false, 'msg' => 'El archivo no es una imagen válida.'];
        }
        if ($info[0] < 50 || $info[1] < 50){
            return ['ok' => false, 'msg' => 'La imagen debe ser al menos de 50x50 px.'];
        }
        if ($info[0] > 2000 || $info[1] > 2000){
            return ['ok' => false, 'msg' => 'La imagen no puede superar 2000x2000 px.'];
        }

        // Preparar carpeta
        $carpeta    = $this->sanitizarCarpeta($nick);
        $baseDir    = $this->storageBase();
        $carpetaDir = $baseDir . $carpeta . DIRECTORY_SEPARATOR;

        if (!is_dir($carpetaDir)){
            if (!mkdir($carpetaDir, 0755, true)){
                return ['ok' => false, 'msg' => 'No se pudo crear la carpeta del usuario.'];
            }
        }

        // Nombre final: {NICK}_{timestamp}_{rand}.ext
        $nombre = $carpeta . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $tipos[$mime];

        if (!move_uploaded_file($file['tmp_name'], $carpetaDir . $nombre)){
            return ['ok' => false, 'msg' => 'No se pudo guardar la imagen.'];
        }

        return [
            'ok'   => true,
            'file' => $carpeta . '/' . $nombre,
            'path' => $carpeta . '/' . $nombre,
        ];
    }
}
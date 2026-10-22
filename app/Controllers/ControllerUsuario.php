<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerUsuario extends Controller{

    /* ============================================================
       VISTA PRINCIPAL
       ============================================================ */
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

    /* ============================================================
       LISTAR (paginado)
       ============================================================ */
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

    /* ============================================================
       COMBOS
       ============================================================ */
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

    /* ============================================================
       OBTENER
       ============================================================ */
    public function obtener(string $id): void{
        AuthGuard::check();
        $row = (new ModelUsuario())->find((int)$id);
        if (!$row) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        unset($row['usuario_password']);
        $this->json(['ok' => true, 'data' => $row]);
    }

    /* ============================================================
       SIGUIENTE NICK
       ============================================================ */
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

    /* ============================================================
       GUARDAR (crear)
       ============================================================ */
    public function guardar(): void{
        AuthGuard::check();

        $d = $this->input();
        if (empty($d)) $d = $_POST;
        $d = $this->normalizar($d);

        if (empty($d['usuario_id_personal'])){
            $this->json([
                'ok' => false,
                'errors' => ['usuario_id_personal' => 'Debes vincular un personal.']
            ], 422);
        }

        $d['usuario_estacion_id'] = !empty($d['usuario_estacion_id'])
            ? (int)$d['usuario_estacion_id']
            : 0;

        $m = new ModelUsuario();

        $nick = $m->generarNickDesdePersonal((int)$d['usuario_id_personal']);
        if (!$nick){
            $this->json(['ok' => false, 'message' => 'No se pudo generar el usuario.'], 500);
        }
        $d['usuario_nick'] = $nick;

        $v = new Validator();
        $v->required('usuario_rol_id',          $d['usuario_rol_id'] ?? '', 'Rol')
          ->required('usuario_departamento_id', $d['usuario_departamento_id'] ?? '', 'Departamento');
        if ($v->fails()) $this->json(['ok' => false, 'errors' => $v->errors()], 422);

        if ($m->existeNick($d['usuario_nick'])){
            $this->json(['ok' => false, 'errors' => ['usuario_nick' => 'El usuario generado ya existe. Intenta de nuevo.']], 422);
        }

        if (!empty($_FILES['usuario_imagen']['name'])){
            $img = $this->subirImagen($_FILES['usuario_imagen'], $d['usuario_nick']);
            if (!$img['ok']) $this->json(['ok' => false, 'errors' => ['usuario_imagen' => $img['msg']]], 422);
            $d['usuario_imagen'] = $img['file'];
        } else {
            $d['usuario_imagen'] = $this->copiarDefault($d['usuario_nick']);
        }

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

    /* ============================================================
       ACTUALIZAR
       ============================================================ */
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

        if (!empty($_FILES['usuario_imagen']['name'])){
            $img = $this->subirImagen($_FILES['usuario_imagen'], $existente['usuario_nick']);
            if (!$img['ok']) $this->json(['ok' => false, 'errors' => ['usuario_imagen' => $img['msg']]], 422);
            $d['usuario_imagen'] = $img['file'];

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

    /* ============================================================
       ELIMINAR IMAGEN
       ============================================================ */
    public function eliminarImagen(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        $m = new ModelUsuario();
        $u = $m->find($id);
        if (!$u) $this->json(['ok' => false, 'message' => 'Usuario no encontrado.'], 404);

        $actual = $u['usuario_imagen'] ?? '';
        if ($actual && strpos($actual, '_default.png') === false){
            $this->borrarArchivo($actual);
        }

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

    /* ============================================================
       RESET PASSWORD
       ============================================================ */
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

    /* ============================================================
       ELIMINAR
       ============================================================ */
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
            $this->borrarCarpetaUsuario($u['usuario_nick']);
            $m->delete($id);
            $this->json(['ok' => true, 'message' => 'Usuario eliminado.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'No se pudo eliminar: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       CAMBIAR STATUS
       ============================================================ */
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
       MENÚS ASIGNADOS AL USUARIO
       ============================================================ */

    /**
     * Devuelve el árbol completo de menús activos + los IDs asignados
     * al usuario indicado.
     * GET /usuarios/menus/{id}
     */
    public function menusAsignados(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        try{
            $db = \App\Core\Database::getConnection();

            // Árbol completo de menús activos
            $sql = "SELECT menu_id, menu_padre_id, menu_nombre, menu_icono,
                           menu_ruta, menu_orden, menu_es_desplegable
                    FROM table_men_menu
                    WHERE menu_estado = 1
                    ORDER BY menu_orden ASC, menu_id ASC";
            $stmt = $db->query($sql);
            $flat = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Construir árbol
            $byId = [];
            foreach ($flat as $item){
                $item['children'] = [];
                $byId[$item['menu_id']] = $item;
            }
            $tree = [];
            foreach ($byId as $mid => &$item){
                $parent = $item['menu_padre_id'];
                if ($parent && isset($byId[$parent])){
                    $byId[$parent]['children'][] = &$item;
                } else {
                    $tree[] = &$item;
                }
            }
            unset($item);

            // Menús ya asignados al usuario
            $stmt = $db->prepare(
                "SELECT menu_id FROM table_men_usuario_menu WHERE usuario_id = :uid"
            );
            $stmt->execute(['uid' => $id]);
            $asignados = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'menu_id');

            $this->json([
                'ok'        => true,
                'arbol'     => $tree,
                'asignados' => array_map('intval', $asignados),
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Guarda los menús asignados al usuario. Reemplaza todo lo anterior.
     * POST /usuarios/menus/guardar/{id}
     */
    public function guardarMenus(string $id): void{
        AuthGuard::check();
        $id = (int)$id;

        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $menus = $d['menus'] ?? [];
        if (!is_array($menus)) $menus = [];

        $db = \App\Core\Database::getConnection();
        try{
            $db->beginTransaction();

            $stmt = $db->prepare("DELETE FROM table_men_usuario_menu WHERE usuario_id = :uid");
            $stmt->execute(['uid' => $id]);

            if (!empty($menus)){
                $stmt = $db->prepare(
                    "INSERT INTO table_men_usuario_menu (usuario_id, menu_id)
                     VALUES (:uid, :mid)"
                );
                foreach ($menus as $mid){
                    $mid = (int)$mid;
                    if ($mid > 0){
                        $stmt->execute(['uid' => $id, 'mid' => $mid]);
                    }
                }
            }

            $db->commit();
            $this->json([
                'ok'      => true,
                'message' => 'Menús asignados correctamente. (' . count($menus) . ' seleccionados)'
            ]);
        } catch (\Throwable $e){
            $db->rollBack();
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       HELPERS PRIVADOS
       ============================================================ */

    private function normalizar(array $d): array{
        if (isset($d['usuario_nick'])){
            $d['usuario_nick'] = strtoupper(trim((string)$d['usuario_nick']));
        }
        return $d;
    }

    private function sanitizarCarpeta(string $nick): string{
        $carpeta = preg_replace('/[^A-Za-z0-9_-]/', '_', strtoupper(trim($nick)));
        return $carpeta === '' ? 'USUARIO' : $carpeta;
    }

    private function storageBase(): string{
        return PUBLIC_PATH . 'uploads' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR;
    }

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

        if (!file_exists($destino)){
            if (!copy($origen, $destino)) return '';
        }

        return $carpeta . '/' . $nombre;
    }

    private function borrarArchivo(string $rutaRelativa): void{
        if ($rutaRelativa === '') return;
        $path = $this->storageBase() . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rutaRelativa);
        if (file_exists($path) && is_file($path)){
            @unlink($path);
        }
    }

    private function borrarCarpetaUsuario(string $nick): void{
        $carpeta = $this->sanitizarCarpeta($nick);
        $dir = $this->storageBase() . $carpeta . DIRECTORY_SEPARATOR;
        if (!is_dir($dir)) return;

        foreach (glob($dir . '*') as $f){
            if (is_file($f)) @unlink($f);
        }
        @rmdir($dir);
    }

    private function subirImagen(array $file, string $nick): array{
        $maxSize = 2 * 1024 * 1024;
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

        $mime = @mime_content_type($file['tmp_name']);
        if (!isset($tipos[$mime])){
            return ['ok' => false, 'msg' => 'Formato no permitido. Solo JPG, PNG o WEBP.'];
        }

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

        $carpeta    = $this->sanitizarCarpeta($nick);
        $baseDir    = $this->storageBase();
        $carpetaDir = $baseDir . $carpeta . DIRECTORY_SEPARATOR;

        if (!is_dir($carpetaDir)){
            if (!mkdir($carpetaDir, 0755, true)){
                return ['ok' => false, 'msg' => 'No se pudo crear la carpeta del usuario.'];
            }
        }

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
        /* ============================================================
       DASHBOARD · Sesiones activas y forzar logout
       ============================================================ */
    public function sesionesActivas(): void
    {
        AuthGuard::check();
        $u = auth_user();
        if ((int)($u['departamento_id'] ?? 0) !== DEPARTAMENTO_SISTEMA) {
            $this->json(['success' => false, 'message' => 'No autorizado.'], 403);
            return;
        }

        $this->safe(function () {
            $db = \App\Core\Database::getConnection();
            $sql = "SELECT s.id, s.session_id, s.usuario_id, s.ip_address,
                           s.usuario_agent, s.created_at, s.last_activity,
                           u.usuario_nick, u.usuario_status,
                           p.personal_nombre AS nombres,
                           p.personal_apellido AS apellidos
                    FROM table_usuario_sessions s
                    INNER JOIN table_usuarios u ON u.usuario_id = s.usuario_id
                    LEFT JOIN table_personal p ON p.id_personal = u.usuario_id_personal
                    WHERE s.last_activity >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                    ORDER BY s.last_activity DESC";
            $rows = $db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            // Formatear fecha
            foreach ($rows as &$r) {
                if (!empty($r['created_at'])) {
                    $r['created_at'] = date('d/m/Y h:i A', strtotime($r['created_at']));
                }
            }
            unset($r);

            $this->json(['success' => true, 'data' => $rows]);
        });
    }

    public function forzarLogout(): void {
        AuthGuard::check();
        $u = auth_user();
        if ((int)($u['departamento_id'] ?? 0) !== DEPARTAMENTO_SISTEMA) {
            $this->json(['success' => false, 'message' => 'No autorizado.'], 403);
            return;
        }

        $d = $this->input();
        $nick = trim((string)($d['nick'] ?? ''));
        if ($nick === '') {
            $this->json(['success' => false, 'message' => 'Usuario inválido.'], 422);
            return;
        }

        $this->safe(function () use ($nick) {
            $db = \App\Core\Database::getConnection();

            // Buscar usuario
            $stmt = $db->prepare("SELECT usuario_id FROM table_usuarios WHERE usuario_nick = :n LIMIT 1");
            $stmt->execute(['n' => $nick]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$row) {
                $this->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
                return;
            }
            $uid = (int)$row['usuario_id'];

            // Borrar todas sus sesiones activas
            $del = $db->prepare("DELETE FROM table_usuario_sessions WHERE usuario_id = :u");
            $del->execute(['u' => $uid]);

            $this->json(['success' => true, 'message' => "Sesión de {$nick} cerrada."]);
        });
    }
}
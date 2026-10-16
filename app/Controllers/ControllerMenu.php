<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Validator;
use App\Models\ModelMenu;
use App\Models\ModelUsuario;
use App\Middleware\AuthGuard;

class ControllerMenu extends Controller{

    /* ============================================================
       VISTA PRINCIPAL
       ============================================================ */
    public function index(): void{
        AuthGuard::check();
        $user = auth_user();
        $mUser = new ModelUsuario();

        $this->view('menu.index', [
            'user'      => $user,
            'menu'      => $mUser->getMenuJerarquico((int)$user['id'], (int)$user['departamento_id']),
            'pageTitle' => 'Gestión de Menús',
            'scripts'   => ['menu.js'],
        ]);
    }

    /* ============================================================
       ÁRBOL (JSON)
       ============================================================ */
    public function arbol(): void{
        AuthGuard::check();
        try{
            $m = new ModelMenu();
            $this->json(['ok' => true, 'data' => $m->arbol()]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       OBTENER
       ============================================================ */
    public function obtener(string $id): void{
        AuthGuard::check();
        $row = (new ModelMenu())->find((int)$id);
        if (!$row){ $this->json(['ok' => false, 'message' => 'Menú no encontrado.'], 404); return; }
        $this->json(['ok' => true, 'data' => $row]);
    }

    /* ============================================================
       COMBOS (menús padre, usuarios, departamentos)
       ============================================================ */
    public function combos(): void{
        AuthGuard::check();
        $m = new ModelMenu();
        $this->json([
            'ok'            => true,
            'padres'        => $m->menusPadre(),
            'usuarios'      => $m->todosUsuarios(),
            'departamentos' => $m->todosDepartamentos(),
        ]);
    }

    /* ============================================================
       GUARDAR (crear)
       ============================================================ */
    public function guardar(): void{
        AuthGuard::check();
        $d = $this->input();
        if (empty($d)) $d = $_POST;

        $v = new Validator();
        $v->required('menu_nombre', $d['menu_nombre'] ?? '', 'Nombre');
        if ($v->fails()){ $this->json(['ok' => false, 'errors' => $v->errors()], 422); return; }

        try{
            $m = new ModelMenu();
            $id = $m->create($d);
            $this->json(['ok' => true, 'message' => 'Menú creado correctamente.', 'id' => $id]);
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

        $v = new Validator();
        $v->required('menu_nombre', $d['menu_nombre'] ?? '', 'Nombre');
        if ($v->fails()){ $this->json(['ok' => false, 'errors' => $v->errors()], 422); return; }

        $m = new ModelMenu();
        if (!$m->find($id)){ $this->json(['ok' => false, 'message' => 'Menú no encontrado.'], 404); return; }

        // Evitar ciclos: padre no puede ser el mismo ni un descendiente
        if (!empty($d['menu_padre_id']) && (int)$d['menu_padre_id'] === $id){
            $this->json(['ok' => false, 'errors' => ['menu_padre_id' => 'Un menú no puede ser padre de sí mismo.']], 422);
            return;
        }

        try{
            $m->update($id, $d);
            $this->json(['ok' => true, 'message' => 'Menú actualizado correctamente.']);
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

        $m = new ModelMenu();
        if (!$m->find($id)){ $this->json(['ok' => false, 'message' => 'Menú no encontrado.'], 404); return; }

        try{
            $m->delete($id);
            $this->json(['ok' => true, 'message' => 'Menú eliminado.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       ESTADO
       ============================================================ */
    public function cambiarEstado(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $estado = (int)($d['menu_estado'] ?? 1);

        try{
            (new ModelMenu())->cambiarEstado($id, $estado);
            $this->json(['ok' => true, 'message' => 'Estado actualizado.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       RUTAS
       ============================================================ */
    public function rutas(string $id): void{
        AuthGuard::check();
        try{
            $this->json(['ok' => true, 'data' => (new ModelMenu())->rutasDe((int)$id)]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function guardarRuta(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $patron = trim((string)($d['patron'] ?? ''));

        if ($patron === ''){ $this->json(['ok' => false, 'message' => 'El patrón es obligatorio.'], 422); return; }

        try{
            (new ModelMenu())->crearRuta($id, $patron);
            $this->json(['ok' => true, 'message' => 'Ruta añadida.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function eliminarRuta(string $rutaId): void{
        AuthGuard::check();
        try{
            (new ModelMenu())->eliminarRuta((int)$rutaId);
            $this->json(['ok' => true, 'message' => 'Ruta eliminada.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       USUARIOS ASIGNADOS
       ============================================================ */
    public function usuariosAsignados(string $id): void{
        AuthGuard::check();
        try{
            $this->json(['ok' => true, 'data' => (new ModelMenu())->usuariosAsignados((int)$id)]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function asignarUsuario(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $userId = (int)($d['usuario_id'] ?? 0);
        if ($userId <= 0){ $this->json(['ok' => false, 'message' => 'Usuario inválido.'], 422); return; }

        try{
            $ok = (new ModelMenu())->asignarUsuario($id, $userId);
            $this->json([
                'ok'      => true,
                'message' => $ok ? 'Usuario asignado.' : 'El usuario ya estaba asignado.'
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function quitarUsuario(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $userId = (int)($d['usuario_id'] ?? 0);
        if ($userId <= 0){ $this->json(['ok' => false, 'message' => 'Usuario inválido.'], 422); return; }

        try{
            (new ModelMenu())->quitarUsuario($id, $userId);
            $this->json(['ok' => true, 'message' => 'Usuario removido.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /* ============================================================
       DEPARTAMENTOS ASIGNADOS
       ============================================================ */
    public function departamentosAsignados(string $id): void{
        AuthGuard::check();
        try{
            $this->json(['ok' => true, 'data' => (new ModelMenu())->departamentosAsignados((int)$id)]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function asignarDepartamento(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $deptoId = (int)($d['departamento_id'] ?? 0);
        if ($deptoId <= 0){ $this->json(['ok' => false, 'message' => 'Departamento inválido.'], 422); return; }

        try{
            $ok = (new ModelMenu())->asignarDepartamento($id, $deptoId);
            $this->json([
                'ok'      => true,
                'message' => $ok ? 'Departamento asignado.' : 'El departamento ya estaba asignado.'
            ]);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function quitarDepartamento(string $id): void{
        AuthGuard::check();
        $id = (int)$id;
        $d = $this->input();
        $deptoId = (int)($d['departamento_id'] ?? 0);
        if ($deptoId <= 0){ $this->json(['ok' => false, 'message' => 'Departamento inválido.'], 422); return; }

        try{
            (new ModelMenu())->quitarDepartamento($id, $deptoId);
            $this->json(['ok' => true, 'message' => 'Departamento removido.']);
        } catch (\Throwable $e){
            $this->json(['ok' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
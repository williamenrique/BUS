<?php
header('Access-Control-Allow-Origin: *');
class BienesTaller extends Controllers{
    private $db;
    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        parent::__construct();
    }
	function getActiveSession(){
		$reuest = $this->model->getActiveSession($_SESSION['idUser']);
	}
    public function validateSession() {
        if (empty($_SESSION['login']) || empty($_SESSION['idUser'])) {
            return false;
        }
        if (isset($_SESSION['session_id'])) {
            $validSession = validateSessionDB($_SESSION['session_id'], $_SESSION['idUser']);
            if (!$validSession) {
                deleteSession($_SESSION['session_id']);
                return false;
            }
        }
        return true;
    }
	private function handleDatabaseError($error) {
        error_log("Error de BD en controlador BienesTaller: " . $error);
        if ($this->isAjaxRequest()) {
            $arrResponse = [
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $error
            ];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        } else {
            $_SESSION['error_message'] = "Error de base de datos: " . $error;
        }
    }
    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    private function resolverIdInstitucion(): int {
        if (!empty($_POST['id_institucion'])) {
            return intval($_POST['id_institucion']);
        }
        if (!empty($_GET['id_institucion'])) {
            return intval($_GET['id_institucion']);
        }
        if (!empty($_SESSION['id_institucion'])) {
            return intval($_SESSION['id_institucion']);
        }
        return 1;
    }

    private function obtenerNombreInstitucion(int $idInstitucion): string {
        $default = 'SERVICIO SOCIALISTA DE LOGISTICA, MANTENIMIENTO Y TRANSPORTE DEL ESTADO YARACUY';
        try {
            $sql = "SELECT nombre FROM table_instituciones WHERE id_institucion = ?";
            $row = $this->model->select($sql, [$idInstitucion]);
            if ($row && !empty($row['nombre'])) {
                return $row['nombre'];
            }
        } catch (Exception $e) {
            error_log("BienesTallerController::obtenerNombreInstitucion ERROR: " . $e->getMessage());
        }
        return $default;
    }

    public function bienesTaller(){
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $idInstitucion = $this->resolverIdInstitucion();

        $data = [
            'page_tag' => "BIENES TALLER SSMLTY",
            'page_title' => "Pagina Principal",
            'page_name' => "bienes_taller",
            'page_link' => "bienes_taller",
            'page_functions' => "function.bienes_taller.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => $this->obtenerNombreInstitucion($idInstitucion)
        ];
        $data['departamentos'] = $this->model->getDepartamentos();
        $this->views->getViews($this, "bienes_taller", $data);
    }

    public function getInitialData() {
        try {
            $data['departamentos'] = $this->model->getDepartamentos();
            $data['grupos'] = $this->model->getGrupos();
            $data['subgrupos'] = $this->model->getSubgrupos();
            $data['secciones'] = $this->model->getSecciones();
            $arrResponse = ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getBienesTaller() {
        try {
            $arrData = $this->model->selectBienesTaller();
            for ($i=0; $i < count($arrData); $i++) {
                $btnEdit = '<button class="btn btn-primary btn-sm" onClick="fntEditBienTaller('.$arrData[$i]['id_bien_taller'].')" title="Editar"><i class="fas fa-pencil-alt"></i></button>';
                $btnDelete = '<button class="btn btn-danger btn-sm" onClick="fntDelBienTaller('.$arrData[$i]['id_bien_taller'].')" title="Eliminar"><i class="far fa-trash-alt"></i></button>';
                $arrData[$i]['acciones'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getBienTaller($id_bien_taller) {
        try {
            $id_bien_taller = intval($id_bien_taller);
            if ($id_bien_taller > 0) {
                $arrData = $this->model->selectBienTaller($id_bien_taller);
                if (empty($arrData)) {
                    $arrResponse = ['success' => false, 'message' => 'Datos no encontrados.'];
                } else {
                    if (!empty($arrData['fecha_adquisicion']) && $arrData['fecha_adquisicion'] != '0000-00-00') {
                        $date = DateTime::createFromFormat('d/m/Y', $arrData['fecha_adquisicion']);
                        if ($date === false) {
                            $date = DateTime::createFromFormat('Y-m-d H:i:s', $arrData['fecha_adquisicion']);
                            if ($date === false) {
                                $date = DateTime::createFromFormat('Y-m-d', $arrData['fecha_adquisicion']);
                            }
                        }
                        $arrData['fecha_adquisicion'] = ($date) ? $date->format('Y-m-d') : '';
                    } else {
                        $arrData['fecha_adquisicion'] = '';
                    }
                    $arrResponse = ['success' => true, 'data' => $arrData];
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function setBienTaller() {
        if ($_POST) {
            try {
                $id_bien_taller = intval($_POST['id_bien_taller'] ?? 0);
                $data = [
                    'bien_depatamento_id' => strClean($_POST['departamento']),
                    'grupo_id' => strClean($_POST['grupo']),
                    'subgrupo_id' => strClean($_POST['subgrupo']),
                    'seccion_id' => strClean($_POST['seccion']),
                    'descripcion_bien' => strtoupper(strClean($_POST['descripcion'])),
                    'fecha_adquisicion' => strClean($_POST['fecha_adquisicion']),
                    'status_bien' => strtoupper(strClean($_POST['status_bien'])),
                    'user_id' => $_SESSION['idUser']
                ];

                if ($id_bien_taller == 0) {
                    $request_bien = $this->model->insertBienTaller($data);
                    $option = 1;
                } else {
                    $data['id_bien_taller'] = $id_bien_taller;
                    $request_bien = $this->model->updateBienTaller($data);
                    $option = 2;
                }

                if ($request_bien > 0) {
                    if ($option == 1) {
                        $arrResponse = ['success' => true, 'msg' => 'Bien de taller guardado correctamente.'];
                    } else {
                        $arrResponse = ['success' => true, 'msg' => 'Bien de taller actualizado correctamente.'];
                    }
                } else {
                    $arrResponse = ['success' => false, 'message' => 'No es posible almacenar los datos.'];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delBienTaller() {
        if ($_POST) {
            try {
                $id_bien_taller = intval($_POST['id_bien_taller']);
                $requestDelete = $this->model->deleteBienTaller($id_bien_taller);
                if ($requestDelete) {
                    $arrResponse = ['success' => true, 'message' => 'Se ha eliminado el bien de taller', 'status' => true];
                } else {
                    $arrResponse = ['success' => false, 'message' => 'Error al eliminar el bien de taller.', 'status' => false];
                }
            } catch (Exception $e) {
                $arrResponse = ['success' => false, 'message' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function generarPdfGeneral() {
        try {
            $idInstitucion = $this->resolverIdInstitucion();
            $nombreInstitucion = $this->obtenerNombreInstitucion($idInstitucion);

            $bienes = $this->model->getAllBienesTallerOrdenados();
            if (empty($bienes)) {
                $arrResponse = ['success' => false, 'message' => 'No hay bienes de taller para mostrar en el reporte.'];
            } else {
                $bienesAgrupados = [];
                foreach ($bienes as $bien) {
                    $departamento = $bien['departamento_bien'] ?? 'Sin Departamento';
                    $bienesAgrupados[$departamento][] = $bien;
                }
                $arrResponse = [
                    'success' => true,
                    'data' => $bienesAgrupados,
                    'nombreInstitucion' => $nombreInstitucion
                ];
            }
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener los datos para el PDF: ' . $e->getMessage()];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function generarPdfPorDepartamento() {
        try {
            $idDepto = strClean($_POST['idDepartamento'] ?? '');
            if (empty($idDepto)) {
                throw new Exception("ID de departamento no válido.");
            }

            $idInstitucion = $this->resolverIdInstitucion();
            $nombreInstitucion = $this->obtenerNombreInstitucion($idInstitucion);

            $bienes = $this->model->selectBienesTallerPorDepartamento($idDepto);
            $deptoInfo = $this->model->getDepartamento($idDepto);

            if (empty($bienes)) {
                $arrResponse = ['success' => false, 'message' => 'No se encontraron bienes de taller para este departamento.'];
            } else {
                $bienesAgrupados[$deptoInfo['departamento_bien']] = $bienes;
                $arrResponse = [
                    'success' => true,
                    'data' => $bienesAgrupados,
                    'departamento' => $deptoInfo['departamento_bien'],
                    'nombreInstitucion' => $nombreInstitucion
                ];
            }
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener los datos: ' . $e->getMessage()];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function generarPdfPorBusqueda() {
        try {
            $termino = strClean($_POST['terminoBusqueda'] ?? '');
            if (empty($termino)) {
                throw new Exception("Debe proporcionar un término de búsqueda.");
            }

            $idInstitucion = $this->resolverIdInstitucion();
            $nombreInstitucion = $this->obtenerNombreInstitucion($idInstitucion);

            $bienes = $this->model->selectBienesTallerPorBusqueda($termino);

            if (empty($bienes)) {
                $arrResponse = ['success' => false, 'message' => 'No se encontraron bienes de taller que coincidan con "' . htmlspecialchars($termino) . '".'];
            } else {
                $bienesAgrupados['Resultados de la Búsqueda'] = $bienes;
                $arrResponse = [
                    'success' => true,
                    'data' => $bienesAgrupados,
                    'termino' => $termino,
                    'nombreInstitucion' => $nombreInstitucion
                ];
            }
        } catch (Exception $e) {
            $arrResponse = ['success' => false, 'message' => 'Error al obtener los datos: ' . $e->getMessage()];
        }
        header('Content-Type: application/json');
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}
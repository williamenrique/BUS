<?php
header('Access-Control-Allow-Origin: *');

/**
 * Controlador del módulo de Compras (multi-institución).
 * URLs:
 *   - compras/costos          → SSLMTY (institución 1)
 *   - compras/costosTaller    → Taller (institución 2)
 */
class Compras extends Controllers{

    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        parent::__construct();
        $this->comprasModel = new ComprasModel();
    }

    function getActiveSession(){
        $request = $this->model->getActiveSession($_SESSION['idUser']);
    }

    public function validateSession(): bool {
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
        error_log("Error de BD en Compras: " . $error);
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

    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Resuelve el ID de institución desde el parámetro de URL.
     */
    private function resolverInstitucion($institucion = 'actual') {
        return ($institucion === 'taller') ? 2 : 1;
    }

    /**
     * Lee id_institucion desde GET/POST con fallback a 1.
     */
    private function obtenerInstitucionDeRequest(): int {
        if (isset($_GET['id_institucion'])) return intval($_GET['id_institucion']);
        if (isset($_POST['id_institucion'])) return intval($_POST['id_institucion']);
        return 1;
    }

    /**************************************************/
    /********* VISTAS *********************************/
    /**************************************************/

    /**
     * Vista principal de Compras.
     * URL: compras/costos
     */
    public function costos($institucion = 'actual'){
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $idInstitucion = $this->resolverInstitucion($institucion);
        $nombreInstitucion = $this->comprasModel->getNombreInstitucion($idInstitucion);
        $this->comprasModel->setInstitucion($idInstitucion);

        $data = [
            'page_tag' => "Compras - " . $nombreInstitucion,
            'page_title' => "Compras - " . obtenerIniciales($nombreInstitucion),
            'page_name' => "compras",
            'page_link' => ($idInstitucion === 2) ? "costos_taller" : "costos",
            'page_functions' => "function.compras.js",
            'id_institucion' => $idInstitucion,
            'nombre_institucion' => obtenerIniciales($nombreInstitucion),
            'es_taller' => ($idInstitucion === 2)
        ];
        $this->views->getViews($this, "costos", $data);
    }

    /**
     * Wrapper Taller.
     * URL: compras/costosTaller
     */
    public function costosTaller() {
        $this->costos('taller');
    }

    /**************************************************/
    /********* API: FLOTA *****************************/
    /**************************************************/

    /**
     * Lista de unidades activas de la institución activa.
     */
    public function getFlota() {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->comprasModel->setInstitucion($idInstitucion);

            $arrData = $this->comprasModel->selectFlotaActiva();
            if (empty($arrData)) {
                $arrResponse = ['status' => false, 'msg' => 'No se encontraron unidades.'];
            } else {
                $arrResponse = ['status' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    /**************************************************/
    /********* API: PENDIENTES ************************/
    /**************************************************/

    public function getComprasPendientes() {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->comprasModel->setInstitucion($idInstitucion);

            $arrData = $this->comprasModel->selectComprasPendientes();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['acciones'] = '<button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-xs" onclick="fntAsignarCosto(this)" data-iddespacho="' . $arrData[$i]['id_despacho'] . '">
                                                <i class="fas fa-dollar-sign"></i> Asignar Costo
                                            </button>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getArticulosPorDespacho(int $id) {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->comprasModel->setInstitucion($idInstitucion);

            $idDespacho = intval($id);
            if ($idDespacho > 0) {
                $infoDespacho = $this->comprasModel->getInfoDespacho($idDespacho);
                $articulos = $this->comprasModel->selectArticulosPorDespacho($idDespacho);

                if (empty($infoDespacho) || empty($articulos)) {
                    $arrResponse = ['status' => false, 'msg' => 'Datos no encontrados.'];
                } else {
                    $arrResponse = ['status' => true, 'data' => ['info' => $infoDespacho, 'articulos' => $articulos]];
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function setCosto() {
        if ($_POST) {
            try {
                $idInstitucion = intval($_POST['id_institucion'] ?? 1);
                $this->comprasModel->setInstitucion($idInstitucion);

                $articulos = json_decode($_POST['articulos'], true);
                $tasa = floatval($_POST['tasaDia']);
                $idUsuario = $_SESSION['idUser'];

                if (empty($articulos) || $tasa <= 0) {
                    throw new Exception("Datos incompletos o tasa inválida.");
                }

                $request = $this->comprasModel->insertCostosPorDespacho($articulos, $tasa, $idUsuario);

                $arrResponse = ($request > 0)
                    ? ['status' => true, 'msg' => 'Se asignó el costo a ' . $request . ' artículos.']
                    : ['status' => false, 'msg' => 'No fue posible asignar el costo.'];
            } catch (Exception $e) {
                $arrResponse = ['status' => false, 'msg' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**************************************************/
    /********* API: COSTEADAS *************************/
    /**************************************************/

    public function getComprasCosteadas() {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->comprasModel->setInstitucion($idInstitucion);

            $draw = intval($_POST['draw'] ?? 0);
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $searchValue = $_POST['search']['value'] ?? '';
            $fechaInicio = !empty($_POST['fechaInicio']) ? $_POST['fechaInicio'] : null;
            $fechaFin = !empty($_POST['fechaFin']) ? $_POST['fechaFin'] : null;

            $comprasData = $this->comprasModel->selectComprasCosteadas($start, $length, $searchValue, $fechaInicio, $fechaFin);
            $arrData = $comprasData['data'];

            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['acciones'] = '<button class="btn btn-info btn-sm" onclick="fntVerDetalleCosto(' . $arrData[$i]['id_despacho'] . ')">
                                                <i class="fas fa-eye"></i> Ver Detalle
                                            </button>';
            }

            $arrResponse = [
                "draw" => $draw,
                "recordsTotal" => $comprasData['total'],
                "recordsFiltered" => $comprasData['total_filtered'],
                "data" => $arrData
            ];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    public function getDetalleCosteado(int $id) {
        try {
            $idInstitucion = $this->obtenerInstitucionDeRequest();
            $this->comprasModel->setInstitucion($idInstitucion);

            $idDespacho = intval($id);
            if ($idDespacho > 0) {
                $infoDespacho = $this->comprasModel->getInfoDespacho($idDespacho);
                $articulos = $this->comprasModel->selectDetalleCosteado($idDespacho);

                if (empty($infoDespacho) || empty($articulos)) {
                    $arrResponse = ['status' => false, 'msg' => 'No se encontraron detalles para este despacho.'];
                } else {
                    $arrResponse = ['status' => true, 'data' => ['info' => $infoDespacho, 'articulos' => $articulos]];
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            } else {
                $arrResponse = ['status' => false, 'msg' => 'ID de despacho no válido.'];
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    /**************************************************/
    /********* API: CONTEO Y REPORTES *****************/
    /**************************************************/

    public function getConteoOrdenesCosteadas() {
        if ($_POST) {
            try {
                $idInstitucion = intval($_POST['id_institucion'] ?? 1);
                $this->comprasModel->setInstitucion($idInstitucion);

                $idFlota = intval($_POST['idFlota']);
                $fechaInicio = !empty($_POST['fechaInicio']) ? $_POST['fechaInicio'] : null;
                $fechaFin = !empty($_POST['fechaFin']) ? $_POST['fechaFin'] : null;

                if (empty($idFlota)) {
                    throw new Exception("Debe seleccionar una unidad.");
                }

                $total = $this->comprasModel->countOrdenesCosteadas($idFlota, $fechaInicio, $fechaFin);

                $arrResponse = ['status' => true, 'total' => $total];
            } catch (Exception $e) {
                $arrResponse = ['status' => false, 'msg' => 'Error: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function generarReporteCompras() {
        if ($_POST) {
            try {
                $idInstitucion = intval($_POST['id_institucion'] ?? 1);
                $this->comprasModel->setInstitucion($idInstitucion);

                $idFlota = intval($_POST['listUnidadReporte']);
                $fechaInicio = $_POST['fechaInicio'];
                $fechaFin = $_POST['fechaFin'];

                if (empty($idFlota) || empty($fechaInicio) || empty($fechaFin)) {
                    throw new Exception("Todos los campos son obligatorios.");
                }

                $datosReporte = $this->comprasModel->selectReporteCosteado($idFlota, $fechaInicio, $fechaFin);

                if (empty($datosReporte)) {
                    $arrResponse = ['status' => false, 'msg' => 'No se encontraron datos con los filtros seleccionados.'];
                } else {
                    $despachosAgrupados = [];
                    foreach ($datosReporte as $item) {
                        $idDespacho = $item['id_despacho'];
                        if (!isset($despachosAgrupados[$idDespacho])) {
                            $despachosAgrupados[$idDespacho] = [
                                'id_despacho' => $idDespacho,
                                'numero_orden' => $item['numero_orden'],
                                'fecha_despacho' => $item['fecha_despacho'],
                                'tasa_dia' => $item['tasa_dia'],
                                'unidad' => $item['id_unidad'] . ' - ' . $item['modelo_unidad'],
                                'articulos' => [],
                                'total_divisa' => 0,
                                'total_bs' => 0,
                            ];
                        }
                        $despachosAgrupados[$idDespacho]['articulos'][] = $item;
                        $despachosAgrupados[$idDespacho]['total_divisa'] += $item['monto_divisa'];
                        $despachosAgrupados[$idDespacho]['total_bs'] += $item['monto_bs'];
                    }

                    $arrResponse = ['status' => true, 'data' => array_values($despachosAgrupados)];
                }
            } catch (Exception $e) {
                $arrResponse = ['status' => false, 'msg' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function generarReporteCosteadas() {
        if ($_POST) {
            try {
                $idInstitucion = intval($_POST['id_institucion'] ?? 1);
                $this->comprasModel->setInstitucion($idInstitucion);

                $fechaInicio = !empty($_POST['fechaInicio']) ? $_POST['fechaInicio'] : null;
                $fechaFin = !empty($_POST['fechaFin']) ? $_POST['fechaFin'] : null;
                $searchValue = $_POST['searchValue'] ?? '';

                $datosReporte = $this->comprasModel->selectReporteCosteadasFiltrado($searchValue, $fechaInicio, $fechaFin);

                if (empty($datosReporte)) {
                    $arrResponse = ['status' => false, 'msg' => 'No se encontraron datos con los filtros aplicados.'];
                } else {
                    $unidadesAgrupadas = [];
                    foreach ($datosReporte as $item) {
                        $idFlota = $item['id_flota'];
                        $idDespacho = $item['id_despacho'];

                        if (!isset($unidadesAgrupadas[$idFlota])) {
                            $unidadesAgrupadas[$idFlota] = [
                                'id_flota' => $idFlota,
                                'nombre_unidad' => $item['id_unidad'] . ' - ' . $item['modelo_unidad'],
                                'despachos' => [],
                                'total_divisa_unidad' => 0,
                                'total_bs_unidad' => 0,
                            ];
                        }

                        if (!isset($unidadesAgrupadas[$idFlota]['despachos'][$idDespacho])) {
                            $unidadesAgrupadas[$idFlota]['despachos'][$idDespacho] = [
                                'id_despacho' => $idDespacho,
                                'numero_orden' => $item['numero_orden'],
                                'fecha_despacho' => $item['fecha_despacho'],
                                'tasa_dia' => $item['tasa_dia'],
                                'articulos' => [],
                                'total_divisa_despacho' => 0,
                                'total_bs_despacho' => 0,
                            ];
                        }

                        $unidadesAgrupadas[$idFlota]['despachos'][$idDespacho]['articulos'][] = $item;
                        $unidadesAgrupadas[$idFlota]['despachos'][$idDespacho]['total_divisa_despacho'] += $item['monto_divisa'];
                        $unidadesAgrupadas[$idFlota]['despachos'][$idDespacho]['total_bs_despacho'] += $item['monto_bs'];
                        $unidadesAgrupadas[$idFlota]['total_divisa_unidad'] += $item['monto_divisa'];
                        $unidadesAgrupadas[$idFlota]['total_bs_unidad'] += $item['monto_bs'];
                    }

                    $arrResponse = ['status' => true, 'data' => array_values($unidadesAgrupadas)];
                }
            } catch (Exception $e) {
                $arrResponse = ['status' => false, 'msg' => 'Error en el proceso: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function anularCosto(int $id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $arrResponse = ['status' => false, 'msg' => 'Método no permitido.'];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $idInstitucion = intval($_POST['id_institucion'] ?? 1);
            $this->comprasModel->setInstitucion($idInstitucion);

            $idDespacho = intval($id);
            if ($idDespacho <= 0) {
                throw new Exception("ID de despacho no válido.");
            }

            $request = $this->comprasModel->anularCostoPorDespacho($idDespacho);
            $arrResponse = ($request)
                ? ['status' => true, 'msg' => 'El costeo ha sido anulado y la orden ha vuelto a pendientes.']
                : ['status' => false, 'msg' => 'No fue posible anular el costeo.'];
        } catch (Exception $e) {
            $arrResponse = ['status' => false, 'msg' => 'Error en el proceso: ' . $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}
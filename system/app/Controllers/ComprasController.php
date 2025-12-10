<?php
header('Access-Control-Allow-Origin: *');

/**
 * Controlador para el módulo de Compras.
 * Gestiona la visualización de compras pendientes y costeadas, la asignación de costos,
 * la anulación de costeos y la generación de reportes relacionados.
 */
class Compras extends Controllers{
    private $db; //para inicializar la base de datos

    public function __construct(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Validar sesión de manera más robusta
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        //invocar para que se ejecute el metodo de la herencia
        parent::__construct();

    }
    /*manejo de sesiones activas*/

	/**
     * Obtiene la sesión activa del usuario actual.
     */
	function getActiveSession(){
		$reuest = $this->model->getActiveSession($_SESSION['idUser']);
	}

    /**
     * Valida si la sesión del usuario es activa y correcta en la base de datos.
     * @return bool True si la sesión es válida, false en caso contrario.
     */
    public function validateSession(): bool {
        // Verificar si la sesión está iniciada y es válida
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
    /*fin manejo de sesiones activas*/

    /**inicio de manejo de errores en cada controlador debe estar */
	/**
     * Maneja y registra los errores de base de datos.
     * @param string $error El mensaje de error a registrar.
     */
	private function handleDatabaseError($error) {
        // Log del error
        error_log("Error de BD en controlador User: " . $error);
        // Puedes elegir cómo manejar el error:
        // 1. Redirigir a una página de error
        // 2. Mostrar un mensaje JSON (para APIs)
        // 3. Guardar en variable para mostrar en vista
        // Para métodos que devuelven JSON:
        if ($this->isAja|xRequest()) {
            $arrResponse = [
                'success' => false,
                'message' => 'Error de conexión a la base de datos',
                'error' => $error
            ];
            header('Content-Type: application/json');
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        } else {
            // Para vistas HTML, podrías guardar el error para mostrarlo
            $_SESSION['error_message'] = "Error de base de datos: " . $error;
        }
    }

    /**
     * Verifica si la solicitud actual es una petición AJAX.
     * @return bool True si es AJAX, false si no.
     */
    private function isAjaxRequest(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
	/**fin de manejo de errores en cada controlador debe estar*/

    /**
     * Carga la vista principal del módulo de Compras.
     */
    public function costos(){
        // Validar nuevamente la sesión antes de mostrar el home
        if (!$this->validateSession()) {
            header("Location:".base_url().'login');
            exit();
        }
        $data = [
            'page_tag' => "Pagina principal",
            'page_title' => "Pagina Principal",
            'page_name' => "compras",
            'page_link' => "costos",
            'page_functions' => "function.compras.js"
        ];
        $this->views->getViews($this, "costos", $data);
    }

    /**
     * Obtiene la lista de unidades activas de la flota para poblar selects.
     */
    public function getFlota() {
        try {
            $arrData = $this->model->selectFlotaActiva();
            if (empty($arrData)) {
                $arrResponse = ['status' => false, 'msg' => 'No se encontraron unidades.'];
            } else {
                $arrResponse = ['status' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) { $this->handleDatabaseError($e->getMessage()); }
        die();
    }

    /**
     * Obtiene la lista de despachos pendientes de costeo para la DataTable.
     */
    public function getComprasPendientes() {
        try {
            $arrData = $this->model->selectComprasPendientes();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['acciones'] = '<button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-xs" onclick="fntAsignarCosto(this)" data-iddespacho="' . $arrData[$i]['id_despacho'] . '">
                                                <i class="fas fa-dollar-sign"></i> Asignar Costo
                                            </button>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            // Manejo de errores
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    /**
     * Obtiene los artículos de un despacho específico que están pendientes de costeo.
     * @param int $id El ID del despacho.
     */
    public function getArticulosPorDespacho(int $id) {
        try {
            $idDespacho = intval($id);
            if ($idDespacho > 0) {
                $infoDespacho = $this->model->getInfoDespacho($idDespacho);
                $articulos = $this->model->selectArticulosPorDespacho($idDespacho);

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

    /**
     * Guarda los costos asignados a los artículos de un despacho.
     */
    public function setCosto() {
        if ($_POST) {
            try {
                // Los artículos vienen como un string JSON, hay que decodificarlo
                $articulos = json_decode($_POST['articulos'], true);
                $tasa = floatval($_POST['tasaDia']);
                $idUsuario = $_SESSION['idUser'];

                if (empty($articulos) || $tasa <= 0) {
                    throw new Exception("Datos incompletos o tasa inválida.");
                }

                $request = $this->model->insertCostosPorDespacho($articulos, $tasa, $idUsuario);

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

    /**
     * Endpoint para ejecutar la migración de órdenes antiguas a la tabla de compras pendientes.
     * Acceder a esta URL una sola vez: /Compras/migrarOrdenesAntiguas
     */
    public function migrarOrdenesAntiguas() {
        try {
            $registros_migrados = $this->model->migrarOrdenesAntiguas();
            if ($registros_migrados > 0) {
                $message = "¡Migración completada! Se han añadido " . $registros_migrados . " artículos pendientes a la lista de compras.";
            } else {
                $message = "No se encontraron nuevas órdenes para migrar. El sistema ya está actualizado.";
            }
            // Mostramos un mensaje simple en pantalla
            echo "<h1>Resultado de la Migración</h1>";
            echo "<p style='font-size: 18px; color: green;'>" . $message . "</p>";
            echo "<a href='" . base_url() . "compras/costos'>Volver al módulo de Compras</a>";
        } catch (Exception $e) {
            echo "<h1>Error en la Migración</h1>";
            echo "<p style='font-size: 18px; color: red;'>Ha ocurrido un error: " . $e->getMessage() . "</p>";
        }
        die();
    }

    /**
     * Obtiene la lista de compras ya costeadas para la DataTable con paginación y filtros.
     */
    public function getComprasCosteadas() {
        try {
            // Parámetros de DataTables y fechas de filtro
            $draw = intval($_POST['draw'] ?? 0);
            $start = intval($_POST['start'] ?? 0);
            $length = intval($_POST['length'] ?? 10);
            $searchValue = $_POST['search']['value'] ?? '';
            $fechaInicio = !empty($_POST['fechaInicio']) ? $_POST['fechaInicio'] : null;
            $fechaFin = !empty($_POST['fechaFin']) ? $_POST['fechaFin'] : null;

            // Obtener datos desde el modelo
            $comprasData = $this->model->selectComprasCosteadas($start, $length, $searchValue, $fechaInicio, $fechaFin);
            $arrData = $comprasData['data'];

            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['acciones'] = '<button class="btn btn-info btn-sm" onclick="fntVerDetalleCosto(' . $arrData[$i]['id_despacho'] . ')">
                                                <i class="fas fa-eye"></i> Ver Detalle
                                            </button>';
            }

            $arrResponse = [
                "draw" => $draw, "recordsTotal" => $comprasData['total'], "recordsFiltered" => $comprasData['total_filtered'], "data" => $arrData
            ];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            $this->handleDatabaseError($e->getMessage());
        }
        die();
    }

    /**
     * Obtiene el detalle de un despacho ya costeado para mostrarlo en un modal.
     * @param int $id El ID del despacho.
     */
    public function getDetalleCosteado(int $id) {
        try {
            $idDespacho = intval($id);
            if ($idDespacho > 0) {
                $infoDespacho = $this->model->getInfoDespacho($idDespacho);
                $articulos = $this->model->selectDetalleCosteado($idDespacho);

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

    /**
     * Obtiene el conteo de órdenes costeadas para una unidad y un rango de fechas específicos.
     */
    public function getConteoOrdenesCosteadas() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['idFlota']);
                $fechaInicio = !empty($_POST['fechaInicio']) ? $_POST['fechaInicio'] : null;
                $fechaFin = !empty($_POST['fechaFin']) ? $_POST['fechaFin'] : null;

                if (empty($idFlota)) {
                    throw new Exception("Debe seleccionar una unidad.");
                }

                $total = $this->model->countOrdenesCosteadas($idFlota, $fechaInicio, $fechaFin);

                $arrResponse = ['status' => true, 'total' => $total];

            } catch (Exception $e) {
                $arrResponse = ['status' => false, 'msg' => 'Error: ' . $e->getMessage()];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    /**
     * Prepara los datos para generar el reporte en PDF de compras por unidad.
     */
    public function generarReporteCompras() {
        if ($_POST) {
            try {
                $idFlota = intval($_POST['listUnidadReporte']);
                $fechaInicio = $_POST['fechaInicio'];
                $fechaFin = $_POST['fechaFin'];

                if (empty($idFlota) || empty($fechaInicio) || empty($fechaFin)) {
                    throw new Exception("Todos los campos son obligatorios.");
                }

                $datosReporte = $this->model->selectReporteCosteado($idFlota, $fechaInicio, $fechaFin);

                if (empty($datosReporte)) {
                    $arrResponse = ['status' => false, 'msg' => 'No se encontraron datos con los filtros seleccionados.'];
                } else {
                    // Agrupar los datos por despacho para el PDF
                    $despachosAgrupados = [];
                    foreach ($datosReporte as $item) {
                        $idDespacho = $item['id_despacho'];
                        if (!isset($despachosAgrupados[$idDespacho])) {
                            $despachosAgrupados[$idDespacho] = [
                                'id_despacho' => $idDespacho,
                                'fecha_despacho' => $item['fecha_despacho'],
                                'tasa_dia' => $item['tasa_dia'], // La tasa es la misma para todos los items del mismo despacho
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

    /**
     * Prepara los datos para generar el reporte en PDF de las compras costeadas filtradas.
     */
    public function generarReporteCosteadas() {
        if ($_POST) {
            try {
                $fechaInicio = !empty($_POST['fechaInicio']) ? $_POST['fechaInicio'] : null;
                $fechaFin = !empty($_POST['fechaFin']) ? $_POST['fechaFin'] : null;
                $searchValue = $_POST['searchValue'] ?? '';

                $datosReporte = $this->model->selectReporteCosteadasFiltrado($searchValue, $fechaInicio, $fechaFin);

                if (empty($datosReporte)) {
                    $arrResponse = ['status' => false, 'msg' => 'No se encontraron datos con los filtros aplicados.'];
                } else {
                    // Agrupar los datos primero por unidad, luego por despacho
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
                                'fecha_despacho' => $item['fecha_despacho'],
                                'tasa_dia' => $item['tasa_dia'], // La tasa es por artículo, pero la guardamos aquí para referencia si es consistente por despacho
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




    /**
     * Anula el costeo de un despacho, devolviéndolo al estado 'Pendiente'.
     * @param int $id El ID del despacho a anular.
     */
    public function anularCosto(int $id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $arrResponse = ['status' => false, 'msg' => 'Método no permitido.'];
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $idDespacho = intval($id);
            if ($idDespacho <= 0) {
                throw new Exception("ID de despacho no válido.");
            }

            $request = $this->model->anularCostoPorDespacho($idDespacho);
            $arrResponse = ($request)
                ? ['status' => true, 'msg' => 'El costeo ha sido anulado y la orden ha vuelto a pendientes.']
                : ['status' => false, 'msg' => 'No fue posible anular el costeo.'];
        } catch (Exception $e) {
            $arrResponse = ['status' => false, 'msg' => 'Error en el proceso: ' . $e->getMessage()];
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }

    /**
     * Endpoint de DEPURACIÓN para ver qué encuentra la consulta de migración.
     * Acceder a: /Compras/debugMigracion
     */
    public function debugMigracion() {
        echo "<pre>";
        echo "<h1>Depuración de la Migración de Órdenes</h1>";
        
        $resultados = $this->model->debugMigracionSelect();

        if (empty($resultados)) {
            echo "<p>La consulta no encontró ningún artículo en la tabla 'table_alm_relacion_despacho'. Esto podría significar que la tabla está vacía.</p>";
        } else {
            echo "<p>Se encontraron " . count($resultados) . " artículos en total en las órdenes de despacho. A continuación se muestra una muestra de los datos:</p>";
            print_r(array_slice($resultados, 0, 20)); // Muestra los primeros 20 resultados
        }
        echo "</pre>";
        die();
    }
}
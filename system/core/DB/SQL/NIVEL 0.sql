
--
-- Volcado de datos para la tabla `table_alm_enlace_producto`
--

INSERT INTO `table_alm_enlace_producto` (`id_enlace_producto`, `enlace_producto`) VALUES
(1, 'ZK6896HGA'),
(2, 'ZK6752D'),
(3, 'ZK6118HGA'),
(4, 'ZK6729D2'),
(5, 'ZK6852HG'),
(6, '3800T-444E'),
(7, '3600'),
(8, 'ZK6860HGA'),
(9, 'ENCAVA (ENT610)'),
(10, 'USO GENERAL'),
(11, 'DESCONTINUADO'),
(12, 'LUBRICANTES'),
(13, 'NEUMATICOS'),
(14, 'BATERIAS'),
(15, 'BLUE BIRD'),
(16, 'TOYOTA'),
(17, 'CHERY'),
(18, 'PULLMAN'),
(21, 'GRUA'),
(22, 'CAVA JAG C/3');
--
-- Volcado de datos para la tabla `table_alm_ubicacion`
--

INSERT INTO `table_alm_ubicacion` (`id_ubicacion`, `ubicacion`) VALUES
(1, 'ZK6896HGA'),
(2, 'ZK6752D'),
(3, 'ZK6118HGA'),
(4, 'ZK6729D2'),
(5, 'ZK6852HG'),
(6, '3800T-444E'),
(7, '3600'),
(8, 'ZK6860HGA'),
(9, 'ENT610'),
(10, 'ESTANTE A'),
(11, 'ESTANTE B'),
(12, 'ESTANTE C'),
(13, 'ESTANTE D'),
(14, 'ESTANTE E'),
(15, 'ESTANTE F'),
(16, 'ESTANTE G'),
(17, 'ESTANTE H'),
(18, 'MESANINA'),
(19, 'ALMACEN B'),
(20, 'ALMACEN PRINCIPAL'),
(21, 'BLUE BIRD');
--
-- Volcado de datos para la tabla `table_bienes_departamentos`
--

INSERT INTO `table_bienes_departamentos` (`depatamento_bien_id`, `departamento_bien`, `departamento_status`) VALUES
('01', 'DIRECCION GENERAL', 1),
('02', 'DIRECCION DE ADMINISTRACION', 1),
('03', 'CONSULTORIA JURIDICA', 1),
('04', 'COORDINACION PLANIFICACION Y PRESUPUESTO', 1),
('05', 'DIRECCION DE OPERACION', 1),
('06', 'COORDINACION DE COMPRAS', 1),
('07', 'COORDINACION DE TESORERIA', 1),
('08', 'COORDINACION DE CONTABILIDAD', 1),
('09', 'COORDINANCION DE RRHH', 1),
('10', 'COORDINANCION DE BIENES', 1),
('100', 'RESGUARDO', 1),
('11', 'COORDINACION D EINFORMATICA', 1),
('12', 'COORDINACION D ELOGISTICA', 1),
('14', 'COORDINACION DE ALMACEN', 1),
('18', 'N/A', 1),
('19', 'C.C.O', 1),
('20', 'SALON DE CLASE', 1);

--
-- Volcado de datos para la tabla `table_bienes_grupo`
--

INSERT INTO `table_bienes_grupo` (`id_grupo`, `grupo`, `grupo_status`) VALUES
('02', 'BIENES MUEBLE', 1);
--
-- Volcado de datos para la tabla `table_bienes_seccion`
--

INSERT INTO `table_bienes_seccion` (`seccion_id`, `seccion`, `seccion_status`) VALUES
('013', 'SECCION 013', 1),
('034', 'SECCION 034', 1),
('027', 'SECCION 027', 1),
('022', 'SECCION 022', 1),
('102', 'SECCION 102', 1),
('006', 'SECCION 006', 1),
('042', 'SECCION 042', 1),
('085', 'SECCION 085', 1),
('073', 'SECCION 073', 1),
('012', 'SECCION 012', 1),
('028', 'SECCION 028', 1),
('032', 'SECCION 032', 1),
('017', 'SECCION 017', 1),
('001', 'SECCION 001', 1),
('020', 'SECCION 020', 1),
('105', 'SECCION 105', 1),
('100', 'SECCION 100', 1),
('041', 'SECCION 041', 1),
('110', 'SECCION 110', 1),
('004', 'SECCION 004', 1),
('029', 'SECCION 029', 1),
('138', 'SECCION 138', 1),
('046', 'SECCION 046', 1),
('104', 'SECCION 104', 1),
('066', 'SECCION 066', 1),
('031', 'SECCION 031', 1),
('N/A', 'N/A', 1),
('102', 'SECCION 102', 1),
('105', 'SECCION 105', 1),
('124', 'SECCION 124', 1),
('024', 'SECCION 024', 1),
('083', 'SECCION 083', 1),
('052', 'SECCION 052', 1),
('124', 'SECCION 124', 1),
('010', 'SECCION 010', 1),
('019', 'SECCION 019', 1),
('040', 'SECCION 040', 1),
('026', 'SECCION 026', 1),
('007', 'SECCION 007', 1),
('116', 'SECCION 116', 1),
('009', 'SECCION 009', 1),
('179', 'SECCION 179', 1),
('172', 'SECCION 172', 1),
('138', 'SECCION 138', 1),
('037', 'SECCION 037', 1),
('089', 'SECCION 089', 1),
('184', 'SECCION 184', 1),
('035', 'SECCION 035', 1),
('005', 'SECCION 005', 1),
('036', 'SECCION 036', 1),
('108', 'SECCION 108', 1),
('101', 'SECCION 101', 1),
('038', 'SECCION 038', 1),
('053', 'SECCION 053', 1),
('061', 'SECCION 061', 1),
('039', 'SECCION 039', 1),
('141', 'SECCION 141', 1),
('32', 'SECCION 32', 1),
('110', 'SECCION 110', 1),
('05', 'SECCION 05', 1),
('09', 'SECCION 09', 1);
--
-- Volcado de datos para la tabla `table_bienes_subgrupo`
--

INSERT INTO `table_bienes_subgrupo` (`subgrupo_id`, `subgrupo`, `subgrupo_descripcion`, `subgrupo_status`) VALUES
('01', 'MAQUINAS MUEBLES', 'MAQUINAS MUEBLES Y DEMAS EQUIPOS DE OFICINA', 1),
('02', 'MOBILIARIO Y ENSERES', 'MOBILIARIO Y ENSERES DE ALOJAMIENTO', 1),
('03', 'MAQUINARIA', 'MAQUINARIA Y DEMAS EQUIPOS DE CONSTRUCCION, CAMPO, INDUSTRIA Y TALLER', 1),
('03-1', 'EQUIPO DE TALLER', 'EQUIPO DE TALLER Y HERRAMIENTAS DE USO GENERAL', 1),
('03-2', 'MAQUINARIA Y EQUIPO', 'MAQUINARIA Y EQUIPO DE CONSTRUCCION Y CONSERVACION', 1),
('03-3', 'MAQUINARIA Y EQUIPO MANT', 'MAQUINARIA Y EQUIPO PARA MANTENIMIENTO AUTOMOTORES', 1),
('04', 'EQUIPO DE TRANSPORTE', 'EQUIPO DE TRANSPORTE', 1),
('04-1', 'VEHICULOS AUTOMOTORES', 'VEHICULOS AUTOMOTORES TERRESTRES', 1),
('04-4', 'EQUIPO AUXILIAR', 'EQUIPO DE AUXILIAR DE TRANSPORTE', 1),
('05', 'EQUIPO DE TELECOMUNICACIONES', 'EQUIPOS DE TELECOMUNICACIONES', 1),
('07-1', 'SIN DESCRIPCION 7-1', NULL, 1),
('07-2', 'SIN DESCRIPCION 7-2', NULL, 1),
('12', 'SN DESCRIPCION 12', NULL, 1),
('13', 'SIN DESCRIPCION 13', NULL, 1);

--
-- Volcado de datos para la tabla `table_departamentos`
--

INSERT INTO `table_departamentos` (`departamento_id`, `departamento_nombre`, `departamento_descripcion`, `departamento_status`) VALUES
(1, 'Sistema', NULL, 1),
(2, 'Estacion', NULL, 1),
(3, 'Administracion', NULL, 1),
(4, 'Almacen', '', 1),
(5, 'Bienes', '', 1),
(6, 'Operaciones', '', 1),
(7, 'Compras', '', 1);
--
-- Volcado de datos para la tabla `table_es_estacion`
--

INSERT INTO `table_es_estacion` (`id_estacion`, `estacion`, `status_estacion`) VALUES
(1, 'E/S Tachira', 1),
(2, 'E/S Gran Parada', 1);

--
-- Volcado de datos para la tabla `table_es_tasa_dia`
--

INSERT INTO `table_es_tasa_dia` (`id_tasa_dia`, `tasa_dia`, `tasa_update`) VALUES
(1, '339.15', '2026-01-16 04:21:49');

--
-- Volcado de datos para la tabla `table_es_tipos_pago`
--

INSERT INTO `table_es_tipos_pago` (`id_tipo_pago`, `nombre`, `descripcion`, `status_tipo_pago`) VALUES
(1, 'Efectivo Divisa', 'Pago en billetes/monedas', 1),
(2, 'Efectivo Bolivares', 'Pago en Billetes/monedas', 1),
(3, 'Tarjeta Debito', 'Pago con tarjeta de débito', 1),
(4, 'Efectivo Euro', 'Pago en Billetes/monedas', 0),
(5, 'Tarjeta Crédito', 'Pago con tarjeta de crédito', 0);

--
-- Volcado de datos para la tabla `table_es_tipos_vehiculo`
--

INSERT INTO `table_es_tipos_vehiculo` (`id_tipo_vehiculo`, `nombre`, `descripcion`, `status_tipo_vehiculo`) VALUES
(1, 'Carro', 'Vehículo particular de pasajeros', 1),
(2, 'Moto', 'Vehículo de dos ruedas', 1),
(3, 'Camion', 'Vehículo pesado de carga', 1),
(4, 'Autobus', 'Vehículo de transporte público', 0);

--
-- Volcado de datos para la tabla `table_flota_marca`
--

INSERT INTO `table_flota_marca` (`id_marca`, `marca_unidad`) VALUES
(1, 'YUTONG'),
(2, 'BLUE BIRD'),
(3, 'FREITHLINE'),
(4, 'ENCAVA'),
(5, 'CHERY'),
(6, 'TOYOTA'),
(7, 'VARIOS'),
(8, 'JAG');

--
-- Volcado de datos para la tabla `table_flota_modelo`
--

INSERT INTO `table_flota_modelo` (`id_modelo`, `modelo_unidad`) VALUES
(1, 'ZK6896HGA'),
(2, 'ZK6752D'),
(3, 'ZK6118HGA'),
(4, 'ZK6729D2'),
(5, 'ZK6852HG'),
(6, '3800T-444E'),
(7, '3600'),
(8, 'ZK6860HGA'),
(9, 'ENT610'),
(10, 'GRUA YTZ5257TQZ40EN'),
(11, '3800T-466E'),
(12, 'LAND CRUSIER'),
(13, 'CHERY'),
(14, 'VARIOS'),
(15, 'JAG');
--
-- Volcado de datos para la tabla `table_men_menu`
--

INSERT INTO `table_men_menu` (`menu_id`, `menu_nombre`, `menu_icono`, `menu_link`, `menu_es_desplegable`, `menu_orden`, `menu_estado`) VALUES
(1, 'Menu', 'fas fa-list', NULL, 0, 1, 0),
(2, 'Almacen', 'fas fa-building', NULL, 1, 1, 1),
(3, 'Estacion', 'fas fa-gas-pump', 'estacion/registrar', 0, 2, 1),
(4, 'Bienes', 'fas fa-box', 'bienes', 0, 3, 1),
(5, 'Operaciones', 'fas fa-bus', NULL, 1, 4, 1),
(6, 'Data', 'fas fa-database', NULL, 1, 5, 1),
(7, 'Usuarios', 'fas fa-users', NULL, 1, 6, 1),
(8, 'Menu', 'fas fa-list', 'menu', 0, 3, 1),
(9, 'Compras', 'fas fa-shopping-cart', NULL, 1, 6, 1);
--
-- Volcado de datos para la tabla `table_per_cargo`
--

INSERT INTO `table_per_cargo` (`id_cargo`, `cargo`, `status_cargo`) VALUES
(1, 'DIRECTOR GENERAL', 1),
(2, 'COORDINADOR(A) DE TESORERIA', 1),
(3, 'COORDINADOR(A) DE PLANIFICACION Y PRESUPUESTO', 1),
(4, 'COORDINADOR(A) DE RUTA ESTUDIANTIL', 1),
(5, 'COORDINADOR DE LOGÍSTICA (E)', 1),
(6, 'COORDINADOR DE MANTENIMIENTO DE VEHÍCULOS (E)', 1),
(7, 'COORDINADOR(A) DE BIENES', 1),
(8, 'COORDINADOR DE INFORMÁTICA (E)', 1),
(9, 'COORDINADOR DE SERVICIOS GENERALES', 1),
(10, 'COORDINADORA DE CONTABILIDAD', 1),
(11, 'COORDINADOR(A) DE ALMACEN DE REPUESTOS', 1),
(12, 'COORDINADOR(A) DE RECURSOS HUMANOS', 1),
(13, 'COORDINADOR(A) DE COMPRAS', 1),
(14, 'DIRECTOR(A) DE ADMINISTRACION', 1),
(15, 'DIRECTOR(A) DE OPERACIONES', 1),
(16, 'ANALISTA DE RECURSOS HUMANOS', 1),
(17, 'ANALISTA FINANCIERO', 1),
(18, 'ANALISTA DE SISTEMA', 1),
(19, 'ANALISTA FINANCIERO', 1),
(20, 'ANALISTA DE INFORMÁTICA', 1),
(21, 'CONSULTOR(A) JURIDICO', 1),
(22, 'OBRERO', 1),
(23, 'OPERADOR DE RUTA', 1),
(24, 'OPERADOR DE GRUA', 1),
(25, 'OPERADOR INSTRUCTOR', 1),
(26, 'MECANICO I', 1),
(27, 'MECANICO AUTOMOTRIZ I', 1),
(28, 'MECANICO AUTOMOTRIZ III', 1),
(29, 'MECANICO DIESEL III', 1),
(30, 'RECAUDADOR', 1),
(31, 'AYUDANTE DE ALMACEN DE REPUESTOS', 1),
(32, 'AYUDANTE MECANICO', 1),
(33, 'DESPACHADOR DE COMBUSTIBLE', 1),
(34, 'VIGILANTE', 1),
(35, 'LATONERO', 1),
(36, 'PERSONAL DE AUTOLAVADO', 1),
(37, 'CHEQUEADOR(A)', 1),
(38, 'SUPERVISOR DE AREA', 1),
(39, 'ASISTENTE ADMINISTRATIVO', 1),
(40, 'ATENCION AL CIUDADANO', 1),
(41, 'RECEPCIONISTA', 1),
(42, 'AYUDANTE MECANICO', 1),
(43, 'CAUCHERO', 1);

--
-- Volcado de datos para la tabla `table_per_roles`
--

INSERT INTO `table_per_roles` (`rol_id`, `rol_nombre`, `rol_descripcion`, `rol_status`, `fecha_creacion`) VALUES
(1, 'Administrador', 'Acceso completo a todas las funciones del sistema', 1, '2025-09-01 20:41:58'),
(2, 'Encargado', 'Acceso a módulos de ventas y clientes', 1, '2025-09-01 20:41:58'),
(3, 'Empleado', 'Es el encargado de despachar articulos', 1, '2025-09-19 20:20:35'),
(4, 'Almacen encargado', 'Jefe de almacen', 0, '2025-09-19 20:21:19'),
(5, 'Bienes encargado', '', 0, '2025-09-25 16:00:18'),
(6, 'Operaciones Encargado', '', 0, '2025-10-06 20:52:59'),
(7, 'NL-16', 'Acceso completo a todas las funciones del sistema', 0, '2025-10-27 22:06:18');

--
-- Volcado de datos para la tabla `table_proveedor`
--

INSERT INTO `table_proveedor` (`id_proveedor`, `rif_proveedor`, `empresa_proveedor`, `responsable_proveedor`, `email_proveedor`, `tlf_proveedor`, `status_proveedor`) VALUES
(1, 'J200120070', 'SSLMTY', 'N/A', 'busyaracuy@gmail.com', 'n/a', 1);

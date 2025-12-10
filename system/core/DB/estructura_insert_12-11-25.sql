-- phpMyAdmin SQL Dump
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO,STRICT_TRANS_TABLES' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Estructura de tabla para la tabla `table_alm_enlace_producto`
DROP TABLE IF EXISTS `table_alm_enlace_producto`;
CREATE TABLE `table_alm_enlace_producto` (
  `id_enlace_producto` int(11) NOT NULL AUTO_INCREMENT,
  `enlace_producto` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_enlace_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_ubicacion`
DROP TABLE IF EXISTS `table_alm_ubicacion`;
CREATE TABLE `table_alm_ubicacion` (
  `id_ubicacion` int(11) NOT NULL AUTO_INCREMENT,
  `ubicacion` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_bienes_departamentos`
DROP TABLE IF EXISTS `table_bienes_departamentos`;
CREATE TABLE `table_bienes_departamentos` (
  `depatamento_bien_id` varchar(10) NOT NULL DEFAULT '',
  `departamento_bien` varchar(100) NOT NULL DEFAULT '0',
  `departamento_status` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`depatamento_bien_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_bienes_grupo`
DROP TABLE IF EXISTS `table_bienes_grupo`;
CREATE TABLE `table_bienes_grupo` (
  `id_grupo` varchar(50) NOT NULL DEFAULT '',
  `grupo` varchar(50) DEFAULT NULL,
  `grupo_status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_bienes_seccion`
DROP TABLE IF EXISTS `table_bienes_seccion`;
CREATE TABLE `table_bienes_seccion` (
  `seccion_id` varchar(50) DEFAULT NULL,
  `seccion` varchar(50) DEFAULT NULL,
  `seccion_status` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_bienes_subgrupo`
DROP TABLE IF EXISTS `table_bienes_subgrupo`;
CREATE TABLE `table_bienes_subgrupo` (
  `subgrupo_id` varchar(50) NOT NULL DEFAULT '',
  `subgrupo` varchar(50) DEFAULT NULL,
  `subgrupo_descripcion` text DEFAULT NULL,
  `subgrupo_status` int(11) DEFAULT NULL,
  PRIMARY KEY (`subgrupo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_departamentos`
DROP TABLE IF EXISTS `table_departamentos`;
CREATE TABLE `table_departamentos` (
  `departamento_id` int(11) NOT NULL AUTO_INCREMENT,
  `departamento_nombre` varchar(100) NOT NULL,
  `departamento_descripcion` text DEFAULT NULL,
  `departamento_status` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`departamento_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_es_cierre`
DROP TABLE IF EXISTS `table_es_cierre`;
CREATE TABLE `table_es_cierre` (
  `id_cierre` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) DEFAULT NULL,
  `fecha_cierre` varchar(10) DEFAULT NULL,
  `hora_cierre` varchar(10) DEFAULT NULL,
  `total_efectivob` varchar(10) DEFAULT NULL,
  `total_efectivod` int(11) DEFAULT NULL,
  `total_tarjeta` varchar(10) DEFAULT NULL,
  `tasa_dia` varchar(10) DEFAULT NULL,
  `total_litros` varchar(10) DEFAULT NULL,
  `id_estacion` int(11) DEFAULT NULL,
  `status_cierre` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_cierre`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Estructura de tabla para la tabla `table_es_estacion`
DROP TABLE IF EXISTS `table_es_estacion`;
CREATE TABLE `table_es_estacion` (
  `id_estacion` int(11) NOT NULL AUTO_INCREMENT,
  `estacion` varchar(50) DEFAULT NULL,
  `status_estacion` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_estacion`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Estructura de tabla para la tabla `table_es_tasa_dia`
DROP TABLE IF EXISTS `table_es_tasa_dia`;
CREATE TABLE `table_es_tasa_dia` (
  `id_tasa_dia` int(11) NOT NULL AUTO_INCREMENT,
  `tasa_dia` varchar(10) DEFAULT NULL,
  `tasa_update` datetime DEFAULT NULL,
  PRIMARY KEY (`id_tasa_dia`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_es_tipos_pago`
DROP TABLE IF EXISTS `table_es_tipos_pago`;
CREATE TABLE `table_es_tipos_pago` (
  `id_tipo_pago` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `status_tipo_pago` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_tipo_pago`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Estructura de tabla para la tabla `table_es_tipos_vehiculo`
DROP TABLE IF EXISTS `table_es_tipos_vehiculo`;
CREATE TABLE `table_es_tipos_vehiculo` (
  `id_tipo_vehiculo` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `status_tipo_vehiculo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_tipo_vehiculo`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Estructura de tabla para la tabla `table_flota_marca`
DROP TABLE IF EXISTS `table_flota_marca`;
CREATE TABLE `table_flota_marca` (
  `id_marca` int(11) NOT NULL AUTO_INCREMENT,
  `marca_unidad` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id_marca`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_flota_modelo`
DROP TABLE IF EXISTS `table_flota_modelo`;
CREATE TABLE `table_flota_modelo` (
  `id_modelo` int(11) NOT NULL AUTO_INCREMENT,
  `modelo_unidad` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id_modelo`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_men_menu`
DROP TABLE IF EXISTS `table_men_menu`;
CREATE TABLE `table_men_menu` (
  `menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_nombre` varchar(100) NOT NULL,
  `menu_icono` varchar(50) DEFAULT NULL,
  `menu_link` varchar(255) DEFAULT NULL,
  `menu_es_desplegable` tinyint(1) DEFAULT 0,
  `menu_orden` int(11) DEFAULT 0,
  `menu_estado` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`menu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_notificaciones`
DROP TABLE IF EXISTS `table_notificaciones`;
CREATE TABLE `table_notificaciones` (
  `id_notificacion` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_notificacion` varchar(50) NOT NULL COMMENT 'Ej: requisicion, recuperacion_pass',
  `id_referencia` int(11) NOT NULL COMMENT 'ID del despacho, solicitud, etc.',
  `mensaje` varchar(255) NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: no leido, 1: leido',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_notificacion`),
  KEY `idx_tipo_leido` (`tipo_notificacion`,`leido`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `table_per_cambio_status_personal`
DROP TABLE IF EXISTS `table_per_cambio_status_personal`;
CREATE TABLE `table_per_cambio_status_personal` (
  `id_cambioPersonal` int(11) NOT NULL AUTO_INCREMENT,
  `id_personal` int(11) NOT NULL,
  `idStatus` int(11) DEFAULT NULL,
  `textCambio` mediumtext DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id_cambioPersonal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_per_cargo`
DROP TABLE IF EXISTS `table_per_cargo`;
CREATE TABLE `table_per_cargo` (
  `id_cargo` int(11) NOT NULL AUTO_INCREMENT,
  `cargo` varchar(45) DEFAULT NULL,
  `status_cargo` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_cargo`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_per_roles`
DROP TABLE IF EXISTS `table_per_roles`;
CREATE TABLE `table_per_roles` (
  `rol_id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_nombre` varchar(50) NOT NULL,
  `rol_descripcion` text DEFAULT NULL,
  `rol_status` tinyint(1) DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`rol_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_proveedor`
DROP TABLE IF EXISTS `table_proveedor`;
CREATE TABLE `table_proveedor` (
  `id_proveedor` int(11) NOT NULL AUTO_INCREMENT,
  `rif_proveedor` varchar(15) DEFAULT NULL,
  `empresa_proveedor` varchar(45) DEFAULT NULL,
  `responsable_proveedor` varchar(50) DEFAULT NULL,
  `email_proveedor` varchar(45) DEFAULT NULL,
  `tlf_proveedor` varchar(15) DEFAULT NULL,
  `status_proveedor` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_proveedor`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_usuarios`
DROP TABLE IF EXISTS `table_usuarios`;
CREATE TABLE `table_usuarios` (
  `usuario_id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_nick` varchar(50) NOT NULL,
  `usuario_password` varchar(255) NOT NULL,
  `usuario_rol_id` int(11) NOT NULL,
  `usuario_departamento_id` int(11) NOT NULL,
  `usuario_estacion_id` int(11) DEFAULT NULL,
  `usuario_imagen` varchar(255) DEFAULT NULL,
  `usuario_status` tinyint(1) DEFAULT 1,
  `usuario_ruta` varchar(50) DEFAULT NULL,
  `usuario_id_personal` int(11) NOT NULL DEFAULT 0,
  `usuario_creado` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`usuario_id`),
  UNIQUE KEY `usuario_nick` (`usuario_nick`),
  KEY `usuario_rol_id` (`usuario_rol_id`),
  KEY `usuario_departamento_id` (`usuario_departamento_id`),
  CONSTRAINT `table_usuarios_ibfk_1` FOREIGN KEY (`usuario_rol_id`) REFERENCES `table_per_roles` (`rol_id`),
  CONSTRAINT `table_usuarios_ibfk_2` FOREIGN KEY (`usuario_departamento_id`) REFERENCES `table_departamentos` (`departamento_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_usuario_sessions`
DROP TABLE IF EXISTS `table_usuario_sessions`;
CREATE TABLE `table_usuario_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `usuario_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `table_usuario_sessions_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_personal`
DROP TABLE IF EXISTS `table_personal`;
CREATE TABLE `table_personal` (
  `id_personal` int(11) NOT NULL AUTO_INCREMENT,
  `personal_cedula` varchar(15) DEFAULT NULL,
  `personal_nombre` varchar(45) DEFAULT NULL,
  `personal_apellido` varchar(45) DEFAULT NULL,
  `personal_cargo` int(11) NOT NULL,
  `personal_tlf` varchar(45) DEFAULT NULL,
  `personal_direccion` text DEFAULT NULL,
  `personal_email` text DEFAULT NULL,
  `personal_tag` int(1) NOT NULL,
  `personal_status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_personal`),
  KEY `personal_cargo` (`personal_cargo`),
  CONSTRAINT `table_personal_ibfk_1` FOREIGN KEY (`personal_cargo`) REFERENCES `table_per_cargo` (`id_cargo`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_flota`
DROP TABLE IF EXISTS `table_flota`;
CREATE TABLE `table_flota` (
  `id_flota` int(11) NOT NULL AUTO_INCREMENT,
  `id_unidad` varchar(20) DEFAULT NULL,
  `id_marca` int(11) NOT NULL,
  `id_modelo` int(11) NOT NULL,
  `vim_unidad` varchar(20) DEFAULT NULL,
  `fecha_creacion` varchar(20) DEFAULT NULL,
  `cap_pasajero` int(11) DEFAULT NULL,
  `tipo_combustible` varchar(20) DEFAULT NULL,
  `transmision` varchar(15) DEFAULT NULL,
  `status_unidad` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_flota`),
  KEY `id_marca` (`id_marca`),
  KEY `id_modelo` (`id_modelo`),
  CONSTRAINT `table_flota_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `table_flota_marca` (`id_marca`),
  CONSTRAINT `table_flota_ibfk_2` FOREIGN KEY (`id_modelo`) REFERENCES `table_flota_modelo` (`id_modelo`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_producto`
DROP TABLE IF EXISTS `table_alm_producto`;
CREATE TABLE `table_alm_producto` (
  `id_producto` int(11) NOT NULL AUTO_INCREMENT,
  `id_enlace_producto` int(11) NOT NULL,
  `id_ubicacion` int(11) NOT NULL,
  `producto` varchar(45) DEFAULT NULL,
  `tag_producto` int(11) DEFAULT NULL,
  `present_producto` varchar(20) NOT NULL,
  `status_producto` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_producto`),
  KEY `id_enlace_producto` (`id_enlace_producto`),
  KEY `id_ubicacion` (`id_ubicacion`),
  CONSTRAINT `table_alm_producto_ibfk_1` FOREIGN KEY (`id_enlace_producto`) REFERENCES `table_alm_enlace_producto` (`id_enlace_producto`),
  CONSTRAINT `table_alm_producto_ibfk_2` FOREIGN KEY (`id_ubicacion`) REFERENCES `table_alm_ubicacion` (`id_ubicacion`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_despacho`
DROP TABLE IF EXISTS `table_alm_despacho`;
CREATE TABLE `table_alm_despacho` (
  `id_despacho` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `operador` varchar(50) NOT NULL,
  `mecanico` varchar(50) NOT NULL,
  `despachador` varchar(50) NOT NULL,
  `fecha_despacho` varchar(12) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `observacion` mediumtext DEFAULT NULL,
  `status_despacho` int(11) NOT NULL,
  `estado_orden` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Requisicion, 2: Aprobada por Compras, 3: Despachada, 4: Rechazada',
  `usuario_aprobador_id` int(11) DEFAULT NULL COMMENT 'FK a table_usuarios. ID del usuario que aprueba.',
  `fecha_aprobacion` datetime DEFAULT NULL COMMENT 'Fecha y hora de la aprobación por Compras',
  PRIMARY KEY (`id_despacho`),
  KEY `id_flota` (`id_flota`),
  KEY `user_id` (`user_id`),
  KEY `fk_despacho_aprobador` (`usuario_aprobador_id`),
  CONSTRAINT `fk_despacho_aprobador` FOREIGN KEY (`usuario_aprobador_id`) REFERENCES `table_usuarios` (`usuario_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `table_alm_despacho_ibfk_1` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `table_alm_despacho_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_historial_cambio`
DROP TABLE IF EXISTS `table_alm_historial_cambio`;
CREATE TABLE `table_alm_historial_cambio` (
  `id_historial_cambio` int(11) NOT NULL AUTO_INCREMENT,
  `obs` text CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `info` text CHARACTER SET utf8 COLLATE utf8_spanish_ci DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `fecha_historia` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_historial_cambio`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `table_alm_historial_cambio_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_relacion_despacho`
DROP TABLE IF EXISTS `table_alm_relacion_despacho`;
CREATE TABLE `table_alm_relacion_despacho` (
  `id_despacho` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cant_despacho` float DEFAULT NULL,
  KEY `id_despacho` (`id_despacho`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `table_alm_relacion_despacho_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `table_alm_despacho` (`id_despacho`),
  CONSTRAINT `table_alm_relacion_despacho_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_relacion_producto`
DROP TABLE IF EXISTS `table_alm_relacion_producto`;
CREATE TABLE `table_alm_relacion_producto` (
  `id_producto` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `cant_producto` float NOT NULL,
  KEY `id_producto` (`id_producto`),
  KEY `id_proveedor` (`id_proveedor`),
  CONSTRAINT `table_alm_relacion_producto_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto` (`id_producto`),
  CONSTRAINT `table_alm_relacion_producto_ibfk_2` FOREIGN KEY (`id_proveedor`) REFERENCES `table_proveedor` (`id_proveedor`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_requisicion`
DROP TABLE IF EXISTS `table_alm_requisicion`;
CREATE TABLE `table_alm_requisicion` (
  `id_requisicion` int(11) NOT NULL AUTO_INCREMENT,
  `id_despacho_fk` int(11) NOT NULL COMMENT 'Enlace al registro en table_alm_despacho',
  `id_flota` int(11) NOT NULL,
  `mecanico_cedula` varchar(15) DEFAULT NULL,
  `tipo_orden` enum('Servicio','Compra','Reparacion') NOT NULL,
  `observacion` text DEFAULT NULL,
  `user_id_creador` int(11) NOT NULL,
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `status_requisicion` int(11) NOT NULL DEFAULT 1 COMMENT '1:Pendiente, 2:Aprobada, 3:Rechazada',
  PRIMARY KEY (`id_requisicion`),
  KEY `id_despacho_fk` (`id_despacho_fk`),
  KEY `id_flota` (`id_flota`),
  KEY `user_id_creador` (`user_id_creador`),
  CONSTRAINT `fk_req_despacho` FOREIGN KEY (`id_despacho_fk`) REFERENCES `table_alm_despacho` (`id_despacho`) ON DELETE CASCADE,
  CONSTRAINT `fk_req_usuario` FOREIGN KEY (`user_id_creador`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_alm_requisicion_detalle`
DROP TABLE IF EXISTS `table_alm_requisicion_detalle`;
CREATE TABLE `table_alm_requisicion_detalle` (
  `id_detalle_req` int(11) NOT NULL AUTO_INCREMENT,
  `id_requisicion_fk` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad_solicitada` float NOT NULL,
  PRIMARY KEY (`id_detalle_req`),
  KEY `id_requisicion_fk` (`id_requisicion_fk`),
  KEY `id_producto` (`id_producto`),
  CONSTRAINT `fk_detreq_prod` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto` (`id_producto`),
  CONSTRAINT `fk_detreq_req` FOREIGN KEY (`id_requisicion_fk`) REFERENCES `table_alm_requisicion` (`id_requisicion`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_bienes_inventario`
DROP TABLE IF EXISTS `table_bienes_inventario`;
CREATE TABLE `table_bienes_inventario` (
  `id_bien` int(11) NOT NULL AUTO_INCREMENT,
  `bien_depatamento_id` varchar(50) NOT NULL DEFAULT '',
  `grupo_id` varchar(50) DEFAULT NULL,
  `subgrupo_id` varchar(50) DEFAULT NULL,
  `seccion_id` varchar(50) NOT NULL DEFAULT '',
  `descripcion_bien` text DEFAULT NULL,
  `fecha_adquisicion` varchar(50) DEFAULT NULL,
  `status_bien` varchar(50) DEFAULT NULL COMMENT '1: Activo, 2: Inactivo, 3: En reparación, 4: Dado de baja',
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `user_id` int(11) NOT NULL,
  `org` int(11) DEFAULT NULL,
  `edo` int(11) DEFAULT NULL,
  `status` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_bien`),
  KEY `bien_depatamento_id` (`bien_depatamento_id`),
  CONSTRAINT `table_bienes_inventario_ibfk_1` FOREIGN KEY (`bien_depatamento_id`) REFERENCES `table_bienes_departamentos` (`depatamento_bien_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_bienes_qr_departamento`
DROP TABLE IF EXISTS `table_bienes_qr_departamento`;
CREATE TABLE `table_bienes_qr_departamento` (
  `id_qr` int(11) NOT NULL AUTO_INCREMENT,
  `bien_depatamento_id` varchar(10) NOT NULL,
  `codigo_qr` varchar(255) NOT NULL,
  `fecha_generacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id_qr`),
  KEY `bien_depatamento_id` (`bien_depatamento_id`),
  CONSTRAINT `table_bienes_qr_departamento_ibfk_1` FOREIGN KEY (`bien_depatamento_id`) REFERENCES `table_bienes_departamentos` (`depatamento_bien_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_compras_pendientes`
DROP TABLE IF EXISTS `table_compras_pendientes`;
CREATE TABLE `table_compras_pendientes` (
  `id_compra_pendiente` int(11) NOT NULL AUTO_INCREMENT,
  `id_despacho` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `id_flota` int(11) NOT NULL,
  `cant_despacho` float NOT NULL,
  `fecha_despacho` date NOT NULL,
  `status_costeo` enum('Pendiente','Costeado','Anulado') NOT NULL DEFAULT 'Pendiente',
  PRIMARY KEY (`id_compra_pendiente`),
  KEY `fk_pendiente_despacho` (`id_despacho`),
  KEY `fk_pendiente_producto` (`id_producto`),
  KEY `fk_pendiente_flota` (`id_flota`),
  CONSTRAINT `fk_pendiente_despacho` FOREIGN KEY (`id_despacho`) REFERENCES `table_alm_despacho` (`id_despacho`) ON DELETE CASCADE,
  CONSTRAINT `fk_pendiente_flota` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `fk_pendiente_producto` FOREIGN KEY (`id_producto`) REFERENCES `table_alm_producto` (`id_producto`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_compras_costos`
DROP TABLE IF EXISTS `table_compras_costos`;
CREATE TABLE `table_compras_costos` (
  `id_costo` int(11) NOT NULL AUTO_INCREMENT,
  `id_compra_pendiente` int(11) NOT NULL,
  `tasa_dia` decimal(10,2) NOT NULL,
  `monto_divisa` decimal(10,2) NOT NULL,
  `monto_bs` decimal(10,2) NOT NULL,
  `fecha_costeo` date NOT NULL,
  `id_usuario_costeo` int(11) NOT NULL,
  PRIMARY KEY (`id_costo`),
  KEY `fk_costo_pendiente` (`id_compra_pendiente`),
  KEY `fk_costo_usuario` (`id_usuario_costeo`),
  CONSTRAINT `fk_costo_pendiente` FOREIGN KEY (`id_compra_pendiente`) REFERENCES `table_compras_pendientes` (`id_compra_pendiente`) ON DELETE CASCADE,
  CONSTRAINT `fk_costo_usuario` FOREIGN KEY (`id_usuario_costeo`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_es_venta`
DROP TABLE IF EXISTS `table_es_venta`;
CREATE TABLE `table_es_venta` (
  `id_venta` int(11) DEFAULT NULL,
  `id_user` int(11) DEFAULT NULL,
  `id_tipo_pago` int(11) DEFAULT NULL,
  `id_tipo_vehiculo` int(11) DEFAULT NULL,
  `litros` varchar(10) DEFAULT NULL,
  `monto` varchar(10) DEFAULT NULL,
  `id_cierre_diario` int(11) DEFAULT NULL,
  `fecha_venta` varchar(50) DEFAULT NULL,
  `hora_venta` varchar(10) DEFAULT NULL,
  `tasa_dia` varchar(10) DEFAULT NULL,
  `id_rol` int(11) DEFAULT NULL,
  `status_ticket` int(1) DEFAULT NULL,
  KEY `id_user` (`id_user`),
  KEY `id_tipo_pago` (`id_tipo_pago`),
  KEY `id_tipo_vehiculo` (`id_tipo_vehiculo`),
  CONSTRAINT `table_es_venta_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `table_usuarios` (`usuario_id`),
  CONSTRAINT `table_es_venta_ibfk_2` FOREIGN KEY (`id_tipo_pago`) REFERENCES `table_es_tipos_pago` (`id_tipo_pago`),
  CONSTRAINT `table_es_venta_ibfk_3` FOREIGN KEY (`id_tipo_vehiculo`) REFERENCES `table_es_tipos_vehiculo` (`id_tipo_vehiculo`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Estructura de tabla para la tabla `table_flota_aceite_historial`
DROP TABLE IF EXISTS `table_flota_aceite_historial`;
CREATE TABLE `table_flota_aceite_historial` (
  `id_aceite_historial` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `fecha_cambio` date NOT NULL,
  `kilometraje_cambio` int(11) NOT NULL COMMENT 'Kilometraje al momento del cambio',
  `kilometraje_anterior` int(11) DEFAULT NULL,
  `kilometraje_proximo_cambio` int(11) NOT NULL COMMENT 'Kilometraje estimado para el siguiente cambio',
  `usuario_id` int(11) NOT NULL,
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`id_aceite_historial`),
  KEY `fk_aceite_flota` (`id_flota`),
  CONSTRAINT `fk_aceite_flota` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `table_flota_kilometraje`
DROP TABLE IF EXISTS `table_flota_kilometraje`;
CREATE TABLE `table_flota_kilometraje` (
  `id_kilometraje` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `kilometraje_actual` int(11) NOT NULL,
  `fecha_actualizacion` datetime NOT NULL DEFAULT current_timestamp(),
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id_kilometraje`),
  KEY `fk_kilometraje_flota` (`id_flota`),
  CONSTRAINT `fk_kilometraje_flota` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Estructura de tabla para la tabla `table_flota_mantenimiento`
DROP TABLE IF EXISTS `table_flota_mantenimiento`;
CREATE TABLE `table_flota_mantenimiento` (
  `id_unidad_mantenimiento` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `ruta_unidad` mediumtext DEFAULT NULL,
  `operardor_unidad` mediumtext DEFAULT NULL,
  `nomb_mecanico` mediumtext DEFAULT NULL,
  `km_unidad` varchar(20) DEFAULT NULL,
  `tipo_mantenimiento` char(1) DEFAULT NULL,
  `diagnostico` mediumtext DEFAULT NULL,
  `recomendacion` mediumtext DEFAULT NULL,
  `obsOperador` mediumtext DEFAULT NULL,
  `obsSupervisor` mediumtext DEFAULT NULL,
  `obsSalida` mediumtext DEFAULT NULL,
  `fecha_entrada` varchar(25) DEFAULT NULL,
  `fecha_salida` varchar(25) DEFAULT NULL,
  `status_mantenimiento` char(1) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`id_unidad_mantenimiento`),
  KEY `fk_table_flota_mantenimiento_table_flota1_idx` (`id_flota`),
  KEY `fk_table_flota_mantenimiento_table_usuarios1_idx` (`usuario_id`),
  CONSTRAINT `fk_table_flota_mantenimiento_table_flota1` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `fk_table_flota_mantenimiento_table_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_flota_status`
DROP TABLE IF EXISTS `table_flota_status`;
CREATE TABLE `table_flota_status` (
  `idCambioStatus` int(11) NOT NULL AUTO_INCREMENT,
  `id_flota` int(11) NOT NULL,
  `idstatus` int(11) DEFAULT NULL,
  `textCambio` mediumtext DEFAULT NULL,
  `fechaCambio` timestamp NULL DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  PRIMARY KEY (`idCambioStatus`),
  KEY `fk_table_flota_status_table_flota1_idx` (`id_flota`),
  KEY `fk_table_flota_status_table_usuarios1_idx` (`usuario_id`),
  CONSTRAINT `fk_table_flota_status_table_flota1` FOREIGN KEY (`id_flota`) REFERENCES `table_flota` (`id_flota`),
  CONSTRAINT `fk_table_flota_status_table_usuarios1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_men_departamento_menu`
DROP TABLE IF EXISTS `table_men_departamento_menu`;
CREATE TABLE `table_men_departamento_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `departamento_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `departamento_id` (`departamento_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `table_men_departamento_menu_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `table_departamentos` (`departamento_id`) ON DELETE CASCADE,
  CONSTRAINT `table_men_departamento_menu_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `table_men_menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_men_rol_menu`
DROP TABLE IF EXISTS `table_men_rol_menu`;
CREATE TABLE `table_men_rol_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rol_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `rol_id` (`rol_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `table_men_rol_menu_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `table_per_roles` (`rol_id`) ON DELETE CASCADE,
  CONSTRAINT `table_men_rol_menu_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `table_men_menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_men_submenu`
DROP TABLE IF EXISTS `table_men_submenu`;
CREATE TABLE `table_men_submenu` (
  `submenu_id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `submenu_nombre` varchar(100) NOT NULL,
  `submenu_link` varchar(255) DEFAULT NULL,
  `submenu_orden` int(11) DEFAULT 0,
  `submenu_estado` tinyint(1) DEFAULT 1,
  `submenu_pagina` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`submenu_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `table_men_submenu_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `table_men_menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Estructura de tabla para la tabla `table_men_usuario_menu`
DROP TABLE IF EXISTS `table_men_usuario_menu`;
CREATE TABLE `table_men_usuario_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `table_men_usuario_menu_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `table_men_usuario_menu_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `table_men_menu` (`menu_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_men_usuario_submenu`
DROP TABLE IF EXISTS `table_men_usuario_submenu`;
CREATE TABLE `table_men_usuario_submenu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NOT NULL,
  `submenu_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `submenu_id` (`submenu_id`),
  CONSTRAINT `table_men_usuario_submenu_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`usuario_id`) ON DELETE CASCADE,
  CONSTRAINT `table_men_usuario_submenu_ibfk_2` FOREIGN KEY (`submenu_id`) REFERENCES `table_men_submenu` (`submenu_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci;

-- Estructura de tabla para la tabla `table_recovery_requests`
DROP TABLE IF EXISTS `table_recovery_requests`;
CREATE TABLE `table_recovery_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `identifier_provided` varchar(100) NOT NULL COMMENT 'Email o CI que ingresó el usuario',
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=Pendiente, 1=Resuelto',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_recovery_user` FOREIGN KEY (`user_id`) REFERENCES `table_usuarios` (`usuario_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;

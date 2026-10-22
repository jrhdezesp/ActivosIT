-- =============================================================================
-- SISTEMA DE GESTION DE ACTIVOS IT - PINTURAS AMERICANAS
-- 01_security.sql: Seguridad, Roles, Permisos y Bitacora de Auditoria
-- =============================================================================

USE `activosit`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. ESTRUCTURA DE TABLAS
-- -----------------------------------------------------------------------------

DROP TABLE IF EXISTS `roles_permisos`;
DROP TABLE IF EXISTS `usuarios_roles`;
DROP TABLE IF EXISTS `permisos`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `bitacora`;

-- Limpieza de tablas obsoletas si existiesen
DROP TABLE IF EXISTS `funciones_roles`;
DROP TABLE IF EXISTS `roles_usuarios`;
DROP TABLE IF EXISTS `funciones`;
DROP TABLE IF EXISTS `usuario`;

CREATE TABLE `usuarios` (
  `id_usuario` INT NOT NULL AUTO_INCREMENT,
  `user_usuario` VARCHAR(255) NOT NULL,
  `clave_usuario` VARCHAR(255) NOT NULL,
  `nombre_usuario` VARCHAR(100) NULL,
  `estado_usuario` BOOLEAN NOT NULL DEFAULT 1,
  `ultimo_acceso_usuario` DATETIME NULL,
  PRIMARY KEY (`id_usuario`),
  UNIQUE KEY `uq_usuarios_user` (`user_usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id_rol` INT NOT NULL AUTO_INCREMENT,
  `nombre_rol` VARCHAR(255) NOT NULL,
  `descripcion_rol` VARCHAR(255) NULL,
  `estado_rol` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_rol`),
  UNIQUE KEY `uq_roles_nombre` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `permisos` (
  `id_permiso` INT NOT NULL AUTO_INCREMENT,
  `nombre_permiso` VARCHAR(255) NOT NULL,
  `descripcion_permiso` VARCHAR(255) NULL,
  `modulo_permiso` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permisos_nombre` (`nombre_permiso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usuarios_roles` (
  `id_usuario_rol` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NOT NULL,
  `id_rol` INT NOT NULL,
  PRIMARY KEY (`id_usuario_rol`),
  UNIQUE KEY `uq_usuario_rol` (`id_usuario`, `id_rol`),
  CONSTRAINT `fk_usuarios_roles_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_usuarios_roles_rol`
    FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles_permisos` (
  `id_rol_permiso` INT NOT NULL AUTO_INCREMENT,
  `id_rol` INT NOT NULL,
  `id_permiso` INT NOT NULL,
  PRIMARY KEY (`id_rol_permiso`),
  UNIQUE KEY `uq_rol_permiso` (`id_rol`, `id_permiso`),
  CONSTRAINT `fk_roles_permisos_rol`
    FOREIGN KEY (`id_rol`) REFERENCES `roles` (`id_rol`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_roles_permisos_permiso`
    FOREIGN KEY (`id_permiso`) REFERENCES `permisos` (`id_permiso`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bitacora` (
  `id_bitacora` INT NOT NULL AUTO_INCREMENT,
  `id_usuario` INT NULL,
  `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tipo_operacion` VARCHAR(50) NOT NULL,
  `modulo_afectado` VARCHAR(100) NOT NULL,
  `registro_relacionado_id` INT NULL,
  `direccion_ip` VARCHAR(45) NULL,
  `datos_anteriores` JSON NULL,
  `datos_nuevos` JSON NULL,
  `resultado` ENUM('EXITO', 'DENEGADO', 'ERROR') NOT NULL,
  PRIMARY KEY (`id_bitacora`),
  KEY `idx_bitacora_usuario` (`id_usuario`),
  CONSTRAINT `fk_bitacora_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. DATOS SEMILLA (SEEDS)
-- -----------------------------------------------------------------------------

-- Usuarios iniciales (Contraseña: [REDACTED_DEFAULT_PASSWORD])
INSERT INTO `usuarios` (`id_usuario`, `user_usuario`, `clave_usuario`, `nombre_usuario`, `estado_usuario`, `ultimo_acceso_usuario`) VALUES
(1, 'admin@pinturasamericanas.com', '$2y$10$4kVxNCTfw1w.702sTa6dR..3wgXXhi410zJ1vvMMrmmk.wceiHb4C', 'Administrador de IT', 1, NOW()),
(2, 'soporte@pinturasamericanas.com', '$2y$10$4kVxNCTfw1w.702sTa6dR..3wgXXhi410zJ1vvMMrmmk.wceiHb4C', 'Técnico de Soporte', 1, NOW()),
(3, 'auditoria@pinturasamericanas.com', '$2y$10$4kVxNCTfw1w.702sTa6dR..3wgXXhi410zJ1vvMMrmmk.wceiHb4C', 'Auditor de Activos', 1, NOW());

-- Roles iniciales
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion_rol`, `estado_rol`) VALUES
(1, 'Administrador', 'Control total de la infraestructura y configuraciones de IT', 1),
(2, 'Auditor', 'Consulta, visualización de movimientos, kardex e informes', 1),
(3, 'Técnico de Soporte', 'Operación de inventario, traslados y mantenimientos', 1);

-- Permisos iniciales
INSERT INTO `permisos` (`id_permiso`, `nombre_permiso`, `descripcion_permiso`, `modulo_permiso`) VALUES
(1, 'USUARIOS_ACCESO', 'Gestión de usuarios y accesos', 'Seguridad'),
(2, 'ROLES_ACCESO', 'Gestión de roles y asignación de permisos', 'Seguridad'),
(3, 'CATALOGOS_GESTION', 'Administración de clasificaciones y catálogos', 'Catálogos'),
(4, 'ARTICULOS_GESTION', 'Gestión de artículos y parámetros de stock', 'Artículos'),
(5, 'EQUIPOS_GESTION', 'Gestión de equipos serializados y hojas de vida', 'Equipos'),
(6, 'INVENTARIO_DOCS', 'Registro de entradas y salidas de inventario', 'Inventario'),
(7, 'ASIGNACIONES_GESTION', 'Asignación y recepción de custodias', 'Asignaciones'),
(8, 'MANTENIMIENTO_REGISTRO', 'Registro y control de servicios técnicos', 'Mantenimientos'),
(9, 'REPORTES_KARDEX', 'Consulta de kardex y reportes analíticos', 'Reportes'),
(10, 'BITACORA_CONSULTA', 'Visualización de registros de auditoría', 'Bitácora');

-- Asignación de Roles a Usuarios
INSERT INTO `usuarios_roles` (`id_usuario`, `id_rol`) VALUES
(1, 1),
(2, 3),
(3, 2);

-- Asignación de Permisos a Roles
INSERT INTO `roles_permisos` (`id_rol`, `id_permiso`) VALUES
-- Administrador: todos los permisos
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8), (1, 9), (1, 10),
-- Auditor: lectura y auditoría
(2, 4), (2, 5), (2, 9), (2, 10),
-- Técnico: artículos, equipos, inventario, asignaciones y mantenimientos
(3, 4), (3, 5), (3, 6), (3, 7), (3, 8);

SET FOREIGN_KEY_CHECKS = 1;

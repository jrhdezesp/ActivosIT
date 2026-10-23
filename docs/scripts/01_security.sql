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
  -- Perfil funcional de la cuenta: se conserva para reportes y para el
  -- formulario de usuarios. La autorizacion real la resuelven los roles.
  `tipo_usuario` VARCHAR(3) NOT NULL DEFAULT 'PBL',
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
  -- nombre_permiso es el codigo que valida el sistema:
  --   * Clase completa del controlador (Controllers\Usuarios\Usuarios) para tipo CTR
  --   * Identificador de menu (Menu_Usuarios) para tipo MNU
  `nombre_permiso` VARCHAR(255) NOT NULL,
  `descripcion_permiso` VARCHAR(255) NULL,
  `modulo_permiso` VARCHAR(255) NOT NULL DEFAULT 'General',
  -- CTR = controlador, MNU = menu, MNT = mantenimiento, RPT = reporte
  `tipo_permiso` VARCHAR(3) NOT NULL DEFAULT 'CTR',
  `estado_permiso` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_permiso`),
  UNIQUE KEY `uq_permisos_nombre` (`nombre_permiso`),
  KEY `idx_permisos_tipo` (`tipo_permiso`)
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

-- Usuarios iniciales.
-- Contraseña para las tres cuentas: Admin123$!
--
-- El hash es bcrypt estandar (sin HMAC) para que sea reproducible en cualquier
-- instalacion. Security::verifyPassword acepta ambos esquemas: bcrypt directo y
-- bcrypt sobre el HMAC de PWD_HASH, por lo que las claves creadas desde la
-- aplicacion siguen funcionando.
--
-- IMPORTANTE: cambie estas contraseñas despues del primer acceso.
INSERT INTO `usuarios` (`id_usuario`, `user_usuario`, `clave_usuario`, `nombre_usuario`, `tipo_usuario`, `estado_usuario`, `ultimo_acceso_usuario`) VALUES
(1, 'admin@pinturasamericanas.com', '$2y$10$5Yi.fT/kPdDjX8OchkuHVu6VyC47Wyo5iyu2vKddpui/hIgGagg4G', 'Administrador de IT', 'ADM', 1, NULL),
(2, 'soporte@pinturasamericanas.com', '$2y$10$5Yi.fT/kPdDjX8OchkuHVu6VyC47Wyo5iyu2vKddpui/hIgGagg4G', 'Técnico de Soporte', 'TEC', 1, NULL),
(3, 'auditoria@pinturasamericanas.com', '$2y$10$5Yi.fT/kPdDjX8OchkuHVu6VyC47Wyo5iyu2vKddpui/hIgGagg4G', 'Auditor de Activos', 'AUD', 1, NULL);

-- Roles iniciales
INSERT INTO `roles` (`id_rol`, `nombre_rol`, `descripcion_rol`, `estado_rol`) VALUES
(1, 'Administrador', 'Control total de la infraestructura y configuraciones de IT', 1),
(2, 'Auditor', 'Consulta, visualización de movimientos, kardex e informes', 1),
(3, 'Técnico de Soporte', 'Operación de inventario, traslados y mantenimientos', 1),
(4, 'Público', 'Cuenta creada por auto-registro: solo accede a su perfil', 1);

-- Permisos iniciales.
-- Los permisos de tipo CTR usan el nombre completo de la clase del controlador
-- (es lo que valida PrivateController) y los de tipo MNU usan el identificador
-- de nav.config.json (es lo que valida Utilities\Nav).
INSERT INTO `permisos` (`id_permiso`, `nombre_permiso`, `descripcion_permiso`, `modulo_permiso`, `tipo_permiso`, `estado_permiso`) VALUES
-- Controladores privados
( 1, 'Controllers\\Dashboard\\Dashboard',              'Panel principal del sistema',                    'Dashboard',  'CTR', 1),
( 2, 'Controllers\\Articulos\\Articulos',              'Catálogo de artículos (listado)',                'Activos',    'CTR', 1),
( 3, 'Controllers\\Articulos\\Articulo',               'Formulario de artículo',                         'Activos',    'CTR', 1),
( 4, 'Controllers\\Catalogos\\Catalogos',              'Catálogos generales',                            'Catálogos',  'CTR', 1),
( 5, 'Controllers\\Catalogos\\Catalogo',               'Formulario de catálogo',                         'Catálogos',  'CTR', 1),
( 6, 'Controllers\\Catalogos\\CatalogosOperativos',    'Catálogos operativos',                           'Catálogos',  'CTR', 1),
( 7, 'Controllers\\Usuarios\\Usuarios',                'Gestión de usuarios',                            'Seguridad',  'CTR', 1),
( 8, 'Controllers\\Usuarios\\Usuario',                 'Formulario de usuario',                          'Seguridad',  'CTR', 1),
( 9, 'Controllers\\Roles\\Roles',                      'Gestión de roles',                               'Seguridad',  'CTR', 1),
(10, 'Controllers\\Roles\\Rol',                        'Formulario de rol',                              'Seguridad',  'CTR', 1),
(11, 'Controllers\\Funciones\\Funciones',              'Gestión de permisos',                            'Seguridad',  'CTR', 1),
(12, 'Controllers\\Funciones\\Funcion',                'Formulario de permiso',                          'Seguridad',  'CTR', 1),
(13, 'Controllers\\Navegacion\\Navegacion',            'Editor de navegación',                           'Seguridad',  'CTR', 1),
(14, 'Controllers\\Perfil\\Perfil',                    'Perfil del usuario autenticado',                 'Perfil',     'CTR', 1),
-- Elementos de menú
(15, 'Menu_Dashboard',            'Menú: dashboard',                'Dashboard',  'MNU', 1),
(16, 'Menu_Articulos',            'Menú: catálogo de artículos',    'Activos',    'MNU', 1),
(17, 'Menu_Equipos',              'Menú: equipos',                  'Activos',    'MNU', 1),
(18, 'Menu_Entradas',             'Menú: entradas de inventario',   'Inventario', 'MNU', 1),
(19, 'Menu_Salidas',              'Menú: salidas de inventario',    'Inventario', 'MNU', 1),
(20, 'Menu_Asignaciones',         'Menú: asignaciones',             'Inventario', 'MNU', 1),
(21, 'Menu_Mantenimientos',       'Menú: mantenimientos',           'Inventario', 'MNU', 1),
(22, 'Menu_Usuarios',             'Menú: usuarios',                 'Seguridad',  'MNU', 1),
(23, 'Menu_Roles',                'Menú: roles',                    'Seguridad',  'MNU', 1),
(24, 'Menu_Funciones',            'Menú: permisos',                 'Seguridad',  'MNU', 1),
(25, 'Menu_Navegacion',           'Menú: navegación',               'Seguridad',  'MNU', 1),
(26, 'Menu_CatalogosGenerales',   'Menú: catálogos generales',      'Catálogos',  'MNU', 1),
(27, 'Menu_CatalogosOperativos',  'Menú: catálogos operativos',     'Catálogos',  'MNU', 1),
(28, 'Menu_CentroReportes',       'Menú: centro de reportes',       'Reportes',   'MNU', 1),
(29, 'Menu_Kardex',               'Menú: kardex',                   'Reportes',   'MNU', 1),
(30, 'Menu_EquiposCustodio',      'Menú: equipos por custodio',     'Reportes',   'MNU', 1),
(31, 'Menu_Auditoria',            'Menú: auditoría',                'Auditoría',  'MNU', 1),
(32, 'Menu_Perfil',               'Menú: mi perfil',                'Perfil',     'MNU', 1),
-- Controladores del módulo de equipos (agregados después del bloque de menús
-- para no alterar los identificadores ya asignados).
(33, 'Controllers\\Equipos\\Equipos',                  'Equipos individuales (listado)',                 'Activos',    'CTR', 1),
(34, 'Controllers\\Equipos\\Equipo',                   'Formulario de equipo',                           'Activos',    'CTR', 1),
-- Controladores del módulo de entradas de inventario.
(35, 'Controllers\\Inventario\\Entradas',              'Entradas de inventario (listado)',               'Inventario', 'CTR', 1),
(36, 'Controllers\\Inventario\\Entrada',               'Formulario de entrada',                          'Inventario', 'CTR', 1),
-- Controladores del módulo de salidas de inventario.
(37, 'Controllers\\Inventario\\Salidas',               'Salidas de inventario (listado)',                'Inventario', 'CTR', 1),
(38, 'Controllers\\Inventario\\Salida',                'Formulario de salida',                           'Inventario', 'CTR', 1);

-- Asignación de Roles a Usuarios
INSERT INTO `usuarios_roles` (`id_usuario`, `id_rol`) VALUES
(1, 1),
(2, 3),
(3, 2);

-- Asignación de Permisos a Roles
INSERT INTO `roles_permisos` (`id_rol`, `id_permiso`) VALUES
-- Administrador: todos los permisos (1..38)
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6), (1, 7), (1, 8),
(1, 9), (1, 10), (1, 11), (1, 12), (1, 13), (1, 14), (1, 15), (1, 16),
(1, 17), (1, 18), (1, 19), (1, 20), (1, 21), (1, 22), (1, 23), (1, 24),
(1, 25), (1, 26), (1, 27), (1, 28), (1, 29), (1, 30), (1, 31), (1, 32),
(1, 33), (1, 34), (1, 35), (1, 36), (1, 37), (1, 38),
-- Auditor: consulta de catálogo, artículos, equipos, entradas, salidas y su perfil
(2, 1), (2, 2), (2, 3), (2, 14), (2, 15), (2, 16), (2, 17), (2, 18), (2, 19), (2, 32),
(2, 33), (2, 34), (2, 35), (2, 36), (2, 37), (2, 38),
-- Técnico de Soporte: operación de activos, equipos, entradas, salidas y catálogos
(3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 14), (3, 15),
(3, 16), (3, 17), (3, 18), (3, 19), (3, 26), (3, 27), (3, 32), (3, 33), (3, 34),
(3, 35), (3, 36), (3, 37), (3, 38),
-- Público: únicamente su perfil
(4, 14), (4, 32);

SET FOREIGN_KEY_CHECKS = 1;

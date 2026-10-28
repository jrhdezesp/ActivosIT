-- =============================================================================
-- SISTEMA DE GESTION DE ACTIVOS IT - PINTURAS AMERICANAS
-- 02_catalogos.sql: Catalogos Generales y Operativos
-- =============================================================================

USE `activosit`;
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. ESTRUCTURA DE TABLAS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `clasificaciones` (
  `id_clasificacion` INT NOT NULL AUTO_INCREMENT,
  `nombre_clasificacion` VARCHAR(255) NOT NULL,
  `descripcion_clasif` VARCHAR(255) NULL,
  `estado_clasif` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_clasificacion`),
  UNIQUE KEY `uq_clasificaciones_nombre` (`nombre_clasificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ubicaciones` (
  `id_ubicacion` INT NOT NULL AUTO_INCREMENT,
  `nombre_ubicacion` VARCHAR(100) NOT NULL,
  `descripcion_ubi` VARCHAR(255) NULL,
  `estado_ubi` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_ubicacion`),
  UNIQUE KEY `uq_ubicaciones_nombre` (`nombre_ubicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departamentos` (
  `id_departamento` INT NOT NULL AUTO_INCREMENT,
  `nombre_departamento` VARCHAR(150) NOT NULL,
  `descripcion_departamento` VARCHAR(255) NULL,
  `estado_departamento` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_departamento`),
  UNIQUE KEY `uq_departamentos_nombre` (`nombre_departamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `unidades_medida` (
  `id_unidad_medida` INT NOT NULL AUTO_INCREMENT,
  `nombre_unidad` VARCHAR(50) NOT NULL,
  `simbolo_medida` VARCHAR(10) NULL,
  `estado_medida` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_unidad_medida`),
  UNIQUE KEY `uq_unidades_medida_nombre` (`nombre_unidad`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tipos_documentos` (
  `id_tipo_doc` INT NOT NULL AUTO_INCREMENT,
  `nombre_tipo` VARCHAR(255) NOT NULL,
  `naturaleza_doc` ENUM('ENTRADA', 'SALIDA') NOT NULL,
  `estado_doc` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_doc`),
  UNIQUE KEY `uq_tipos_documentos_nombre` (`nombre_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tipos_mantenimientos` (
  `id_tipo_mantenimiento` INT NOT NULL AUTO_INCREMENT,
  `nombre_tipo` VARCHAR(255) NOT NULL,
  `descripcion_mant` VARCHAR(255) NULL,
  `estado_mant` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_tipo_mantenimiento`),
  UNIQUE KEY `uq_tipos_mantenimientos_nombre` (`nombre_tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `motivos_ajustes_anulaciones` (
  `id_motivo` INT NOT NULL AUTO_INCREMENT,
  `nombre_motivo` VARCHAR(255) NOT NULL,
  `motivo_aplica_a` ENUM('AJUSTE_ENTRADA', 'AJUSTE_SALIDA', 'ANULACION', 'BAJA') NOT NULL,
  `estado_motivo` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_motivo`),
  UNIQUE KEY `uq_motivos_nombre` (`nombre_motivo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Sin datos semilla: los registros se crean desde la aplicación.

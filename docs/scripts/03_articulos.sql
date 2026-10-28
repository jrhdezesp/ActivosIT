-- =============================================================================
-- SISTEMA DE GESTION DE ACTIVOS IT - PINTURAS AMERICANAS
-- 03_articulos.sql: Catalogo General de Articulos
-- Requiere: 02_catalogos.sql
-- =============================================================================

USE `activosit`;
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. ESTRUCTURA DE TABLA
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `articulos` (
  `id_articulo` INT NOT NULL AUTO_INCREMENT,
  `codigo_interno_articulo` VARCHAR(255) NOT NULL,
  `nombre_articulo` VARCHAR(255) NOT NULL,
  `descripcion_articulo` TEXT NULL,
  `marca_articulo` VARCHAR(100) NULL,
  `modelo_articulo` VARCHAR(150) NULL,
  `observaciones_articulo` TEXT NULL,
  `id_clasificacion` INT NOT NULL,
  `id_unidad_medida` INT NOT NULL,
  `tipo_control` ENUM('CANTIDAD', 'SERIE') NOT NULL DEFAULT 'CANTIDAD',
  `existencia_minima` INT NULL DEFAULT 0,
  `existencia_actual` INT NOT NULL DEFAULT 0,
  `estado_registro` BOOLEAN NOT NULL DEFAULT 1,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_modificacion` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_articulo`),
  UNIQUE KEY `uq_articulos_codigo` (`codigo_interno_articulo`),
  KEY `idx_articulos_clasificacion` (`id_clasificacion`),
  KEY `idx_articulos_unidad` (`id_unidad_medida`),
  KEY `idx_articulos_estado` (`estado_registro`),
  CONSTRAINT `fk_articulos_clasificacion`
    FOREIGN KEY (`id_clasificacion`) REFERENCES `clasificaciones` (`id_clasificacion`)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_articulos_unidad`
    FOREIGN KEY (`id_unidad_medida`) REFERENCES `unidades_medida` (`id_unidad_medida`)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Sin datos semilla: los registros se crean desde la aplicación.

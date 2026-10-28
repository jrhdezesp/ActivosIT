-- =============================================================================
-- SISTEMA DE GESTION DE ACTIVOS IT - PINTURAS AMERICANAS
-- 04_equipos.sql: Equipos Individuales y Activos Serializados
-- Requiere: 02_catalogos.sql, 03_articulos.sql
-- =============================================================================

USE `activosit`;
SET NAMES utf8mb4;

-- -----------------------------------------------------------------------------
-- 1. ESTRUCTURA DE TABLA
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `equipos_individuales` (
  `id_equipo` INT NOT NULL AUTO_INCREMENT,
  `id_articulo` INT NOT NULL,
  `numero_serie_equipo` VARCHAR(100) NULL,
  `codigo_patrimonial_equipo` VARCHAR(100) NULL,
  `fecha_adquisicion` DATE NULL,
  `id_motivo` INT NULL,
  `proveedor_origen` VARCHAR(150) NULL,
  `estado_operativo` ENUM('DISPONIBLE', 'RESERVADO', 'ASIGNADO', 'EN_MANTENIMIENTO', 'EN_REVISION', 'DE_BAJA') NOT NULL DEFAULT 'DISPONIBLE',
  `estado_fisico` VARCHAR(100) NOT NULL DEFAULT 'BUENO',
  `id_ubicacion` INT NOT NULL,
  `garantia_vencimiento_equipo` DATE NULL,
  `observaciones_equipo` TEXT NULL,
  `estado_registro` BOOLEAN NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_equipo`),
  UNIQUE KEY `uq_equipos_serie` (`numero_serie_equipo`),
  UNIQUE KEY `uq_equipos_patrimonial` (`codigo_patrimonial_equipo`),
  KEY `idx_equipos_articulo` (`id_articulo`),
  KEY `idx_equipos_ubicacion` (`id_ubicacion`),
  KEY `idx_equipos_motivo` (`id_motivo`),
  CONSTRAINT `fk_equipos_articulo`
    FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_equipos_ubicacion`
    FOREIGN KEY (`id_ubicacion`) REFERENCES `ubicaciones` (`id_ubicacion`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_equipos_motivo`
    FOREIGN KEY (`id_motivo`) REFERENCES `motivos_ajustes_anulaciones` (`id_motivo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Sin datos semilla: los registros se crean desde la aplicación.

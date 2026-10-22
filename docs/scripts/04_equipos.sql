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
  `estado_operativo` ENUM('DISPONIBLE', 'ASIGNADO', 'EN_MANTENIMIENTO', 'EN_REVISION', 'DE_BAJA') NOT NULL DEFAULT 'DISPONIBLE',
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
-- 2. DATOS SEMILLA (SEEDS)
-- -----------------------------------------------------------------------------

INSERT INTO `equipos_individuales` (
  `id_equipo`,
  `id_articulo`,
  `numero_serie_equipo`,
  `codigo_patrimonial_equipo`,
  `fecha_adquisicion`,
  `id_motivo`,
  `proveedor_origen`,
  `estado_operativo`,
  `id_ubicacion`,
  `garantia_vencimiento_equipo`,
  `observaciones_equipo`,
  `estado_registro`
) VALUES
(
  1,
  1, -- Laptop Dell Latitude 3420
  'DELL-LAT-8841A',
  'PA-IT-00101',
  '2026-01-15',
  NULL,
  'Dell Technologies Honduras',
  'DISPONIBLE',
  1, -- Bodega Principal IT
  '2028-01-15',
  'Equipo nuevo en empaque original con cargador.',
  1
),
(
  2,
  1, -- Laptop Dell Latitude 3420
  'DELL-LAT-8842B',
  'PA-IT-00102',
  '2026-01-15',
  NULL,
  'Dell Technologies Honduras',
  'DISPONIBLE',
  1, -- Bodega Principal IT
  '2028-01-15',
  'Equipo preparado para entrega a nuevo colaborador.',
  1
),
(
  3,
  2, -- Monitor Dell 24 pulgadas FHD
  'MON-DL-9901X',
  'PA-IT-00201',
  '2026-02-10',
  NULL,
  'CompuSistemas',
  'DISPONIBLE',
  1, -- Bodega Principal IT
  '2027-02-10',
  'Monitor verificado sin píxeles muertos.',
  1
)
ON DUPLICATE KEY UPDATE `codigo_patrimonial_equipo` = VALUES(`codigo_patrimonial_equipo`);

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
-- 2. DATOS SEMILLA (SEEDS)
-- -----------------------------------------------------------------------------

INSERT INTO `articulos` (
  `id_articulo`,
  `codigo_interno_articulo`,
  `nombre_articulo`,
  `descripcion_articulo`,
  `id_clasificacion`,
  `id_unidad_medida`,
  `tipo_control`,
  `existencia_minima`,
  `existencia_actual`,
  `estado_registro`,
  `fecha_creacion`
) VALUES
(
  1,
  'ART-LAP-001',
  'Laptop Dell Latitude 3420',
  'Laptop corporativa Core i5, 16GB RAM, SSD 512GB para trabajo administrativo e híbrido.',
  1, -- Equipos de cómputo
  1, -- Unidad
  'SERIE',
  3,
  2,
  1,
  NOW()
),
(
  2,
  'ART-MON-001',
  'Monitor Dell 24 pulgadas FHD',
  'Monitor LED Full HD 1080p con entradas HDMI y DisplayPort.',
  2, -- Periféricos
  1, -- Unidad
  'SERIE',
  2,
  1,
  1,
  NOW()
),
(
  3,
  'ART-CAB-001',
  'Bobina Cable de Red UTP Cat6',
  'Cable de red categoría 6, 100% cobre para infraestructura interna.',
  3, -- Redes
  3, -- Metro
  'CANTIDAD',
  50,
  305,
  1,
  NOW()
),
(
  4,
  'ART-CON-001',
  'Conectores RJ45 Blindados',
  'Paquete de conectores macho RJ45 categoría 6 para cableado de red.',
  3, -- Redes
  1, -- Unidad
  'CANTIDAD',
  20,
  100,
  1,
  NOW()
),
(
  5,
  'ART-PER-001',
  'Kit Teclado y Mouse Inalámbrico Logitech',
  'Combo periférico inalámbrico USB para estaciones de trabajo.',
  2, -- Periféricos
  5, -- Kit
  'CANTIDAD',
  5,
  12,
  1,
  NOW()
)
ON DUPLICATE KEY UPDATE `nombre_articulo` = VALUES(`nombre_articulo`);

-- =============================================================================
-- SISTEMA DE GESTION DE ACTIVOS IT - PINTURAS AMERICANAS
-- 05_inventario.sql: Entradas, Salidas y Movimientos (Kardex)
-- Requiere: 01_security.sql, 02_catalogos.sql, 03_articulos.sql, 04_equipos.sql
-- =============================================================================

USE `activosit`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. ESTRUCTURA DE TABLAS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `documentos_entrada` (
  `id_doc_entrada` INT NOT NULL AUTO_INCREMENT,
  `correlativo` VARCHAR(50) NOT NULL,
  `id_tipo_doc` INT NOT NULL,
  `fecha_movimiento` DATETIME NOT NULL,
  `id_usuario` INT NOT NULL,
  `id_motivo` INT NULL,
  `proveedor_origen` VARCHAR(150) NULL,
  `estado_doc` ENUM('BORRADOR', 'CONFIRMADO', 'ANULADO') NOT NULL DEFAULT 'BORRADOR',
  `observaciones` TEXT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_doc_entrada`),
  UNIQUE KEY `uq_doc_entrada_correlativo` (`correlativo`),
  KEY `idx_doc_ent_tipo` (`id_tipo_doc`),
  KEY `idx_doc_ent_usuario` (`id_usuario`),
  KEY `idx_doc_ent_motivo` (`id_motivo`),
  CONSTRAINT `fk_doc_entrada_tipo`
    FOREIGN KEY (`id_tipo_doc`) REFERENCES `tipos_documentos` (`id_tipo_doc`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_entrada_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_entrada_motivo`
    FOREIGN KEY (`id_motivo`) REFERENCES `motivos_ajustes_anulaciones` (`id_motivo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detalles_entrada` (
  `id_detalle_ent` INT NOT NULL AUTO_INCREMENT,
  `id_doc_entrada` INT NOT NULL,
  `id_articulo` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `id_equipo` INT NULL,
  `costo_unitario` DECIMAL(10,2) NULL DEFAULT 0.00,
  PRIMARY KEY (`id_detalle_ent`),
  KEY `idx_det_ent_doc` (`id_doc_entrada`),
  KEY `idx_det_ent_articulo` (`id_articulo`),
  KEY `idx_det_ent_equipo` (`id_equipo`),
  CONSTRAINT `fk_det_entrada_documento`
    FOREIGN KEY (`id_doc_entrada`) REFERENCES `documentos_entrada` (`id_doc_entrada`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_det_entrada_articulo`
    FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_det_entrada_equipo`
    FOREIGN KEY (`id_equipo`) REFERENCES `equipos_individuales` (`id_equipo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `documentos_salida` (
  `id_doc_salida` INT NOT NULL AUTO_INCREMENT,
  `correlativo` VARCHAR(50) NOT NULL,
  `id_tipo_doc` INT NOT NULL,
  `fecha_movimiento` DATETIME NOT NULL,
  `id_usuario` INT NOT NULL,
  `id_motivo` INT NULL,
  `destino_salida` VARCHAR(255) NOT NULL,
  `autorizacion` VARCHAR(255) NULL,
  `estado_doc` ENUM('BORRADOR', 'CONFIRMADO', 'ANULADO') NOT NULL DEFAULT 'BORRADOR',
  `observaciones` TEXT NULL,
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_doc_salida`),
  UNIQUE KEY `uq_doc_salida_correlativo` (`correlativo`),
  KEY `idx_doc_sal_tipo` (`id_tipo_doc`),
  KEY `idx_doc_sal_usuario` (`id_usuario`),
  KEY `idx_doc_sal_motivo` (`id_motivo`),
  CONSTRAINT `fk_doc_salida_tipo`
    FOREIGN KEY (`id_tipo_doc`) REFERENCES `tipos_documentos` (`id_tipo_doc`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_salida_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_doc_salida_motivo`
    FOREIGN KEY (`id_motivo`) REFERENCES `motivos_ajustes_anulaciones` (`id_motivo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `detalles_salida` (
  `id_detalle_salida` INT NOT NULL AUTO_INCREMENT,
  `id_doc_salida` INT NOT NULL,
  `id_articulo` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `id_equipo` INT NULL,
  PRIMARY KEY (`id_detalle_salida`),
  KEY `idx_det_sal_doc` (`id_doc_salida`),
  KEY `idx_det_sal_articulo` (`id_articulo`),
  KEY `idx_det_sal_equipo` (`id_equipo`),
  CONSTRAINT `fk_det_salida_documento`
    FOREIGN KEY (`id_doc_salida`) REFERENCES `documentos_salida` (`id_doc_salida`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_det_salida_articulo`
    FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_det_salida_equipo`
    FOREIGN KEY (`id_equipo`) REFERENCES `equipos_individuales` (`id_equipo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `movimientos_inventario` (
  `id_movimiento` INT NOT NULL AUTO_INCREMENT,
  `id_articulo` INT NOT NULL,
  `id_motivo` INT NULL,
  `tipo_movimiento` ENUM('ENTRADA', 'SALIDA', 'AJUSTE_POSITIVO', 'AJUSTE_NEGATIVO') NOT NULL,
  `cantidad` INT NOT NULL,
  `existencia_anterior` INT NOT NULL,
  `existencia_nueva` INT NOT NULL,
  `id_doc_referencia` INT NULL,
  `origen_referencia` VARCHAR(50) NULL,
  `fecha_movimiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_usuario` INT NOT NULL,
  `observacion` VARCHAR(255) NULL,
  PRIMARY KEY (`id_movimiento`),
  KEY `idx_mov_articulo` (`id_articulo`),
  KEY `idx_mov_usuario` (`id_usuario`),
  KEY `idx_mov_motivo` (`id_motivo`),
  CONSTRAINT `fk_mov_articulo`
    FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id_articulo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_usuario`
    FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mov_motivo`
    FOREIGN KEY (`id_motivo`) REFERENCES `motivos_ajustes_anulaciones` (`id_motivo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. DATOS SEMILLA (SEEDS)
-- -----------------------------------------------------------------------------

-- Documento de entrada inicial (Recepción por compra)
INSERT INTO `documentos_entrada` (
  `id_doc_entrada`,
  `correlativo`,
  `id_tipo_doc`,
  `fecha_movimiento`,
  `id_usuario`,
  `id_motivo`,
  `proveedor_origen`,
  `estado_doc`,
  `observaciones`,
  `fecha_creacion`
) VALUES
(
  1,
  'ENT-2026-0001',
  1, -- Recepción por compra
  NOW(),
  1, -- Admin
  NULL,
  'Dell Technologies Honduras',
  'CONFIRMADO',
  'Recepción de lote inicial de laptops y monitores corporativos.',
  NOW()
)
ON DUPLICATE KEY UPDATE `correlativo` = VALUES(`correlativo`);

-- Detalles de la entrada inicial
INSERT INTO `detalles_entrada` (`id_detalle_ent`, `id_doc_entrada`, `id_articulo`, `cantidad`, `id_equipo`, `costo_unitario`) VALUES
(1, 1, 1, 1, 1, 850.00),
(2, 1, 1, 1, 2, 850.00),
(3, 1, 2, 1, 3, 180.00),
(4, 1, 3, 305, NULL, 0.45)
ON DUPLICATE KEY UPDATE `cantidad` = VALUES(`cantidad`);

-- Registro en Kardex (movimientos_inventario) correspondiente a la recepción inicial
INSERT INTO `movimientos_inventario` (
  `id_movimiento`,
  `id_articulo`,
  `id_motivo`,
  `tipo_movimiento`,
  `cantidad`,
  `existencia_anterior`,
  `existencia_nueva`,
  `id_doc_referencia`,
  `origen_referencia`,
  `fecha_movimiento`,
  `id_usuario`,
  `observacion`
) VALUES
(1, 1, NULL, 'ENTRADA', 2, 3, 5, 1, 'documentos_entrada', NOW(), 1, 'Recepción inicial según ENT-2026-0001'),
(2, 2, NULL, 'ENTRADA', 1, 7, 8, 1, 'documentos_entrada', NOW(), 1, 'Recepción inicial según ENT-2026-0001'),
(3, 3, NULL, 'ENTRADA', 305, 0, 305, 1, 'documentos_entrada', NOW(), 1, 'Recepción inicial de bobina de red')
ON DUPLICATE KEY UPDATE `observacion` = VALUES(`observacion`);

SET FOREIGN_KEY_CHECKS = 1;

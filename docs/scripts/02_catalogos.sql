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
-- 2. DATOS SEMILLA (SEEDS)
-- -----------------------------------------------------------------------------

-- Clasificaciones
INSERT INTO `clasificaciones` (`id_clasificacion`, `nombre_clasificacion`, `descripcion_clasif`, `estado_clasif`) VALUES
(1, 'Equipos de cómputo', 'Laptops, computadoras de escritorio, servidores y estaciones de trabajo', 1),
(2, 'Periféricos y accesorios', 'Monitores, teclados, mouse, diademas y cámaras web', 1),
(3, 'Redes y conectividad', 'Switches, routers, access points y cableado estructurado', 1),
(4, 'Impresión y escaneo', 'Impresoras térmicas, multifuncionales y escáneres', 1),
(5, 'Componentes y repuestos', 'Discos duros, memorias RAM, fuentes de poder y adaptadores', 1)
ON DUPLICATE KEY UPDATE `descripcion_clasif` = VALUES(`descripcion_clasif`);

-- Ubicaciones
INSERT INTO `ubicaciones` (`id_ubicacion`, `nombre_ubicacion`, `descripcion_ubi`, `estado_ubi`) VALUES
(1, 'Bodega Principal IT', 'Almacén general de suministros y equipos nuevos', 1),
(2, 'Taller de Soporte Técnico', 'Área de diagnóstico, preparación y mantenimiento', 1),
(3, 'Data Center / Sala de Servidores', 'Infraestructura central de servidores y enlaces de red', 1),
(4, 'Edificio Corporativo - Gerencia', 'Oficinas corporativas y estaciones activas', 1),
(5, 'Sucursal Tienda Central', 'Equipos asignados a punto de venta y operaciones', 1)
ON DUPLICATE KEY UPDATE `descripcion_ubi` = VALUES(`descripcion_ubi`);

-- Unidades de medida
INSERT INTO `unidades_medida` (`id_unidad_medida`, `nombre_unidad`, `simbolo_medida`, `estado_medida`) VALUES
(1, 'Unidad', 'UND', 1),
(2, 'Pieza', 'PZA', 1),
(3, 'Metro', 'MTR', 1),
(4, 'Caja', 'CJ', 1),
(5, 'Kit', 'KIT', 1)
ON DUPLICATE KEY UPDATE `simbolo_medida` = VALUES(`simbolo_medida`);

-- Tipos de documentos
INSERT INTO `tipos_documentos` (`id_tipo_doc`, `nombre_tipo`, `naturaleza_doc`, `estado_doc`) VALUES
(1, 'Recepción por compra', 'ENTRADA', 1),
(2, 'Ajuste positivo de inventario', 'ENTRADA', 1),
(3, 'Despacho para asignación', 'SALIDA', 1),
(4, 'Salida por baja o desecho', 'SALIDA', 1),
(5, 'Ajuste negativo de inventario', 'SALIDA', 1)
ON DUPLICATE KEY UPDATE `naturaleza_doc` = VALUES(`naturaleza_doc`);

-- Tipos de mantenimientos
INSERT INTO `tipos_mantenimientos` (`id_tipo_mantenimiento`, `nombre_tipo`, `descripcion_mant`, `estado_mant`) VALUES
(1, 'Preventivo programado', 'Limpieza interna, cambio de pasta térmica y revisión general', 1),
(2, 'Correctivo de hardware', 'Reemplazo o reparación de componentes defectuosos', 1),
(3, 'Correctivo de software', 'Reinstalación de sistema operativo, drivers o software corporativo', 1),
(4, 'Upgrade o ampliación', 'Incremento de memoria RAM, cambio a disco SSD o repotenciación', 1)
ON DUPLICATE KEY UPDATE `descripcion_mant` = VALUES(`descripcion_mant`);

-- Motivos de ajustes y anulaciones
INSERT INTO `motivos_ajustes_anulaciones` (`id_motivo`, `nombre_motivo`, `motivo_aplica_a`, `estado_motivo`) VALUES
(1, 'Diferencia en conteo físico', 'AJUSTE_ENTRADA', 1),
(2, 'Donación o ingreso no facturado', 'AJUSTE_ENTRADA', 1),
(3, 'Pérdida o extravío', 'AJUSTE_SALIDA', 1),
(4, 'Deterioro o rotura accidental', 'AJUSTE_SALIDA', 1),
(5, 'Error involuntario en digitación', 'ANULACION', 1),
(6, 'Obsolescencia tecnológica', 'BAJA', 1),
(7, 'Falla irreparable de componentes', 'BAJA', 1)
ON DUPLICATE KEY UPDATE `motivo_aplica_a` = VALUES(`motivo_aplica_a`);

-- =============================================================================
-- SISTEMA DE GESTION DE ACTIVOS IT - PINTURAS AMERICANAS
-- 06_operaciones.sql: Asignaciones, Devoluciones y Mantenimientos
-- Requiere: 01_security.sql, 02_catalogos.sql, 03_articulos.sql, 04_equipos.sql
-- =============================================================================

USE `activosit`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. ESTRUCTURA DE TABLAS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `asignaciones_devoluciones` (
  `id_asignacion` INT NOT NULL AUTO_INCREMENT,
  `id_equipo` INT NOT NULL,
  `persona_responsable` VARCHAR(150) NOT NULL,
  `departamento_destino` VARCHAR(150) NOT NULL,
  `id_usuario_entrega` INT NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL,
  `condicion_entrega` TEXT NULL,
  `fecha_devolucion` DATETIME NULL,
  `id_usuario_recibe` INT NULL,
  `condicion_devolucion` TEXT NULL,
  `estado_asignacion` ENUM('ACTIVA', 'DEVUELTO') NOT NULL DEFAULT 'ACTIVA',
  `observaciones` TEXT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asig_equipo` (`id_equipo`),
  KEY `idx_asig_usuario_entrega` (`id_usuario_entrega`),
  KEY `idx_asig_usuario_recibe` (`id_usuario_recibe`),
  CONSTRAINT `fk_asig_equipo`
    FOREIGN KEY (`id_equipo`) REFERENCES `equipos_individuales` (`id_equipo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asig_usuario_entrega`
    FOREIGN KEY (`id_usuario_entrega`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asig_usuario_recibe`
    FOREIGN KEY (`id_usuario_recibe`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `mantenimientos` (
  `id_mantenimiento` INT NOT NULL AUTO_INCREMENT,
  `id_equipo` INT NOT NULL,
  `id_tipo_mantenimiento` INT NOT NULL,
  `fecha_recepcion` DATETIME NOT NULL,
  `diagnostico_inicial` TEXT NULL,
  `trabajo_realizado` TEXT NULL,
  `tecnico_responsable` VARCHAR(150) NOT NULL,
  `repuestos_utilizados` TEXT NULL,
  `costo_mantenimiento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha_finalizacion` DATETIME NULL,
  `estado_mantenimiento` ENUM('EN_PROCESO', 'COMPLETADO', 'CANCELADO') NOT NULL DEFAULT 'EN_PROCESO',
  `id_usuario_registro` INT NOT NULL,
  PRIMARY KEY (`id_mantenimiento`),
  KEY `idx_mant_equipo` (`id_equipo`),
  KEY `idx_mant_tipo` (`id_tipo_mantenimiento`),
  KEY `idx_mant_usuario` (`id_usuario_registro`),
  CONSTRAINT `fk_mant_equipo`
    FOREIGN KEY (`id_equipo`) REFERENCES `equipos_individuales` (`id_equipo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mant_tipo`
    FOREIGN KEY (`id_tipo_mantenimiento`) REFERENCES `tipos_mantenimientos` (`id_tipo_mantenimiento`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_mant_usuario`
    FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. DATOS SEMILLA (SEEDS)
-- -----------------------------------------------------------------------------

-- Asignación de ejemplo inicial
INSERT INTO `asignaciones_devoluciones` (
  `id_asignacion`,
  `id_equipo`,
  `persona_responsable`,
  `departamento_destino`,
  `id_usuario_entrega`,
  `fecha_asignacion`,
  `condicion_entrega`,
  `estado_asignacion`,
  `observaciones`
) VALUES
(
  1,
  2, -- Laptop Dell Latitude 3420 (PA-IT-00102)
  'Lic. Carlos Rivera',
  'Gerencia de Ventas',
  1, -- Admin
  NOW(),
  'Equipo nuevo con cargador original y maletín.',
  'ACTIVA',
  'Asignación para labores gerenciales y comerciales.'
)
ON DUPLICATE KEY UPDATE `persona_responsable` = VALUES(`persona_responsable`);

-- Coherencia del inventario: un equipo con asignación activa no puede figurar
-- como DISPONIBLE en el catálogo de equipos.
UPDATE `equipos_individuales`
   SET `estado_operativo` = 'ASIGNADO'
 WHERE `id_equipo` = 2
   AND `estado_operativo` <> 'ASIGNADO';

-- Mantenimiento preventivo de ejemplo inicial
INSERT INTO `mantenimientos` (
  `id_mantenimiento`,
  `id_equipo`,
  `id_tipo_mantenimiento`,
  `fecha_recepcion`,
  `diagnostico_inicial`,
  `trabajo_realizado`,
  `tecnico_responsable`,
  `repuestos_utilizados`,
  `costo_mantenimiento`,
  `fecha_finalizacion`,
  `estado_mantenimiento`,
  `id_usuario_registro`
) VALUES
(
  1,
  3, -- Monitor Dell (PA-IT-00201)
  1, -- Preventivo programado
  NOW(),
  'Mantenimiento preventivo semestral e inspección de voltaje.',
  'Limpieza exterior e interna, verificación de panel LED y puertos.',
  'Técnico de Soporte',
  'Aire comprimido, alcohol isopropílico',
  0.00,
  NOW(),
  'COMPLETADO',
  2 -- Soporte
)
ON DUPLICATE KEY UPDATE `trabajo_realizado` = VALUES(`trabajo_realizado`);

SET FOREIGN_KEY_CHECKS = 1;

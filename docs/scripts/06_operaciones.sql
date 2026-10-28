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
  `id_departamento` INT NULL,
  `id_usuario_entrega` INT NOT NULL,
  `fecha_asignacion` DATETIME NOT NULL,
  `condicion_entrega` TEXT NULL,
  `accesorios_entregados` TEXT NULL,
  `fecha_devolucion` DATETIME NULL,
  `id_usuario_recibe` INT NULL,
  `condicion_devolucion` TEXT NULL,
  `estado_asignacion` ENUM('ACTIVA', 'DEVUELTO') NOT NULL DEFAULT 'ACTIVA',
  `observaciones` TEXT NULL,
  PRIMARY KEY (`id_asignacion`),
  KEY `idx_asig_equipo` (`id_equipo`),
  KEY `idx_asig_departamento` (`id_departamento`),
  KEY `idx_asig_usuario_entrega` (`id_usuario_entrega`),
  KEY `idx_asig_usuario_recibe` (`id_usuario_recibe`),
  CONSTRAINT `fk_asig_equipo`
    FOREIGN KEY (`id_equipo`) REFERENCES `equipos_individuales` (`id_equipo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_asig_departamento`
    FOREIGN KEY (`id_departamento`) REFERENCES `departamentos` (`id_departamento`)
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
  `resultado_mantenimiento` TEXT NULL,
  `recomendaciones` TEXT NULL,
  `tecnico_responsable` VARCHAR(150) NOT NULL,
  `repuestos_utilizados` TEXT NULL,
  `costo_mantenimiento` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha_finalizacion` DATETIME NULL,
  `estado_mantenimiento` ENUM('EN_PROCESO', 'COMPLETADO', 'CANCELADO') NOT NULL DEFAULT 'EN_PROCESO',
  `motivo_cancelacion` TEXT NULL,
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
-- 2. IMAGENES DEL ESTADO DE LOS EQUIPOS
-- -----------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `equipos_imagenes` (
  `id_imagen` INT NOT NULL AUTO_INCREMENT,
  `id_equipo` INT NOT NULL,
  `ruta_imagen` VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NULL,
  `tipo_mime` VARCHAR(50) NOT NULL,
  `tamano_bytes` INT NOT NULL DEFAULT 0,
  `descripcion` VARCHAR(255) NULL,
  `contexto` ENUM('REGISTRO', 'ESTADO', 'MANTENIMIENTO', 'ASIGNACION', 'DEVOLUCION', 'BAJA') NOT NULL DEFAULT 'ESTADO',
  `id_mantenimiento` INT NULL,
  `id_usuario_registro` INT NOT NULL,
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_imagen`),
  KEY `idx_img_equipo` (`id_equipo`),
  KEY `idx_img_mantenimiento` (`id_mantenimiento`),
  KEY `idx_img_usuario` (`id_usuario_registro`),
  CONSTRAINT `fk_img_equipo`
    FOREIGN KEY (`id_equipo`) REFERENCES `equipos_individuales` (`id_equipo`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_img_mantenimiento`
    FOREIGN KEY (`id_mantenimiento`) REFERENCES `mantenimientos` (`id_mantenimiento`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_img_usuario`
    FOREIGN KEY (`id_usuario_registro`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Sin datos semilla: las operaciones se registran desde la aplicación.

SET FOREIGN_KEY_CHECKS = 1;

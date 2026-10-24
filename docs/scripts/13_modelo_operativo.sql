-- ActivosIT - Migracion incremental del modelo operativo ampliado
-- Agrega datos de catalogo, mantenimiento y asignaciones sin modificar
-- existencias, documentos ni historiales existentes.

USE `activosit`;
SET NAMES utf8mb4;

ALTER TABLE `articulos`
  ADD COLUMN IF NOT EXISTS `marca_articulo` VARCHAR(100) NULL AFTER `descripcion_articulo`,
  ADD COLUMN IF NOT EXISTS `modelo_articulo` VARCHAR(150) NULL AFTER `marca_articulo`,
  ADD COLUMN IF NOT EXISTS `observaciones_articulo` TEXT NULL AFTER `modelo_articulo`;

ALTER TABLE `asignaciones_devoluciones`
  ADD COLUMN IF NOT EXISTS `accesorios_entregados` TEXT NULL AFTER `condicion_entrega`;

ALTER TABLE `mantenimientos`
  ADD COLUMN IF NOT EXISTS `resultado_mantenimiento` TEXT NULL AFTER `trabajo_realizado`,
  ADD COLUMN IF NOT EXISTS `recomendaciones` TEXT NULL AFTER `resultado_mantenimiento`,
  ADD COLUMN IF NOT EXISTS `motivo_cancelacion` TEXT NULL AFTER `estado_mantenimiento`;

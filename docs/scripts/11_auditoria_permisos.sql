-- ============================================================
-- ActivosIT - Migracion incremental de permisos de auditoria
-- Fecha: 2026-10-23
--
-- Este script asegura el permiso de menu y el permiso del controlador
-- de la Bitacora de Auditoria, y los asigna a los roles Administrador
-- y Auditor. Es idempotente: se puede ejecutar varias veces sin
-- duplicar registros.
--
-- No modifica usuarios, existencias, equipos, asignaciones,
-- movimientos ni eventos de la bitacora.
--
-- Uso:
--   mysql -h 127.0.0.1 -P 3306 -u root -p activosit < docs/scripts/12_auditoria_permisos.sql
--
-- Ajusta host, puerto, usuario y nombre de base segun parameters.env.
-- ============================================================

SET NAMES utf8mb4;

-- 1. Permisos de menu y controlador.
--    La tabla permisos tiene una clave unica por nombre_permiso, por lo
--    que ON DUPLICATE KEY UPDATE actualiza los existentes sin cambiar
--    sus identificadores.
INSERT INTO `permisos`
  (`nombre_permiso`, `descripcion_permiso`, `modulo_permiso`, `tipo_permiso`, `estado_permiso`)
VALUES
  ('Menu_Auditoria',                    'Menú: auditoría',                 'Auditoría', 'MNU', 1),
  ('Controllers\\Auditoria\\Auditoria', 'Bitácora de auditoría (consulta)', 'Auditoría', 'CTR', 1)
ON DUPLICATE KEY UPDATE
  `descripcion_permiso` = VALUES(`descripcion_permiso`),
  `modulo_permiso`      = VALUES(`modulo_permiso`),
  `tipo_permiso`        = VALUES(`tipo_permiso`),
  `estado_permiso`      = VALUES(`estado_permiso`);

-- 2. Asignacion de permisos a los roles con acceso a auditoria.
--    El Técnico de Soporte no recibe acceso a la bitacora.
--    Administrador tiene acceso global, pero se incluye para que el
--    script deje el mismo estado que la instalacion completa.
INSERT IGNORE INTO `roles_permisos` (`id_rol`, `id_permiso`)
SELECT r.`id_rol`, p.`id_permiso`
FROM `roles` r
JOIN `permisos` p
  ON p.`nombre_permiso` IN (
    'Menu_Auditoria',
    'Controllers\\Auditoria\\Auditoria'
  )
WHERE r.`nombre_rol` IN ('Administrador', 'Auditor');

-- 3. Verificacion de permisos.
SELECT p.`id_permiso`, p.`nombre_permiso`, p.`modulo_permiso`, p.`tipo_permiso`, p.`estado_permiso`
FROM `permisos` p
WHERE p.`nombre_permiso` IN (
  'Menu_Auditoria',
  'Controllers\\Auditoria\\Auditoria'
)
ORDER BY p.`id_permiso`;

-- 4. Verificacion de asignaciones por rol.
SELECT r.`nombre_rol`, p.`nombre_permiso`
FROM `roles_permisos` rp
JOIN `roles` r ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p ON p.`id_permiso` = rp.`id_permiso`
WHERE p.`nombre_permiso` IN (
  'Menu_Auditoria',
  'Controllers\\Auditoria\\Auditoria'
)
ORDER BY r.`id_rol`, p.`id_permiso`;

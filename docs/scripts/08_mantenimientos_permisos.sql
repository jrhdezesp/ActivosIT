-- ============================================================
-- ActivosIT - Migracion incremental de permisos de mantenimientos
-- Fecha: 2026-10-23
--
-- Este script asegura el permiso de menu y los permisos de los
-- controladores del modulo de Mantenimientos. Es idempotente: se
-- puede ejecutar varias veces sin duplicar registros.
--
-- No modifica equipos, mantenimientos ni existencias.
--
-- Uso:
--   mysql -h 127.0.0.1 -P 3306 -u root -p activosit < docs/scripts/09_mantenimientos_permisos.sql
--
-- Ajusta host, puerto, usuario y nombre de base segun parameters.env.
-- ============================================================

SET NAMES utf8mb4;

-- 1. Permisos de menu y controladores.
--    La tabla permisos tiene una clave unica por nombre_permiso, por lo
--    que ON DUPLICATE KEY UPDATE actualiza los existentes sin cambiar
--    sus identificadores.
INSERT INTO `permisos`
  (`nombre_permiso`, `descripcion_permiso`, `modulo_permiso`, `tipo_permiso`, `estado_permiso`)
VALUES
  ('Menu_Mantenimientos',                       'Menú: mantenimientos de equipos',      'Inventario', 'MNU', 1),
  ('Controllers\\Inventario\\Mantenimientos',  'Mantenimientos de equipos (listado)',  'Inventario', 'CTR', 1),
  ('Controllers\\Inventario\\Mantenimiento',   'Formulario de mantenimiento',          'Inventario', 'CTR', 1)
ON DUPLICATE KEY UPDATE
  `descripcion_permiso` = VALUES(`descripcion_permiso`),
  `modulo_permiso`      = VALUES(`modulo_permiso`),
  `tipo_permiso`        = VALUES(`tipo_permiso`),
  `estado_permiso`      = VALUES(`estado_permiso`);

-- 2. Asignacion de permisos a los roles operativos.
--    Administrador y Soporte pueden consultar y operar el modulo.
--    Auditor es un rol de consulta: solo recibe el menu y el listado.
INSERT IGNORE INTO `roles_permisos` (`id_rol`, `id_permiso`)
SELECT r.`id_rol`, p.`id_permiso`
FROM `roles` r
JOIN `permisos` p
  ON p.`nombre_permiso` IN (
    'Menu_Mantenimientos',
    'Controllers\\Inventario\\Mantenimientos'
  )
WHERE r.`nombre_rol` IN ('Administrador', 'Auditor', 'Tecnico de Soporte', 'Técnico de Soporte');

INSERT IGNORE INTO `roles_permisos` (`id_rol`, `id_permiso`)
SELECT r.`id_rol`, p.`id_permiso`
FROM `roles` r
JOIN `permisos` p
  ON p.`nombre_permiso` = 'Controllers\\Inventario\\Mantenimiento'
WHERE r.`nombre_rol` IN ('Administrador', 'Tecnico de Soporte', 'Técnico de Soporte');

-- 3. El rol Auditor no debe operar el formulario. Si una version
--    anterior del script le asigno ese permiso, se retira aqui para
--    que el script deje siempre el mismo estado (idempotente).
DELETE rp
FROM `roles_permisos` rp
JOIN `roles` r ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p ON p.`id_permiso` = rp.`id_permiso`
WHERE r.`nombre_rol` = 'Auditor'
  AND p.`nombre_permiso` = 'Controllers\\Inventario\\Mantenimiento';

-- 4. Verificacion de permisos.
SELECT p.`id_permiso`, p.`nombre_permiso`, p.`modulo_permiso`, p.`tipo_permiso`, p.`estado_permiso`
FROM `permisos` p
WHERE p.`nombre_permiso` IN (
  'Menu_Mantenimientos',
  'Controllers\\Inventario\\Mantenimientos',
  'Controllers\\Inventario\\Mantenimiento'
)
ORDER BY p.`id_permiso`;

-- 5. Verificacion de asignaciones por rol.
SELECT r.`nombre_rol`, p.`nombre_permiso`
FROM `roles_permisos` rp
JOIN `roles` r ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p ON p.`id_permiso` = rp.`id_permiso`
WHERE p.`nombre_permiso` IN (
  'Menu_Mantenimientos',
  'Controllers\\Inventario\\Mantenimientos',
  'Controllers\\Inventario\\Mantenimiento'
)
ORDER BY r.`id_rol`, p.`id_permiso`;

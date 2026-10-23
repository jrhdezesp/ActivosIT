-- ============================================================
-- ActivosIT - Migracion incremental de permisos de inventario
-- Fecha: 2026-10-23
--
-- Este script asegura los permisos de menu y controladores de los
-- modulos de Equipos, Entradas y Salidas. Es idempotente: se puede
-- ejecutar varias veces sin duplicar registros.
--
-- No modifica existencias, documentos, equipos ni movimientos.
--
-- Uso:
--   mysql -h 127.0.0.1 -P 3307 -u root -p activosit < docs/scripts/07_inventario_permisos.sql
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
  ('Menu_Equipos',                     'Menú: equipos',                        'Activos',    'MNU', 1),
  ('Controllers\\Equipos\\Equipos',    'Equipos individuales (listado)',       'Activos',    'CTR', 1),
  ('Controllers\\Equipos\\Equipo',     'Formulario de equipo',                 'Activos',    'CTR', 1),
  ('Menu_Entradas',                    'Menú: entradas de inventario',         'Inventario', 'MNU', 1),
  ('Controllers\\Inventario\\Entradas','Entradas de inventario (listado)',     'Inventario', 'CTR', 1),
  ('Controllers\\Inventario\\Entrada', 'Formulario de entrada',                'Inventario', 'CTR', 1),
  ('Menu_Salidas',                     'Menú: salidas de inventario',          'Inventario', 'MNU', 1),
  ('Controllers\\Inventario\\Salidas', 'Salidas de inventario (listado)',      'Inventario', 'CTR', 1),
  ('Controllers\\Inventario\\Salida',  'Formulario de salida',                 'Inventario', 'CTR', 1)
ON DUPLICATE KEY UPDATE
  `descripcion_permiso` = VALUES(`descripcion_permiso`),
  `modulo_permiso`      = VALUES(`modulo_permiso`),
  `tipo_permiso`        = VALUES(`tipo_permiso`),
  `estado_permiso`      = VALUES(`estado_permiso`);

-- 2. Asignacion de permisos a los roles operativos.
--    Administrador tiene acceso global, pero se incluye para que el
--    script deje el mismo estado que la instalacion completa.
INSERT IGNORE INTO `roles_permisos` (`id_rol`, `id_permiso`)
SELECT r.`id_rol`, p.`id_permiso`
FROM `roles` r
JOIN `permisos` p
  ON p.`nombre_permiso` IN (
    'Menu_Equipos',
    'Controllers\\Equipos\\Equipos',
    'Controllers\\Equipos\\Equipo',
    'Menu_Entradas',
    'Controllers\\Inventario\\Entradas',
    'Controllers\\Inventario\\Entrada',
    'Menu_Salidas',
    'Controllers\\Inventario\\Salidas',
    'Controllers\\Inventario\\Salida'
  )
WHERE r.`nombre_rol` IN ('Administrador', 'Auditor', 'Tecnico de Soporte', 'Técnico de Soporte');

-- 3. Verificacion de permisos.
SELECT p.`id_permiso`, p.`nombre_permiso`, p.`modulo_permiso`, p.`tipo_permiso`, p.`estado_permiso`
FROM `permisos` p
WHERE p.`nombre_permiso` IN (
  'Menu_Equipos',
  'Controllers\\Equipos\\Equipos',
  'Controllers\\Equipos\\Equipo',
  'Menu_Entradas',
  'Controllers\\Inventario\\Entradas',
  'Controllers\\Inventario\\Entrada',
  'Menu_Salidas',
  'Controllers\\Inventario\\Salidas',
  'Controllers\\Inventario\\Salida'
)
ORDER BY p.`id_permiso`;

-- 4. Verificacion de asignaciones por rol.
SELECT r.`nombre_rol`, p.`nombre_permiso`
FROM `roles_permisos` rp
JOIN `roles` r ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p ON p.`id_permiso` = rp.`id_permiso`
WHERE p.`nombre_permiso` IN (
  'Menu_Equipos',
  'Controllers\\Equipos\\Equipos',
  'Controllers\\Equipos\\Equipo',
  'Menu_Entradas',
  'Controllers\\Inventario\\Entradas',
  'Controllers\\Inventario\\Entrada',
  'Menu_Salidas',
  'Controllers\\Inventario\\Salidas',
  'Controllers\\Inventario\\Salida'
)
ORDER BY r.`id_rol`, p.`id_permiso`;

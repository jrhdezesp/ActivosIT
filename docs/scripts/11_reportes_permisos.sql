-- ============================================================
-- ActivosIT - Migracion incremental de permisos de reportes
-- Fecha: 2026-10-23
--
-- Este script asegura los permisos de menu y los permisos de los
-- controladores del Centro de Reportes, Kardex y Equipos por Custodio.
-- Es idempotente: se puede ejecutar varias veces sin duplicar registros.
--
-- No modifica existencias, equipos, asignaciones ni movimientos.
--
-- Uso:
--   mysql -h 127.0.0.1 -P 3306 -u root -p activosit < docs/scripts/11_reportes_permisos.sql
--
-- Ajusta host, puerto, usuario y nombre de base segun parameters.env.
-- ============================================================

SET NAMES utf8mb4;

-- 1. Permisos de menu y controladores.
--    Los permisos de menu ya existen en la instalacion base y conservan
--    su identificador. La tabla permisos tiene una clave unica por
--    nombre_permiso, por lo que ON DUPLICATE KEY UPDATE evita duplicados.
INSERT INTO `permisos`
  (`nombre_permiso`, `descripcion_permiso`, `modulo_permiso`, `tipo_permiso`, `estado_permiso`)
VALUES
  ('Menu_CentroReportes',                        'Menú: centro de reportes',             'Reportes', 'MNU', 1),
  ('Menu_Kardex',                                'Menú: Kardex de movimientos',          'Reportes', 'MNU', 1),
  ('Menu_EquiposCustodio',                       'Menú: equipos por custodio',           'Reportes', 'MNU', 1),
  ('Controllers\\Reportes\\Reportes',            'Centro de reportes (indicadores)',     'Reportes', 'CTR', 1),
  ('Controllers\\Reportes\\Kardex',              'Kardex de movimientos (reporte)',      'Reportes', 'CTR', 1),
  ('Controllers\\Reportes\\EquiposCustodio',     'Equipos por custodio (reporte)',       'Reportes', 'CTR', 1)
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
    'Menu_CentroReportes',
    'Menu_Kardex',
    'Menu_EquiposCustodio',
    'Controllers\\Reportes\\Reportes',
    'Controllers\\Reportes\\Kardex',
    'Controllers\\Reportes\\EquiposCustodio'
  )
WHERE r.`nombre_rol` IN ('Administrador', 'Auditor', 'Tecnico de Soporte', 'Técnico de Soporte');

-- 3. Verificacion de permisos.
SELECT p.`id_permiso`, p.`nombre_permiso`, p.`modulo_permiso`, p.`tipo_permiso`, p.`estado_permiso`
FROM `permisos` p
WHERE p.`nombre_permiso` IN (
  'Menu_CentroReportes',
  'Menu_Kardex',
  'Menu_EquiposCustodio',
  'Controllers\\Reportes\\Reportes',
  'Controllers\\Reportes\\Kardex',
  'Controllers\\Reportes\\EquiposCustodio'
)
ORDER BY p.`id_permiso`;

-- 4. Verificacion de asignaciones por rol.
SELECT r.`nombre_rol`, p.`nombre_permiso`
FROM `roles_permisos` rp
JOIN `roles` r ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p ON p.`id_permiso` = rp.`id_permiso`
WHERE p.`nombre_permiso` IN (
  'Menu_CentroReportes',
  'Menu_Kardex',
  'Menu_EquiposCustodio',
  'Controllers\\Reportes\\Reportes',
  'Controllers\\Reportes\\Kardex',
  'Controllers\\Reportes\\EquiposCustodio'
)
ORDER BY r.`id_rol`, p.`id_permiso`;

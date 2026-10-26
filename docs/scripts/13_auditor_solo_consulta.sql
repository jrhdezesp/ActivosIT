.-- ============================================================
-- ActivosIT - Correccion de permisos del rol Auditor
-- Fecha: 2026-10-26
--
-- El rol Auditor esta definido como consulta: puede ver listados,
-- reportes, kardex y la bitacora, pero no debe operar formularios.
-- Los scripts 01 y 07 le asignaban tambien los formularios de
-- Articulo, Equipo, Entrada y Salida. Este script revoca esos cuatro
-- permisos de controlador y deja intactos los permisos de menu y de
-- listado.
--
-- Es idempotente: se puede ejecutar varias veces sin efectos
-- adicionales.
--
-- No modifica usuarios, existencias, equipos, documentos,
-- movimientos, asignaciones, mantenimientos ni eventos de bitacora.
--
-- Uso:
--   mysql -h 127.0.0.1 -P 3306 -u root -p activosit < docs/scripts/14_auditor_solo_consulta.sql
--
-- Ajusta host, puerto, usuario y nombre de base segun parameters.env.
-- ============================================================

USE `activosit`;
SET NAMES utf8mb4;

-- 1. Revocar los permisos de formulario del rol Auditor.
--    Se conservan los permisos de listado:
--      Controllers\Articulos\Articulos
--      Controllers\Equipos\Equipos
--      Controllers\Inventario\Entradas
--      Controllers\Inventario\Salidas
DELETE rp
FROM `roles_permisos` rp
JOIN `roles` r
  ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p
  ON p.`id_permiso` = rp.`id_permiso`
WHERE r.`nombre_rol` = 'Auditor'
  AND p.`nombre_permiso` IN (
    'Controllers\\Articulos\\Articulo',
    'Controllers\\Equipos\\Equipo',
    'Controllers\\Inventario\\Entrada',
    'Controllers\\Inventario\\Salida'
  );

-- 2. Verificacion: permisos de controlador que conserva el Auditor.
SELECT p.`id_permiso`, p.`nombre_permiso`, p.`descripcion_permiso`
FROM `roles_permisos` rp
JOIN `roles` r
  ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p
  ON p.`id_permiso` = rp.`id_permiso`
WHERE r.`nombre_rol` = 'Auditor'
  AND p.`tipo_permiso` = 'CTR'
ORDER BY p.`id_permiso`;

-- 3. Verificacion: el Auditor no debe conservar ningun formulario.
SELECT COUNT(*) AS `formularios_auditor`
FROM `roles_permisos` rp
JOIN `roles` r
  ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p
  ON p.`id_permiso` = rp.`id_permiso`
WHERE r.`nombre_rol` = 'Auditor'
  AND p.`nombre_permiso` IN (
    'Controllers\\Articulos\\Articulo',
    'Controllers\\Equipos\\Equipo',
    'Controllers\\Inventario\\Entrada',
    'Controllers\\Inventario\\Salida'
  );

-- 4. Verificacion: Administrador y Tecnico de Soporte conservan los
--    formularios operativos.
SELECT r.`nombre_rol`, COUNT(*) AS `formularios_operativos`
FROM `roles_permisos` rp
JOIN `roles` r
  ON r.`id_rol` = rp.`id_rol`
JOIN `permisos` p
  ON p.`id_permiso` = rp.`id_permiso`
WHERE r.`nombre_rol` IN ('Administrador', 'Tecnico de Soporte', 'Técnico de Soporte')
  AND p.`nombre_permiso` IN (
    'Controllers\\Articulos\\Articulo',
    'Controllers\\Equipos\\Equipo',
    'Controllers\\Inventario\\Entrada',
    'Controllers\\Inventario\\Salida'
  )
GROUP BY r.`nombre_rol`
ORDER BY r.`id_rol`;

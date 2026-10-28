-- ============================================================
-- ActivosIT - Alta inicial de administrador
--
-- Este script no forma parte del esquema base. Se ejecuta una sola vez
-- despues de instalar 00-06 y despues de generar el hash de la clave
-- con scripts/create_admin.php o con el comando documentado.
--
-- No contiene credenciales reales. Reemplace los valores marcados.
-- ============================================================

USE `activosit`;
SET NAMES utf8mb4;

SET @admin_email = 'admin@tu-dominio.local';
SET @admin_hash = 'PEGAR_AQUI_EL_HASH';
SET @admin_nombre = 'Administrador';

START TRANSACTION;

INSERT INTO `usuarios`
(
    `user_usuario`,
    `clave_usuario`,
    `nombre_usuario`,
    `tipo_usuario`,
    `estado_usuario`
)
VALUES
(
    @admin_email,
    @admin_hash,
    @admin_nombre,
    'ADM',
    1
)
ON DUPLICATE KEY UPDATE
    `clave_usuario` = VALUES(`clave_usuario`),
    `nombre_usuario` = VALUES(`nombre_usuario`),
    `tipo_usuario` = 'ADM',
    `estado_usuario` = 1;

SET @admin_id = (
    SELECT `id_usuario`
    FROM `usuarios`
    WHERE `user_usuario` = @admin_email COLLATE utf8mb4_unicode_ci
    LIMIT 1
);

INSERT IGNORE INTO `usuarios_roles` (`id_usuario`, `id_rol`)
SELECT @admin_id, `id_rol`
FROM `roles`
WHERE `nombre_rol` = 'Administrador'
LIMIT 1;

COMMIT;

SELECT
    u.`id_usuario`,
    u.`user_usuario`,
    u.`nombre_usuario`,
    u.`tipo_usuario`,
    u.`estado_usuario`,
    r.`id_rol`,
    r.`nombre_rol`
FROM `usuarios` u
LEFT JOIN `usuarios_roles` ur ON ur.`id_usuario` = u.`id_usuario`
LEFT JOIN `roles` r ON r.`id_rol` = ur.`id_rol`
WHERE u.`user_usuario` = @admin_email COLLATE utf8mb4_unicode_ci;

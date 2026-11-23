-- ==========================================================
--  Usuarios con PRIVILEGIOS MÍNIMOS (NO se usa root / superusuario)
--  Ejecutar UNA vez como root en MySQL Workbench, DESPUÉS de 2fa.sql
-- ==========================================================

-- 1) Usuario que usa la aplicación web (el que está en clases/mysql.inc.php)
CREATE USER IF NOT EXISTS 'app_web'@'localhost' IDENTIFIED BY 'App_Web#2026';

GRANT SELECT, INSERT, UPDATE ON parcial_2fa.usuarios        TO 'app_web'@'localhost';
GRANT SELECT, INSERT         ON parcial_2fa.intentos_login  TO 'app_web'@'localhost';

-- 2) Usuario solo de consulta (reportes / auditoría)
CREATE USER IF NOT EXISTS 'app_reportes'@'localhost' IDENTIFIED BY 'App_Rep#2026';

GRANT SELECT ON parcial_2fa.intentos_login TO 'app_reportes'@'localhost';

FLUSH PRIVILEGES;

-- 3) COMANDO QUE MUESTRA LOS PRIVILEGIOS (tómale captura para la evaluación)
SHOW GRANTS FOR 'app_web'@'localhost';
SHOW GRANTS FOR 'app_reportes'@'localhost';

-- Opcional: ver todos los usuarios creados
-- SELECT User, Host FROM mysql.user WHERE User LIKE 'app_%';

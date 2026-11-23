-- Esquema completo (instalación desde cero). Ejecutar en phpMyAdmin como root.
CREATE DATABASE IF NOT EXISTS parcial_2fa CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE parcial_2fa;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`              INT AUTO_INCREMENT PRIMARY KEY,
  `Nombre`          VARCHAR(80)  NOT NULL,
  `Apellido`        VARCHAR(80)  NOT NULL,
  `Usuario`         VARCHAR(150) NOT NULL UNIQUE,
  `Correo`          VARCHAR(150) NOT NULL UNIQUE,
  `HashMagic`       VARCHAR(255) NOT NULL,          -- contraseña con password_hash()
  `secret_2fa`      VARCHAR(255) NULL,              -- se llena SOLO al activar 2FA
  `Sexo`            ENUM('M','F') NOT NULL,
  `FechaDelSistema` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `intentos_login` (
  `id`                 INT AUTO_INCREMENT PRIMARY KEY,
  `Usuario`            VARCHAR(150) NOT NULL,
  `ipRemoto`           VARCHAR(45)  NOT NULL,
  `deteccion_anomalia` TINYINT      NOT NULL DEFAULT 0,
  `fecha`              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

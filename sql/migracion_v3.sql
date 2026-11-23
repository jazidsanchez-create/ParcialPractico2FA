-- Para quien YA tiene la tabla usuarios de la versión anterior (sin Correo ni FechaDelSistema).
USE parcial_2fa;

ALTER TABLE `usuarios`
  ADD `Correo` VARCHAR(150) NULL AFTER `Usuario`,
  ADD `FechaDelSistema` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- los usuarios que ya existían usaban el correo como usuario
UPDATE `usuarios` SET `Correo` = `Usuario` WHERE `Correo` IS NULL;

ALTER TABLE `usuarios`
  MODIFY `Correo` VARCHAR(150) NOT NULL,
  ADD UNIQUE KEY `uq_correo` (`Correo`);

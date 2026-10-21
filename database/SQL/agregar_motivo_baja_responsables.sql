-- Ejecutar una sola vez en la base de datos correspondiente.
-- Guarda el motivo de la baja vigente de la relación persona-empresa.
ALTER TABLE `responsables`
ADD COLUMN `motivo_baja` TEXT NULL AFTER `fecha_baja`;

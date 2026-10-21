-- Actualiza una base existente para que funcionarios use "ci" en lugar de "carnet".
ALTER TABLE `funcionarios`
    CHANGE COLUMN `carnet` `ci` VARCHAR(50) NOT NULL,
    DROP INDEX `funcionarios_carnet_unique`,
    ADD UNIQUE INDEX `funcionarios_ci_unique` (`ci`);

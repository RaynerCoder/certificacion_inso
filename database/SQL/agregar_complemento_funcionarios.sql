ALTER TABLE `funcionarios`
    DROP INDEX `funcionarios_ci_unique`,
    ADD COLUMN `complemento` VARCHAR(10) NOT NULL DEFAULT '' AFTER `ci`,
    ADD UNIQUE INDEX `funcionarios_ci_complemento_unique` (`ci`, `complemento`);

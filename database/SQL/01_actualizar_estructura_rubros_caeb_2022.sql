-- ARCHIVO 1 DE 2: ACTUALIZACION DE ESTRUCTURA DE RUBROS
--
-- IMPORTANTE:
--   1. Seleccione primero su base de datos en phpMyAdmin.
--   2. Importe este archivo dentro de esa base.
--   3. No utiliza USE, information_schema ni PREPARE.
--
-- Puede ejecutarse aunque el intento anterior haya agregado parcialmente
-- columnas o indices. No elimina registros, no cambia ids, no modifica
-- personas_rubros y no reinicia AUTO_INCREMENT.

SET NAMES utf8mb4;

DROP PROCEDURE IF EXISTS `actualizar_estructura_rubros_caeb_2022`;

DELIMITER $$

CREATE PROCEDURE `actualizar_estructura_rubros_caeb_2022`()
BEGIN
    -- Si la columna ya existe, MySQL genera 1060 y se continua normalmente.
    BEGIN
        DECLARE CONTINUE HANDLER FOR 1060 SET @caeb_id_rubro_padre_existia = 1;

        ALTER TABLE `rubros`
            ADD COLUMN `id_rubro_padre` BIGINT UNSIGNED NULL AFTER `id`;
    END;

    BEGIN
        DECLARE CONTINUE HANDLER FOR 1060 SET @caeb_codigo_existia = 1;

        ALTER TABLE `rubros`
            ADD COLUMN `codigo_caeb` VARCHAR(5) NULL AFTER `id_rubro_padre`;
    END;

    -- Convierte una posible version CHAR(5) anterior sin perder codigos.
    ALTER TABLE `rubros`
        MODIFY COLUMN `codigo_caeb` VARCHAR(5) NULL;

    BEGIN
        DECLARE CONTINUE HANDLER FOR 1060 SET @caeb_nivel_existia = 1;

        ALTER TABLE `rubros`
            ADD COLUMN `nivel_caeb` VARCHAR(20) NULL AFTER `codigo_caeb`;
    END;

    BEGIN
        DECLARE CONTINUE HANDLER FOR 1061 SET @caeb_indice_codigo_existia = 1;

        ALTER TABLE `rubros`
            ADD UNIQUE KEY `rubros_codigo_caeb_unique` (`codigo_caeb`);
    END;

    BEGIN
        DECLARE CONTINUE HANDLER FOR 1061 SET @caeb_indice_nivel_existia = 1;

        ALTER TABLE `rubros`
            ADD KEY `rubros_nivel_caeb_index` (`nivel_caeb`);
    END;

    -- 1826: nombre de restriccion duplicado en MySQL.
    -- 1005: restriccion equivalente ya existente en algunas versiones MariaDB.
    BEGIN
        DECLARE CONTINUE HANDLER FOR 1826 SET @caeb_clave_padre_existia = 1;
        DECLARE CONTINUE HANDLER FOR 1005 SET @caeb_clave_padre_existia = 1;

        ALTER TABLE `rubros`
            ADD CONSTRAINT `rubros_id_rubro_padre_foreign`
            FOREIGN KEY (`id_rubro_padre`)
            REFERENCES `rubros` (`id`)
            ON UPDATE CASCADE
            ON DELETE SET NULL;
    END;
END$$

DELIMITER ;

CALL `actualizar_estructura_rubros_caeb_2022`();
DROP PROCEDURE `actualizar_estructura_rubros_caeb_2022`;

-- Control visual: deben aparecer id_rubro_padre, codigo_caeb y nivel_caeb.
SHOW COLUMNS FROM `rubros`;

-- Control visual: deben aparecer los indices de codigo, nivel y padre.
SHOW INDEX FROM `rubros`;

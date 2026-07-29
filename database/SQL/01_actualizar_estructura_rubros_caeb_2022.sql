-- ARCHIVO 1 DE 2: ACTUALIZACION DE ESTRUCTURA DE RUBROS
-- No elimina datos, no cambia ids y no reinicia AUTO_INCREMENT.
-- RUBROS: ESTRUCTURA JERARQUICA PARA CAEB-2022
-- Este archivo transforma la tabla rubros existente sin eliminar registros,
-- relaciones ni ids. Ejecutarlo antes del archivo 02 de datos.
--
-- Conserva el nombre de las tablas `rubros` y `personas_rubros`.
-- No elimina registros, no cambia ids y no reinicia AUTO_INCREMENT.
--
-- Campos incorporados en `rubros`:
--   * id_rubro_padre: id del nivel CAEB inmediatamente superior.
--   * codigo_caeb: codigo oficial de longitud 1 a 5.
--   * nivel_caeb: SECCION, DIVISION, GRUPO, CLASE o SUBCLASE.

USE `sistema_certificador_inso`;
SET NAMES utf8mb4;

SET @base_actual = DATABASE();

SELECT COUNT(*) INTO @existe_id_rubro_padre
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND COLUMN_NAME = 'id_rubro_padre';

SET @sql = IF(
    @existe_id_rubro_padre = 0,
    'ALTER TABLE `rubros` ADD COLUMN `id_rubro_padre` BIGINT UNSIGNED NULL AFTER `id`',
    'SELECT ''La columna id_rubro_padre ya existe'' AS aviso'
);
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SELECT COUNT(*) INTO @existe_codigo_caeb
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND COLUMN_NAME = 'codigo_caeb';

SET @sql = IF(
    @existe_codigo_caeb = 0,
    'ALTER TABLE `rubros` ADD COLUMN `codigo_caeb` VARCHAR(5) NULL AFTER `id_rubro_padre`',
    'ALTER TABLE `rubros` MODIFY COLUMN `codigo_caeb` VARCHAR(5) NULL'
);
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SELECT COUNT(*) INTO @existe_nivel_caeb
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND COLUMN_NAME = 'nivel_caeb';

SET @sql = IF(
    @existe_nivel_caeb = 0,
    'ALTER TABLE `rubros` ADD COLUMN `nivel_caeb` VARCHAR(20) NULL AFTER `codigo_caeb`',
    'SELECT ''La columna nivel_caeb ya existe'' AS aviso'
);
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SELECT COUNT(*) INTO @existe_indice_codigo
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND INDEX_NAME = 'rubros_codigo_caeb_unique';

SET @sql = IF(
    @existe_indice_codigo = 0,
    'ALTER TABLE `rubros` ADD UNIQUE KEY `rubros_codigo_caeb_unique` (`codigo_caeb`)',
    'SELECT ''El indice unico de codigo_caeb ya existe'' AS aviso'
);
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SELECT COUNT(*) INTO @existe_indice_nivel
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND INDEX_NAME = 'rubros_nivel_caeb_index';

SET @sql = IF(
    @existe_indice_nivel = 0,
    'ALTER TABLE `rubros` ADD KEY `rubros_nivel_caeb_index` (`nivel_caeb`)',
    'SELECT ''El indice de nivel_caeb ya existe'' AS aviso'
);
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SELECT COUNT(*) INTO @existe_clave_padre
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND COLUMN_NAME = 'id_rubro_padre'
  AND REFERENCED_TABLE_NAME = 'rubros';

SET @sql = IF(
    @existe_clave_padre = 0,
    'ALTER TABLE `rubros` ADD CONSTRAINT `rubros_id_rubro_padre_foreign` FOREIGN KEY (`id_rubro_padre`) REFERENCES `rubros` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
    'SELECT ''La relacion jerarquica de rubros ya existe'' AS aviso'
);
PREPARE sentencia FROM @sql;
EXECUTE sentencia;
DEALLOCATE PREPARE sentencia;

SELECT
    COLUMN_NAME AS campo,
    COLUMN_TYPE AS tipo,
    IS_NULLABLE AS permite_null,
    COLUMN_KEY AS clave
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = @base_actual
  AND TABLE_NAME = 'rubros'
  AND COLUMN_NAME IN ('id', 'id_rubro_padre', 'codigo_caeb', 'nivel_caeb', 'nombre')
ORDER BY ORDINAL_POSITION;

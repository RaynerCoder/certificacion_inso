SET @password := '$2y$10$HkDkN5TgeMT.OVzANVUAeezHhmQvKGioe4c/LUtbX2RA314j2q1ke';

INSERT INTO users (id, name, email, password, estado, created_at, updated_at) VALUES
(4, 'rhuanca', 'rhuanca@inso.gob.bo', @password, 1, NOW(), NOW()),
(5, 'mmunoz', 'mmunoz@inso.gob.bo', @password, 1, NOW(), NOW()),
(6, 'fsantos', 'fsantos@inso.gob.bo', @password, 1, NOW(), NOW()),
(7, 'dlaruta', 'dlaruta@inso.gob.bo', @password, 1, NOW(), NOW()),
(10, 'elluito', 'elluito@inso.gob.bo', @password, 1, NOW(), NOW()),
(11, 'abustillos', 'abustillos@inso.gob.bo', @password, 1, NOW(), NOW()),
(12, 'gzamora', 'gzamora@inso.gob.bo', @password, 1, NOW(), NOW()),
(13, 'asoria', 'asoria@inso.gob.bo', @password, 1, NOW(), NOW()),
(14, 'aale', 'aale@inso.gob.bo', @password, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), email = VALUES(email), password = VALUES(password), estado = VALUES(estado), updated_at = NOW();

INSERT INTO cargos (id, nombre, descripcion, area, estado, created_at, updated_at, deleted_at) VALUES
(4, 'Jefe de Unidad UTHSI', 'Jefe para la Unidad Tecnica de Higiene y Seguridad Industrial del INSO, gestion 2026.', 'UNIDAD TECNICA DE HIGIENE SEGURIDAD INDUSTRIAL', 1, NOW(), NOW(), NULL),
(8, 'Quimico UTHSI', 'Funcionario tecnico quimico de UTHSI.', 'AREA DE LABORATORIO DE QUIMICA', 1, NOW(), NOW(), NULL),
(9, 'Quimico UTHSI II', 'Funcionario tecnico quimico de UTHSI II.', 'AREA DE LABORATORIO DE QUIMICA', 1, NOW(), NOW(), NULL),
(10, 'Quimico UTHSI III', 'Funcionario tecnico quimico de UTHSI III.', 'AREA DE LABORATORIO DE QUIMICA', 1, NOW(), NOW(), NULL),
(11, 'Ingeniero UTHSI', 'Funcionario tecnico de ingenieria UTHSI.', 'AREA DE INGENIERIA', 1, NOW(), NOW(), NULL),
(12, 'Secretaria UTHSI', 'Apoyo administrativo de la Unidad Tecnica de Higiene y Seguridad Industrial.', 'UNIDAD TECNICA DE HIGIENE SEGURIDAD INDUSTRIAL', 1, NOW(), NOW(), NULL),
(13, 'Ingeniero Industrial UTHSI', 'Funcionario de ingenieria industrial UTHSI.', 'AREA DE INGENIERIA', 1, NOW(), NOW(), NULL),
(14, 'Secretaria del INSO', 'Apoyo administrativo de Direccion General Ejecutiva.', 'DIRECCION GENERAL EJECUTIVA', 1, NOW(), NOW(), NULL),
(15, 'Director General Ejecutivo a.i.', 'Autoridad de Direccion General Ejecutiva.', 'DIRECCION GENERAL EJECUTIVA', 1, NOW(), NOW(), NULL)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), descripcion = VALUES(descripcion), area = VALUES(area), estado = VALUES(estado), updated_at = NOW(), deleted_at = NULL;

INSERT INTO funcionarios (id, id_usuario, nombres, apellido_paterno, apellido_materno, carnet, telefono, genero, estado, created_at, updated_at, deleted_at) VALUES
(4, 4, 'Rene', 'Huanca', 'Poma', '3379243 LP', NULL, 1, 1, NOW(), NOW(), NULL),
(5, 5, 'Max Reynaldo', 'Munoz', 'Moreno', '2606554 LP', NULL, 1, 1, NOW(), NOW(), NULL),
(6, 6, 'Freddy', 'Santos', 'Mancilla', '2499885 LP', NULL, 1, 1, NOW(), NOW(), NULL),
(7, 7, 'David', 'Laruta', 'Onofre', '6788323 LP', NULL, 1, 1, NOW(), NOW(), NULL),
(8, 10, 'Estela', 'Lluito', 'Quenta', '2617166 LP', NULL, 0, 1, NOW(), NOW(), NULL),
(9, 11, 'Ana Maria', 'Bustillos', 'Vargas', '2362861 LP', NULL, 0, 1, NOW(), NOW(), NULL),
(10, 12, 'Guillermo Alejandro', 'Zamora', 'Kraljevic', '4838885 LP', NULL, 1, 1, NOW(), NOW(), NULL),
(11, 13, 'Angela Paola', 'Soria', 'de La Torre', '4261889 LP', NULL, 0, 1, NOW(), NOW(), NULL),
(12, 14, 'Armando', 'Ale', 'Quispe', '4775938 LP', NULL, 1, 1, NOW(), NOW(), NULL)
ON DUPLICATE KEY UPDATE id_usuario = VALUES(id_usuario), nombres = VALUES(nombres), apellido_paterno = VALUES(apellido_paterno), apellido_materno = VALUES(apellido_materno), carnet = VALUES(carnet), telefono = VALUES(telefono), genero = VALUES(genero), estado = VALUES(estado), updated_at = NOW(), deleted_at = NULL;

INSERT INTO funcionarios_cargos (id, id_funcionario, id_cargo, created_at, updated_at, deleted_at) VALUES
(4, 4, 4, NOW(), NOW(), NULL),
(5, 5, 8, NOW(), NOW(), NULL),
(6, 6, 9, NOW(), NOW(), NULL),
(7, 7, 10, NOW(), NOW(), NULL),
(8, 8, 11, NOW(), NOW(), NULL),
(9, 9, 12, NOW(), NOW(), NULL),
(10, 10, 13, NOW(), NOW(), NULL),
(11, 11, 14, NOW(), NOW(), NULL),
(12, 12, 15, NOW(), NOW(), NULL)
ON DUPLICATE KEY UPDATE id_funcionario = VALUES(id_funcionario), id_cargo = VALUES(id_cargo), updated_at = NOW(), deleted_at = NULL;

INSERT INTO roles_users (id, id_role, id_user, created_at, updated_at) VALUES
(4, 1, 4, NOW(), NOW()),
(5, 2, 5, NOW(), NOW()),
(6, 2, 6, NOW(), NOW()),
(7, 2, 7, NOW(), NOW()),
(10, 2, 10, NOW(), NOW()),
(11, 2, 11, NOW(), NOW()),
(12, 2, 12, NOW(), NOW()),
(13, 2, 13, NOW(), NOW()),
(14, 1, 14, NOW(), NOW())
ON DUPLICATE KEY UPDATE id_role = VALUES(id_role), id_user = VALUES(id_user), updated_at = NOW();
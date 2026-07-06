START TRANSACTION;

SET @usuario_id := (SELECT id FROM users ORDER BY id LIMIT 1);

-- =========================================================
-- 1) TIPOS DE CERTIFICADO / TRAMITE
-- =========================================================

INSERT INTO tipos_certificados
(nombre, estado, id_usuario_registro, id_usuario_modificacion, created_at, updated_at)
SELECT nombre, 'ACTIVO', @usuario_id, @usuario_id, NOW(), NOW()
FROM (
    SELECT 'Analisis Toxicologico Ocupacional' AS nombre
    UNION SELECT 'Analisis Fisicoquimico de Calidad de Agua'
    UNION SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire'
    UNION SELECT 'Inspeccion de Lugares Insalubres de Trabajo'
    UNION SELECT 'Medicion de Gases Industriales'
    UNION SELECT 'Monitoreo de Higiene Industrial'
    UNION SELECT 'Estudio de Carga de Fuego SIPPCI'
    UNION SELECT 'Estudio de Ergonomia'
    UNION SELECT 'Inspeccion de Seguridad Industrial a Empresas'
    UNION SELECT 'Gestion de Riesgo Industrial'
    UNION SELECT 'Estudio PGSST'
    UNION SELECT 'Certificacion en Buenas Practicas de Manufactura'
    UNION SELECT 'Charla Informativa en Seguridad'
    UNION SELECT 'Curso de Capacitacion INSO'
    UNION SELECT 'Copia Legalizada'
    UNION SELECT 'Valoracion Dosimetrica por Radiacion'
    UNION SELECT 'Registro de Producto Plaguicida'
    UNION SELECT 'Despacho Aduanero'
    UNION SELECT 'Certificado de Empresa Aplicadora de Plaguicidas'
    UNION SELECT 'Certificado de Empresa Formuladora de Plaguicidas'
    UNION SELECT 'Certificado de Asesor Tecnico'
    UNION SELECT 'Carnet de Aplicador de Plaguicidas'
    UNION SELECT 'Certificado de Libre Venta para Plaguicidas'
    UNION SELECT 'Certificado de Exportacion de Plaguicidas'
    UNION SELECT 'Apostillado de Documento Plaguicida'
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM tipos_certificados tc
    WHERE tc.nombre = datos.nombre
      AND tc.deleted_at IS NULL
);

-- =========================================================
-- 2) REQUISITOS
-- =========================================================

INSERT INTO requisitos
(descripcion, estado, id_usuario_registro, id_usuario_modificacion, created_at, updated_at)
SELECT descripcion, 'ACTIVO', @usuario_id, @usuario_id, NOW(), NOW()
FROM (
    SELECT 'Nota de solicitud dirigida al Director General Ejecutivo del INSO' AS descripcion
    UNION SELECT 'Detalle del requerimiento solicitado'
    UNION SELECT 'Datos de contacto del solicitante'
    UNION SELECT 'Comprobante de pago o deposito segun arancel vigente'
    UNION SELECT 'Documentacion de respaldo o informacion complementaria'
    UNION SELECT 'Cantidad de muestras o puntos de monitoreo'
    UNION SELECT 'Identificacion de la muestra o punto de monitoreo'
    UNION SELECT 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION SELECT 'Fecha y lugar de toma de muestra'
    UNION SELECT 'Cadena de frio y cadena de custodia'
    UNION SELECT 'Envase seguro, hermetico o preacidificado segun corresponda'
    UNION SELECT 'Tipo de analisis solicitado'
    UNION SELECT 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION SELECT 'Planos generales de la instalacion'
    UNION SELECT 'Matriz IPER'
    UNION SELECT 'Cantidad de personal dependiente de la empresa'
    UNION SELECT 'Descripcion de infraestructura, maquinaria y equipos'
    UNION SELECT 'Documento original o copia del documento a legalizar'
    UNION SELECT 'Acreditacion del titular o representante legal del documento'
    UNION SELECT 'Fotocopia de cedula de identidad'
    UNION SELECT 'Lecturas dosimetricas equivalentes a un ano'
    UNION SELECT 'Certificacion o licencia vigente emitida por entidad competente'
    UNION SELECT 'Memorial de solicitud de registro de producto plaguicida'
    UNION SELECT 'Declaracion jurada de uso exclusivo en salud publica o uso domestico'
    UNION SELECT 'Certificado de libre venta del pais de origen'
    UNION SELECT 'Certificado de analisis fisicoquimico del producto'
    UNION SELECT 'Metodologia analitica utilizada'
    UNION SELECT 'Etiqueta original del producto por cada presentacion'
    UNION SELECT 'Estandar analitico del ingrediente activo'
    UNION SELECT 'Muestra comercial en envase original'
    UNION SELECT 'Informacion tecnica cientifica del producto en espanol'
    UNION SELECT 'Hoja de datos de seguridad del producto'
    UNION SELECT 'Estudios toxicologicos del producto'
    UNION SELECT 'Estudios ecotoxicologicos del producto'
    UNION SELECT 'Proyecto de etiqueta segun normativa INSO'
    UNION SELECT 'Carta de autorizacion del fabricante'
    UNION SELECT 'Fotocopia del NIT de la empresa'
    UNION SELECT 'Fotocopia del registro SEPREC vigente'
    UNION SELECT 'Fotocopia de cedula de identidad del representante legal'
    UNION SELECT 'Fotocopia del registro vigente de plaguicida'
    UNION SELECT 'Factura de exportacion con cantidad, presentacion y lote'
    UNION SELECT 'Acta de constitucion de la empresa'
    UNION SELECT 'Domicilio legal, telefono, fax y correo electronico'
    UNION SELECT 'Poder notariado del representante legal'
    UNION SELECT 'Curriculum vitae documentado del personal tecnico'
    UNION SELECT 'Certificado de aprobacion del curso de uso y manejo de plaguicidas'
    UNION SELECT 'Carnet de aplicador de plaguicidas vigente'
    UNION SELECT 'Manual de aplicacion de plaguicidas de uso domestico'
    UNION SELECT 'Cuaderno de registro de plaguicidas aplicados'
    UNION SELECT 'Lista de equipos y planilla de dotacion de EPP'
    UNION SELECT 'Inspeccion tecnica aprobada por INSO'
    UNION SELECT 'Manifiesto ambiental o ficha ambiental aprobada'
    UNION SELECT 'Comprobante de suministro de agua potable y alcantarillado'
    UNION SELECT 'Plan de atencion a emergencias'
    UNION SELECT 'Plan de higiene, salud ocupacional y bienestar'
    UNION SELECT 'Nomina de instalaciones y equipos'
    UNION SELECT 'Datos generales del personal de planta'
    UNION SELECT 'Descripcion de claves de fabricacion o formulacion'
    UNION SELECT 'Contrato de trabajo con asesor tecnico'
    UNION SELECT 'Titulo en provision nacional legalizado'
    UNION SELECT 'Titulo academico'
    UNION SELECT 'Dos fotografias a color segun requisito del tramite'
    UNION SELECT 'Carpeta foliada segun requisito del tramite'
    UNION SELECT 'Certificado medico ocupacional vigente'
    UNION SELECT 'Formulario oficial firmado y sellado por representante legal'
    UNION SELECT 'Padron como fabricante o formulador vigente'
    UNION SELECT 'Detalle del producto a exportar'
    UNION SELECT 'Registro INSO del producto'
    UNION SELECT 'Pais de destino, lugar de embarque, puerto de salida y medio de transporte'
) datos
WHERE NOT EXISTS (
    SELECT 1
    FROM requisitos r
    WHERE r.descripcion = datos.descripcion
      AND r.deleted_at IS NULL
);

-- =========================================================
-- 3) RELACION ENTRE TIPOS DE CERTIFICADO Y REQUISITOS
-- =========================================================

INSERT INTO requisitos_tipos_certificados
(id_requisito, id_tipo_certificado, estado, id_usuario_registro, id_usuario_modificacion, created_at, updated_at)
SELECT r.id, tc.id, 'ACTIVO', @usuario_id, @usuario_id, NOW(), NOW()
FROM (
    SELECT 'Analisis Toxicologico Ocupacional' AS tipo, 'Nota de solicitud dirigida al Director General Ejecutivo del INSO' AS requisito
    UNION ALL SELECT 'Analisis Toxicologico Ocupacional', 'Detalle del requerimiento solicitado'
    UNION ALL SELECT 'Analisis Toxicologico Ocupacional', 'Identificacion de la muestra o punto de monitoreo'
    UNION ALL SELECT 'Analisis Toxicologico Ocupacional', 'Fecha y lugar de toma de muestra'
    UNION ALL SELECT 'Analisis Toxicologico Ocupacional', 'Cadena de frio y cadena de custodia'
    UNION ALL SELECT 'Analisis Toxicologico Ocupacional', 'Tipo de analisis solicitado'
    UNION ALL SELECT 'Analisis Toxicologico Ocupacional', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Analisis Fisicoquimico de Calidad de Agua', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Analisis Fisicoquimico de Calidad de Agua', 'Identificacion de la muestra o punto de monitoreo'
    UNION ALL SELECT 'Analisis Fisicoquimico de Calidad de Agua', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Analisis Fisicoquimico de Calidad de Agua', 'Envase seguro, hermetico o preacidificado segun corresponda'
    UNION ALL SELECT 'Analisis Fisicoquimico de Calidad de Agua', 'Cadena de frio y cadena de custodia'
    UNION ALL SELECT 'Analisis Fisicoquimico de Calidad de Agua', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire', 'Cantidad de muestras o puntos de monitoreo'
    UNION ALL SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire', 'Identificacion de la muestra o punto de monitoreo'
    UNION ALL SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire', 'Tipo de analisis solicitado'
    UNION ALL SELECT 'Monitoreo Ambiental/Ocupacional de Calidad de Aire', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Inspeccion de Lugares Insalubres de Trabajo', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Inspeccion de Lugares Insalubres de Trabajo', 'Documentacion de respaldo o informacion complementaria'
    UNION ALL SELECT 'Inspeccion de Lugares Insalubres de Trabajo', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Inspeccion de Lugares Insalubres de Trabajo', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'

    UNION ALL SELECT 'Medicion de Gases Industriales', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Medicion de Gases Industriales', 'Cantidad de muestras o puntos de monitoreo'
    UNION ALL SELECT 'Medicion de Gases Industriales', 'Identificacion de la muestra o punto de monitoreo'
    UNION ALL SELECT 'Medicion de Gases Industriales', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Medicion de Gases Industriales', 'Tipo de analisis solicitado'
    UNION ALL SELECT 'Medicion de Gases Industriales', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Monitoreo de Higiene Industrial', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Monitoreo de Higiene Industrial', 'Cantidad de muestras o puntos de monitoreo'
    UNION ALL SELECT 'Monitoreo de Higiene Industrial', 'Identificacion de la muestra o punto de monitoreo'
    UNION ALL SELECT 'Monitoreo de Higiene Industrial', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Monitoreo de Higiene Industrial', 'Tipo de analisis solicitado'
    UNION ALL SELECT 'Monitoreo de Higiene Industrial', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Estudio de Carga de Fuego SIPPCI', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Estudio de Carga de Fuego SIPPCI', 'Cantidad de muestras o puntos de monitoreo'
    UNION ALL SELECT 'Estudio de Carga de Fuego SIPPCI', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Estudio de Carga de Fuego SIPPCI', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Estudio de Carga de Fuego SIPPCI', 'Planos generales de la instalacion'
    UNION ALL SELECT 'Estudio de Carga de Fuego SIPPCI', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Estudio de Ergonomia', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Estudio de Ergonomia', 'Cantidad de personal dependiente de la empresa'
    UNION ALL SELECT 'Estudio de Ergonomia', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Estudio de Ergonomia', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Inspeccion de Seguridad Industrial a Empresas', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Inspeccion de Seguridad Industrial a Empresas', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Inspeccion de Seguridad Industrial a Empresas', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Inspeccion de Seguridad Industrial a Empresas', 'Planos generales de la instalacion'
    UNION ALL SELECT 'Inspeccion de Seguridad Industrial a Empresas', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Gestion de Riesgo Industrial', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Gestion de Riesgo Industrial', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Gestion de Riesgo Industrial', 'Ubicacion geografica del muestreo, monitoreo o inspeccion'
    UNION ALL SELECT 'Gestion de Riesgo Industrial', 'Planos generales de la instalacion'
    UNION ALL SELECT 'Gestion de Riesgo Industrial', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Estudio PGSST', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Estudio PGSST', 'Cantidad de personal dependiente de la empresa'
    UNION ALL SELECT 'Estudio PGSST', 'Matriz IPER'
    UNION ALL SELECT 'Estudio PGSST', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Estudio PGSST', 'Planos generales de la instalacion'
    UNION ALL SELECT 'Estudio PGSST', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Certificacion en Buenas Practicas de Manufactura', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Certificacion en Buenas Practicas de Manufactura', 'Descripcion de infraestructura, maquinaria y equipos'
    UNION ALL SELECT 'Certificacion en Buenas Practicas de Manufactura', 'Descripcion del proceso, ambiente o puesto de trabajo'
    UNION ALL SELECT 'Certificacion en Buenas Practicas de Manufactura', 'Planos generales de la instalacion'
    UNION ALL SELECT 'Certificacion en Buenas Practicas de Manufactura', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Charla Informativa en Seguridad', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Charla Informativa en Seguridad', 'Detalle del requerimiento solicitado'
    UNION ALL SELECT 'Charla Informativa en Seguridad', 'Datos de contacto del solicitante'

    UNION ALL SELECT 'Curso de Capacitacion INSO', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Curso de Capacitacion INSO', 'Detalle del requerimiento solicitado'
    UNION ALL SELECT 'Curso de Capacitacion INSO', 'Datos de contacto del solicitante'
    UNION ALL SELECT 'Curso de Capacitacion INSO', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Copia Legalizada', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Copia Legalizada', 'Acreditacion del titular o representante legal del documento'
    UNION ALL SELECT 'Copia Legalizada', 'Documento original o copia del documento a legalizar'
    UNION ALL SELECT 'Copia Legalizada', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Valoracion Dosimetrica por Radiacion', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Valoracion Dosimetrica por Radiacion', 'Fotocopia de cedula de identidad'
    UNION ALL SELECT 'Valoracion Dosimetrica por Radiacion', 'Lecturas dosimetricas equivalentes a un ano'
    UNION ALL SELECT 'Valoracion Dosimetrica por Radiacion', 'Certificacion o licencia vigente emitida por entidad competente'

    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Memorial de solicitud de registro de producto plaguicida'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Declaracion jurada de uso exclusivo en salud publica o uso domestico'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Certificado de libre venta del pais de origen'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Certificado de analisis fisicoquimico del producto'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Metodologia analitica utilizada'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Etiqueta original del producto por cada presentacion'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Estandar analitico del ingrediente activo'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Muestra comercial en envase original'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Informacion tecnica cientifica del producto en espanol'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Hoja de datos de seguridad del producto'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Estudios toxicologicos del producto'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Estudios ecotoxicologicos del producto'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Proyecto de etiqueta segun normativa INSO'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Carta de autorizacion del fabricante'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Fotocopia del NIT de la empresa'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Fotocopia del registro SEPREC vigente'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Fotocopia de cedula de identidad del representante legal'
    UNION ALL SELECT 'Registro de Producto Plaguicida', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Despacho Aduanero', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Despacho Aduanero', 'Datos de contacto del solicitante'
    UNION ALL SELECT 'Despacho Aduanero', 'Fotocopia del registro vigente de plaguicida'
    UNION ALL SELECT 'Despacho Aduanero', 'Factura de exportacion con cantidad, presentacion y lote'
    UNION ALL SELECT 'Despacho Aduanero', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Acta de constitucion de la empresa'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Domicilio legal, telefono, fax y correo electronico'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Fotocopia del NIT de la empresa'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Poder notariado del representante legal'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Fotocopia del registro SEPREC vigente'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Curriculum vitae documentado del personal tecnico'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Certificado de aprobacion del curso de uso y manejo de plaguicidas'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Carnet de aplicador de plaguicidas vigente'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Manual de aplicacion de plaguicidas de uso domestico'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Cuaderno de registro de plaguicidas aplicados'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Lista de equipos y planilla de dotacion de EPP'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Inspeccion tecnica aprobada por INSO'
    UNION ALL SELECT 'Certificado de Empresa Aplicadora de Plaguicidas', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Fotocopia del NIT de la empresa'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Fotocopia del registro SEPREC vigente'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Fotocopia de cedula de identidad del representante legal'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Manifiesto ambiental o ficha ambiental aprobada'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Comprobante de suministro de agua potable y alcantarillado'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Plan de atencion a emergencias'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Plan de higiene, salud ocupacional y bienestar'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Nomina de instalaciones y equipos'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Curriculum vitae documentado del personal tecnico'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Datos generales del personal de planta'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Descripcion de claves de fabricacion o formulacion'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Contrato de trabajo con asesor tecnico'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Inspeccion tecnica aprobada por INSO'
    UNION ALL SELECT 'Certificado de Empresa Formuladora de Plaguicidas', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Fotocopia de cedula de identidad'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Titulo en provision nacional legalizado'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Titulo academico'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Curriculum vitae documentado del personal tecnico'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Dos fotografias a color segun requisito del tramite'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Carpeta foliada segun requisito del tramite'
    UNION ALL SELECT 'Certificado de Asesor Tecnico', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Fotocopia de cedula de identidad'
    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Certificado de aprobacion del curso de uso y manejo de plaguicidas'
    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Certificado medico ocupacional vigente'
    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Curriculum vitae documentado del personal tecnico'
    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Dos fotografias a color segun requisito del tramite'
    UNION ALL SELECT 'Carnet de Aplicador de Plaguicidas', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Certificado de Libre Venta para Plaguicidas', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Certificado de Libre Venta para Plaguicidas', 'Registro INSO del producto'
    UNION ALL SELECT 'Certificado de Libre Venta para Plaguicidas', 'Certificado de analisis fisicoquimico del producto'
    UNION ALL SELECT 'Certificado de Libre Venta para Plaguicidas', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Formulario oficial firmado y sellado por representante legal'
    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Fotocopia del registro vigente de plaguicida'
    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Padron como fabricante o formulador vigente'
    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Detalle del producto a exportar'
    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Pais de destino, lugar de embarque, puerto de salida y medio de transporte'
    UNION ALL SELECT 'Certificado de Exportacion de Plaguicidas', 'Comprobante de pago o deposito segun arancel vigente'

    UNION ALL SELECT 'Apostillado de Documento Plaguicida', 'Nota de solicitud dirigida al Director General Ejecutivo del INSO'
    UNION ALL SELECT 'Apostillado de Documento Plaguicida', 'Acreditacion del titular o representante legal del documento'
    UNION ALL SELECT 'Apostillado de Documento Plaguicida', 'Documento original o copia del documento a legalizar'
) mapa
JOIN tipos_certificados tc
    ON tc.nombre = mapa.tipo
   AND tc.deleted_at IS NULL
JOIN requisitos r
    ON r.descripcion = mapa.requisito
   AND r.deleted_at IS NULL
WHERE NOT EXISTS (
    SELECT 1
    FROM requisitos_tipos_certificados rtc
    WHERE rtc.id_requisito = r.id
      AND rtc.id_tipo_certificado = tc.id
      AND rtc.deleted_at IS NULL
);

COMMIT;
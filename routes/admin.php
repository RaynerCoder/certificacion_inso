<?php

use App\Http\Controllers\TerritorioController;
use App\Http\Controllers\FabricanteController;
use App\Http\Controllers\TipoProductoController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\NaturalController;
use App\Http\Controllers\TipoEmpresaController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\RubroController;
use App\Http\Controllers\TipoCertificadoController;
use App\Http\Controllers\RequisitoController;
use App\Http\Controllers\CertificadoController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\PresentacionController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\IngredienteController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\CargoController;
use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\ProcedenciaController;
use App\Http\Controllers\PagoController;
use App\Models\Certificado;
use App\Http\Controllers\TipoEvidenciaController;
use App\Http\Controllers\TramitadorController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    $usuario = auth()->user()?->loadMissing('persona', 'funcionario.cargos', 'roles');
    $personaId = $usuario?->persona?->id;

    $puedeVerResumenInstitucional = $usuario?->tieneRol('administrador') ?? false;

    $consultaTramites = Certificado::query();

    if (! $puedeVerResumenInstitucional) {
        $consultaTramites->where(function ($consulta) use ($usuario, $personaId) {
            if (! $usuario) {
                $consulta->whereRaw('1 = 0');
                return;
            }

            // Solicitante o tramitador: cuenta solo los trámites vinculados a su persona.
            if ($personaId) {
                $consulta->where('id_persona_beneficiario', $personaId)
                    ->orWhere('id_persona_tramitador', $personaId);
            }

            // Funcionario INSO: cuenta lo que registró o lo que pasó por su bandeja.
            $consulta->orWhere('id_usuario_registro', $usuario->id)
                ->orWhereHas('seguimientos', function ($seguimiento) use ($usuario) {
                    $seguimiento->where('id_usuario_origen', $usuario->id)
                        ->orWhere('id_usuario_siguiente', $usuario->id)
                        ->orWhere('id_usuario_anterior', $usuario->id);
                });
        });
    }

    if (! $puedeVerResumenInstitucional && ! $personaId && ! $usuario?->funcionario) {
        $consultaTramites->whereRaw('1 = 0');
    }

    $estado = fn (array $estados) => (clone $consultaTramites)->whereIn('estado', $estados)->count();

    $resumenInicio = [
        'es_usuario_externo' => ! $puedeVerResumenInstitucional,
        'titulo' => $puedeVerResumenInstitucional ? 'Resumen institucional' : 'Resumen de mis trámites',
        'detalle' => $puedeVerResumenInstitucional
            ? 'Vista general de los trámites registrados en el sistema.'
            : 'Estos datos corresponden únicamente a los trámites donde usted participa.',
        'total' => (clone $consultaTramites)->count(),
        'en_revision' => $estado(['PENDIENTE', 'EN_REVISION', 'DERIVADO', 'BORRADOR']),
        'observados' => $estado(['OBSERVADO', 'CORRECCION_SOLICITADA']),
        'finalizados' => $estado(['FINALIZADO', 'APROBADO', 'EMITIDO']),
    ];

    return view('admin.dashboard', compact('resumenInicio'));
})->name('admin_dashboard');

/* =========================
   REPORTES
========================= */
Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes_index')->middleware('permiso:reportes.ver');



/* =========================
   TERRITORIOS
========================= */
Route::get('/territorios', [TerritorioController::class, 'index'])->name('territorios_index');
Route::post('/territorios', [TerritorioController::class, 'store'])->name('territorios_store');
Route::put('/territorios/{territorio}', [TerritorioController::class, 'update'])->name('territorios_update');
Route::delete('/territorios/{territorio}', [TerritorioController::class, 'destroy'])->name('territorios_destroy');



/* =========================
   PERSONAS
========================= */
Route::get('/personas', [PersonaController::class, 'index'])->name('personas_index');
Route::get('/personas/create', [PersonaController::class, 'create'])->name('personas_create');
Route::post('/personas', [PersonaController::class, 'store'])->name('personas_store');
Route::get('/personas/{persona}/edit', [PersonaController::class, 'edit'])->name('personas_edit');
Route::put('/personas/{persona}', [PersonaController::class, 'update'])->name('personas_update');
Route::delete('/personas/{persona}', [PersonaController::class, 'destroy'])->name('personas_destroy');
Route::get('/personas/{persona}',[PersonaController::class, 'show'])->name('personas_show');



/* =========================
   PERSONAS NATURALES
========================= */
Route::get('/personas-naturales', [NaturalController::class, 'index'])->name('personas_naturales_index');
Route::get('/personas-naturales/create', [NaturalController::class, 'create'])->name('personas_naturales_create');
Route::post('/personas-naturales', [NaturalController::class, 'store'])->name('personas_naturales_store');
Route::get('/personas-naturales/{natural}/edit', [NaturalController::class, 'edit'])->name('personas_naturales_edit');
Route::put('/personas-naturales/{natural}', [NaturalController::class, 'update'])->name('personas_naturales_update');
Route::delete('/personas-naturales/{natural}', [NaturalController::class, 'destroy'])->name('personas_naturales_destroy');


/* =========================
   TIPOS DE EMPRESAS
========================= */
Route::get('/tipos-empresas', [TipoEmpresaController::class, 'index'])->name('tipos_empresas_index');
Route::get('/tipos-empresas/create', [TipoEmpresaController::class, 'create'])->name('tipos_empresas_create');
Route::post('/tipos-empresas', [TipoEmpresaController::class, 'store'])->name('tipos_empresas_store');
Route::get('/tipos-empresas/{tipoEmpresa}/edit', [TipoEmpresaController::class, 'edit'])->name('tipos_empresas_edit');
Route::put('/tipos-empresas/{tipoEmpresa}', [TipoEmpresaController::class, 'update'])->name('tipos_empresas_update');
Route::delete('/tipos-empresas/{tipoEmpresa}', [TipoEmpresaController::class, 'destroy'])->name('tipos_empresas_destroy');


/* =========================
   EMPRESAS
========================= */
Route::get('/empresas/buscar/{empresa}', [EmpresaController::class, 'buscar'])->name('empresas_buscar');
Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas_index');
Route::get('/empresas/create', [EmpresaController::class, 'create'])->name('empresas_create');
Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas_store');
Route::get('/empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('empresas_edit');
Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas_update');
Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas_destroy');




/* =========================
   RESPONSABLES
========================= */
Route::get('/responsables', [ResponsableController::class, 'index'])->name('responsables_index');
Route::get('/responsables/create', [ResponsableController::class, 'create'])->name('responsables_create');
Route::post('/responsables', [ResponsableController::class, 'store'])->name('responsables_store');
Route::get('/responsables/{responsable}', [ResponsableController::class, 'show'])->name('responsables_show');
Route::get('/responsables/{responsable}/edit', [ResponsableController::class, 'edit'])->name('responsables_edit');
Route::put('/responsables/{responsable}', [ResponsableController::class, 'update'])->name('responsables_update');
Route::delete('/responsables/{responsable}', [ResponsableController::class, 'destroy'])->name('responsables_destroy');


/* =========================
   TRAMITADORES
========================= */
Route::get('/tramitadores', [TramitadorController::class, 'index'])->name('tramitadores_index')->middleware('permiso:tramitadores.ver');
Route::get('/tramitadores/create', [TramitadorController::class, 'create'])->name('tramitadores_create')->middleware('permiso:tramitadores.ver');
Route::post('/tramitadores', [TramitadorController::class, 'store'])->name('tramitadores_store')->middleware('permiso:tramitadores.ver');
Route::post('/tramitadores/{tramitador}/dar-baja', [TramitadorController::class, 'darBaja'])
    ->name('tramitadores_baja')
    ->middleware('permiso:tramitadores.ver');



/* =========================
   RUBROS
========================= */
Route::get('/rubros', [RubroController::class, 'index'])->name('rubros_index');
Route::get('/rubros/create', [RubroController::class, 'create'])->name('rubros_create');
Route::post('/rubros', [RubroController::class, 'store'])->name('rubros_store');
Route::get('/rubros/{rubro}/edit', [RubroController::class, 'edit'])->name('rubros_edit');
Route::put('/rubros/{rubro}', [RubroController::class, 'update'])->name('rubros_update');
Route::delete('/rubros/{rubro}', [RubroController::class, 'destroy'])->name('rubros_destroy');



/* =========================
   TIPOS DE CERTIFICADOR
========================= */
Route::get('/tipos-certificados', [TipoCertificadoController::class, 'index'])->name('tipos_certificados_index');
Route::get('/tipos-certificados/create', [TipoCertificadoController::class, 'create'])->name('tipos_certificados_create');
Route::post('/tipos-certificados', [TipoCertificadoController::class, 'store'])->name('tipos_certificados_store');
Route::get('/tipos-certificados/{tipoCertificado}', [TipoCertificadoController::class, 'show'])->name('tipos_certificados_show');
Route::get('/tipos-certificados/{tipoCertificado}/edit', [TipoCertificadoController::class, 'edit'])->name('tipos_certificados_edit');
Route::put('/tipos-certificados/{tipoCertificado}', [TipoCertificadoController::class, 'update'])->name('tipos_certificados_update');
Route::delete('/tipos-certificados/{tipoCertificado}', [TipoCertificadoController::class, 'destroy'])->name('tipos_certificados_destroy');



/* =========================
   REQUISITOS
========================= */
Route::get('/requisitos', [RequisitoController::class, 'index'])->name('requisitos_index');
Route::get('/requisitos/create', [RequisitoController::class, 'create'])->name('requisitos_create');
Route::post('/requisitos', [RequisitoController::class, 'store'])->name('requisitos_store');
Route::get('/requisitos/{requisito}/edit', [RequisitoController::class, 'edit'])->name('requisitos_edit');
Route::put('/requisitos/{requisito}', [RequisitoController::class, 'update'])->name('requisitos_update');
Route::delete('/requisitos/{requisito}', [RequisitoController::class, 'destroy'])->name('requisitos_destroy');



/* =========================
   TRAMITES
========================= */



/* =========================
   CERTIFICADOS
========================= */
Route::get('/certificados', [CertificadoController::class, 'index'])->name('certificados_index');
Route::get('/certificados/create', [CertificadoController::class, 'create'])->name('certificados_create');
Route::post('/certificados', [CertificadoController::class, 'store'])->name('certificados_store');
Route::get('/certificados-emitidos', [CertificadoController::class, 'emitidos'])->name('certificados_emitidos_index');
Route::get('/certificados/plantillas', [CertificadoController::class, 'plantillas'])->name('certificados_plantillas_index');
Route::get('/certificados/plantillas/create', [CertificadoController::class, 'crearPlantilla'])->name('certificados_plantillas_create');
Route::post('/certificados/plantillas', [CertificadoController::class, 'guardarPlantilla'])->name('certificados_plantillas_store');
Route::put('/certificados/plantillas/guardar/{plantillaCertificado}', [CertificadoController::class, 'actualizarPlantilla'])->name('certificados_plantillas_update');
Route::get('/certificados/plantillas/{tipoCertificado}', [CertificadoController::class, 'verPlantilla'])->name('certificados_plantillas_show');
Route::get('/certificados/plantillas/{tipoCertificado}/edit', [CertificadoController::class, 'editarPlantilla'])->name('certificados_plantillas_edit');
Route::get('/certificados/{certificado}/emitir', [CertificadoController::class, 'emitir'])->name('certificados_emitir');
Route::post('/certificados/{certificado}/emitir', [CertificadoController::class, 'guardarEmision'])->name('certificados_emitir_guardar');
Route::post('/certificados/{certificado}/enviar-solicitante', [CertificadoController::class, 'enviarCertificadoSolicitante'])->name('certificados_enviar_solicitante');
Route::get('/certificados/{certificado}/edit', [CertificadoController::class, 'edit'])->name('certificados_edit');
Route::put('/certificados/{certificado}', [CertificadoController::class, 'update'])->name('certificados_update');
Route::delete('/certificados/{certificado}', [CertificadoController::class, 'destroy'])->name('certificados_destroy');
Route::get('/certificados/{certificado}',[CertificadoController::class, 'show'])->name('certificados_show');



/* =========================
   FABRICANTES
========================= */
Route::get('/fabricantes', [FabricanteController::class, 'index'])->name('fabricantes_index');
Route::get('/fabricantes/create', [FabricanteController::class, 'create'])->name('fabricantes_create');
Route::post('/fabricantes', [FabricanteController::class, 'store'])->name('fabricantes_store');
Route::get('/fabricantes/{fabricante}/edit', [FabricanteController::class, 'edit'])->name('fabricantes_edit');
Route::put('/fabricantes/{fabricante}', [FabricanteController::class, 'update'])->name('fabricantes_update');
Route::delete('/fabricantes/{fabricante}', [FabricanteController::class, 'destroy'])->name('fabricantes_destroy');



/* =========================
   TIPOS DE PRODUCTOS
========================= */
Route::get('/tipos-productos', [TipoProductoController::class, 'index'])->name('tipos_productos_index');
Route::get('/tipos-productos/create', [TipoProductoController::class, 'create'])->name('tipos_productos_create');
Route::post('/tipos-productos', [TipoProductoController::class, 'store'])->name('tipos_productos_store');
Route::get('/tipos-productos/{tipoProducto}/edit', [TipoProductoController::class, 'edit'])->name('tipos_productos_edit');
Route::put('/tipos-productos/{tipoProducto}', [TipoProductoController::class, 'update'])->name('tipos_productos_update');
Route::delete('/tipos-productos/{tipoProducto}', [TipoProductoController::class, 'destroy'])->name('tipos_productos_destroy');



/* =========================
   INGREDIENTES
========================= */
Route::get('/ingredientes', [IngredienteController::class, 'index'])->name('ingredientes_index');
Route::get('/ingredientes/create', [IngredienteController::class, 'create'])->name('ingredientes_create');
Route::post('/ingredientes', [IngredienteController::class, 'store'])->name('ingredientes_store');
Route::get('/ingredientes/{ingrediente}/edit', [IngredienteController::class, 'edit'])->name('ingredientes_edit');
Route::put('/ingredientes/{ingrediente}', [IngredienteController::class, 'update'])->name('ingredientes_update');
Route::delete('/ingredientes/{ingrediente}', [IngredienteController::class, 'destroy'])->name('ingredientes_destroy');
Route::get('/ingredientes/{ingrediente}',[IngredienteController::class, 'show'])->name('ingredientes_show');



/* =========================
   PRODUCTOS
========================= */
Route::get('/productos', [ProductoController::class, 'index'])->name('productos_index');
Route::get('/productos/create', [ProductoController::class, 'create'])->name('productos_create');
Route::post('/productos', [ProductoController::class, 'store'])->name('productos_store');
Route::get('/productos/{producto}/edit', [ProductoController::class, 'edit'])->name('productos_edit');
Route::put('/productos/{producto}', [ProductoController::class, 'update'])->name('productos_update');
Route::delete('/productos/{producto}', [ProductoController::class, 'destroy'])->name('productos_destroy');
Route::get('/productos/{producto}',[ProductoController::class, 'show'])->name('productos_show');



/* =========================
   PRESENTACIONES
========================= */
Route::get('/presentaciones', [PresentacionController::class, 'index'])->name('presentaciones_index');
Route::get('/presentaciones/create', [PresentacionController::class, 'create'])->name('presentaciones_create');
Route::post('/presentaciones', [PresentacionController::class, 'store'])->name('presentaciones_store');
Route::get('/presentaciones/{presentacion}/edit', [PresentacionController::class, 'edit'])->name('presentaciones_edit');
Route::put('/presentaciones/{presentacion}', [PresentacionController::class, 'update'])->name('presentaciones_update');
Route::delete('/presentaciones/{presentacion}', [PresentacionController::class, 'destroy'])->name('presentaciones_destroy');
Route::get('/presentaciones/{presentacion}',[PresentacionController::class, 'show'])->name('presentaciones_show');



/* =========================
   REGISTROS
========================= */
Route::get('/registros', [RegistroController::class, 'index'])->name('registros_index');
Route::get('/registros/create', [RegistroController::class, 'create'])->name('registros_create');
Route::post('/registros', [RegistroController::class, 'store'])->name('registros_store');
Route::get('/registros/{registro}/edit', [RegistroController::class, 'edit'])->name('registros_edit');
Route::put('/registros/{registro}', [RegistroController::class, 'update'])->name('registros_update');
Route::delete('/registros/{registro}', [RegistroController::class, 'destroy'])->name('registros_destroy');
Route::get('/registros/{registro}',[RegistroController::class, 'show'])->name('registros_show');



/* =========================
   USUARIOS
========================= */
Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios_index');
Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('usuarios_create');
Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios_store');
Route::get('/usuarios/{usuario}/edit', [UsuarioController::class, 'edit'])->name('usuarios_edit');
Route::put('/usuarios/{usuario}', [UsuarioController::class, 'update'])->name('usuarios_update');
Route::delete('/usuarios/{usuario}', [UsuarioController::class, 'destroy'])->name('usuarios_destroy');
Route::get('/usuarios/{usuario}',[UsuarioController::class, 'show'])->name('usuarios_show');



/* =========================
   ROLES
========================= */
Route::get('/roles', [RolController::class, 'index'])->name('roles_index');
Route::get('/roles/create', [RolController::class, 'create'])->name('roles_create');
Route::post('/roles', [RolController::class, 'store'])->name('roles_store');
Route::get('/roles/{rol}/edit', [RolController::class, 'edit'])->name('roles_edit');
Route::put('/roles/{rol}', [RolController::class, 'update'])->name('roles_update');
Route::delete('/roles/{rol}', [RolController::class, 'destroy'])->name('roles_destroy');
Route::get('/roles/{rol}',[RolController::class, 'show'])->name('roles_show');



/* =========================
   PERMISOS
========================= */
Route::get('/permisos', [PermisoController::class, 'index'])->name('permisos_index');
Route::get('/permisos/create', [PermisoController::class, 'create'])->name('permisos_create');
Route::post('/permisos', [PermisoController::class, 'store'])->name('permisos_store');
Route::get('/permisos/{permiso}/edit', [PermisoController::class, 'edit'])->name('permisos_edit');
Route::put('/permisos/{permiso}', [PermisoController::class, 'update'])->name('permisos_update');
Route::delete('/permisos/{permiso}', [PermisoController::class, 'destroy'])->name('permisos_destroy');
Route::get('/permisos/{permiso}',[PermisoController::class, 'show'])->name('permisos_show');


/* =========================
   AREAS
========================= */
Route::get('/areas', [AreaController::class, 'index'])->name('areas_index');
Route::get('/areas/create', [AreaController::class, 'create'])->name('areas_create');
Route::post('/areas', [AreaController::class, 'store'])->name('areas_store');
Route::get('/areas/{area}/edit', [AreaController::class, 'edit'])->name('areas_edit');
Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas_update');
Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('areas_destroy');
Route::get('/areas/{area}', [AreaController::class, 'show'])->name('areas_show');



/* =========================
   CARGOS
========================= */
Route::get('/cargos', [CargoController::class, 'index'])->name('cargos_index');
Route::get('/cargos/create', [CargoController::class, 'create'])->name('cargos_create');
Route::post('/cargos', [CargoController::class, 'store'])->name('cargos_store');
Route::get('/cargos/{cargo}/edit', [CargoController::class, 'edit'])->name('cargos_edit');
Route::put('/cargos/{cargo}', [CargoController::class, 'update'])->name('cargos_update');
Route::delete('/cargos/{cargo}', [CargoController::class, 'destroy'])->name('cargos_destroy');
Route::get('/cargos/{cargo}',[CargoController::class, 'show'])->name('cargos_show');



/* =========================
   SEGUIMIENTOS - TRAMITE
========================= */
Route::get('/seguimientos', [SeguimientoController::class, 'index'])->name('seguimientos_index')->middleware('permiso:seguimientos_tramite.atender');
Route::get('/seguimientos/mis-solicitudes', [SeguimientoController::class, 'index'])->name('seguimientos_mis_tramites_beneficiario')->middleware('permiso:seguimientos_tramite.enviados');
Route::get('/seguimientos/registrados-por-mi', [SeguimientoController::class, 'index'])->name('seguimientos_tramites_registrados_funcionario')->middleware('permiso:seguimientos_tramite.registrados');
Route::get('/seguimientos/todos', [SeguimientoController::class, 'index'])->name('seguimientos_todos')->middleware('permiso:seguimientos_tramite.consulta_general');
Route::get('/seguimientos/finalizados', [SeguimientoController::class, 'index'])->name('seguimientos_finalizados')->middleware('permiso:seguimientos_tramite.consulta_general');
Route::get('/seguimientos/create', [SeguimientoController::class, 'create'])->name('seguimientos_create')->middleware('permiso:seguimientos_tramite.iniciar');
Route::post('/seguimientos', [SeguimientoController::class, 'store'])
    ->name('seguimientos_store')
    ->middleware(['permiso:seguimientos_tramite.iniciar', 'throttle:10,1']);
Route::get('/seguimientos/{seguimiento}/historial', [SeguimientoController::class, 'historial'])->name('seguimientos_tramite_historial')->middleware('permiso:seguimientos_tramite.historial');
Route::post('/seguimientos/{seguimiento}/asignar-tecnico', [SeguimientoController::class, 'asignarTecnico'])->name('seguimientos_asignar_tecnico')->middleware('permiso:seguimientos_tramite.atender');
Route::post('/seguimientos/{seguimiento}/derivar-tecnico', [SeguimientoController::class, 'derivarTecnico'])->name('seguimientos_derivar_tecnico')->middleware('permiso:seguimientos_tramite.atender');
Route::post('/seguimientos/{seguimiento}/revision-tecnica', [SeguimientoController::class, 'revisarTecnico'])->name('seguimientos_revision_tecnica')->middleware('permiso:seguimientos_tramite.atender');
Route::post('/seguimientos/{seguimiento}/finalizar-tramite', [SeguimientoController::class, 'finalizarTramite'])->name('seguimientos_finalizar_tramite')->middleware('permiso:seguimientos_tramite.atender');
Route::post('/seguimientos/{seguimiento}/notificar-correccion', [SeguimientoController::class, 'notificarCorreccionSolicitante'])->name('seguimientos_notificar_correccion')->middleware('permiso:seguimientos_tramite.atender');
Route::post('/seguimientos/{seguimiento}/registrar-correccion-recibida', [SeguimientoController::class, 'registrarCorreccionRecibida'])->name('seguimientos_registrar_correccion_recibida')->middleware('permiso:seguimientos_tramite.atender');
Route::post('/seguimientos/{seguimiento}/reenviar-correccion', [SeguimientoController::class, 'reenviarCorreccion'])->name('seguimientos_reenviar_correccion')->middleware('permiso:seguimientos_tramite.enviados');
Route::get('/seguimientos/{seguimiento}/edit', [SeguimientoController::class, 'edit'])->name('seguimientos_edit');
Route::put('/seguimientos/{seguimiento}', [SeguimientoController::class, 'update'])->name('seguimientos_update');
Route::delete('/seguimientos/{seguimiento}', [SeguimientoController::class, 'destroy'])->name('seguimientos_destroy');
Route::get('/seguimientos/{seguimiento}', [SeguimientoController::class, 'show'])->name('seguimientos_show')->middleware('permiso:seguimientos_tramite.historial');
Route::get('/notificaciones/tramites', [SeguimientoController::class, 'notificacionesTramites'])->name('notificaciones_tramites');
Route::post('/notificaciones/tramites/{notificacion}/leer', [SeguimientoController::class, 'marcarNotificacionTramite'])->name('notificaciones_tramites_leer');
Route::post('/notificaciones/tramites/leer-todas', [SeguimientoController::class, 'marcarTodasNotificacionesTramite'])->name('notificaciones_tramites_leer_todas');


/* =========================
   PROCEDENCIA
========================= */
Route::get('/procedencias', [ProcedenciaController::class, 'index'])->name('procedencias_index');
Route::get('/procedencias/create', [ProcedenciaController::class, 'create'])->name('procedencias_create');
Route::post('/procedencias', [ProcedenciaController::class, 'store'])->name('procedencias_store');
Route::get('/procedencias/{procedencia}/edit', [ProcedenciaController::class, 'edit'])->name('procedencias_edit');
Route::put('/procedencias/{procedencia}', [ProcedenciaController::class, 'update'])->name('procedencias_update');
Route::delete('/procedencias/{procedencia}', [ProcedenciaController::class, 'destroy'])->name('procedencias_destroy');
Route::get('/procedencias/{procedencia}',[ProcedenciaController::class, 'show'])->name('procedencias_show');



/* =========================
   PAGO
========================= */
Route::get('/pagos', [PagoController::class, 'index'])->name('pagos_index');
Route::get('/pagos/create', [PagoController::class, 'create'])->name('pagos_create');
Route::post('/pagos', [PagoController::class, 'store'])->name('pagos_store');
Route::get('/pagos/{pago}/edit', [PagoController::class, 'edit'])->name('pagos_edit');
Route::put('/pagos/{pago}', [PagoController::class, 'update'])->name('pagos_update');
Route::delete('/pagos/{pago}', [PagoController::class, 'destroy'])->name('pagos_destroy');
Route::get('/pagos/{pago}',[PagoController::class, 'show'])->name('pagos_show');



/* =========================
   TIPOS DE EVIDENCIA
========================= */
Route::get('/tipos_evidencias', [TipoEvidenciaController::class, 'index'])->name('tipos_evidencias_index');
Route::get('/tipos_evidencias/create', [TipoEvidenciaController::class, 'create'])->name('tipos_evidencias_create');
Route::post('/tipos_evidencias', [TipoEvidenciaController::class, 'store'])->name('tipos_evidencias_store');
Route::get('/tipos_evidencias/{tipoEvidencia}/edit', [TipoEvidenciaController::class, 'edit'])->name('tipos_evidencias_edit');
Route::put('/tipos_evidencias/{tipoEvidencia}', [TipoEvidenciaController::class, 'update'])->name('tipos_evidencias_update');
Route::delete('/tipos_evidencias/{tipoEvidencia}', [TipoEvidenciaController::class, 'destroy'])->name('tipos_evidencias_destroy');




/* =========================
   INGREDIENTES
========================= */
Route::get('/ingredientes', [IngredienteController::class, 'index'])->name('ingredientes_index');
Route::get('/ingredientes/create', [IngredienteController::class, 'create'])->name('ingredientes_create');
Route::post('/ingredientes', [IngredienteController::class, 'store'])->name('ingredientes_store');
Route::get('/ingredientes/{ingrediente}/edit', [IngredienteController::class, 'edit'])->name('ingredientes_edit');
Route::put('/ingredientes/{ingrediente}', [IngredienteController::class, 'update'])->name('ingredientes_update');
Route::delete('/ingredientes/{ingrediente}', [IngredienteController::class, 'destroy'])->name('ingredientes_destroy');




/* =========================
   PRESENTACIONES
========================= */
Route::get('/presentaciones', [PresentacionController::class, 'index'])->name('presentaciones_index');
Route::get('/presentaciones/create', [PresentacionController::class, 'create'])->name('presentaciones_create');
Route::post('/presentaciones', [PresentacionController::class, 'store'])->name('presentaciones_store');
Route::get('/presentaciones/{presentacion}/edit', [PresentacionController::class, 'edit'])->name('presentaciones_edit');
Route::put('/presentaciones/{presentacion}', [PresentacionController::class, 'update'])->name('presentaciones_update');
Route::delete('/presentaciones/{presentacion}', [PresentacionController::class, 'destroy'])->name('presentaciones_destroy');



/* =========================
   REGISTROS
========================= */
Route::get('/registros', [RegistroController::class, 'index'])->name('registros_index');
Route::get('/registros/create', [RegistroController::class, 'create'])->name('registros_create');
Route::post('/registros', [RegistroController::class, 'store'])->name('registros_store');
Route::get('/registros/{registro}/edit', [RegistroController::class, 'edit'])->name('registros_edit');
Route::put('/registros/{registro}', [RegistroController::class, 'update'])->name('registros_update');
Route::delete('/registros/{registro}', [RegistroController::class, 'destroy'])->name('registros_destroy');

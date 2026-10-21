<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Funcionario;
use App\Models\Natural;

class CiudadaniaDigitalController extends Controller
{

    // Envía al usuario desde el sistema hacia la pantalla de inicio de sesión de Ciudadanía Digital.
    public function redirigir()
    {
        // Utiliza la conexión configurada para iniciar el flujo OIDC
        return Socialite::driver('oidc_ciudadania_digital')->redirect();
    }


    // Recibe la respuesta enviada por Ciudadanía Digital después de autenticar al ciudadano.
    public function callback(Request $solicitud)
    {
        // Obtiene al ciudadano autenticado y valida la respuesta mediante el proveedor OIDC.
        $ciudadano = Socialite::driver('oidc_ciudadania_digital')->user();

        // Obtiene todos los datos originales enviados por Ciudadanía Digital.
        $datos = $ciudadano->getRaw();

        // Obtiene el perfil que contiene los datos personales.
        $perfil = is_array($datos['profile'] ?? null) ? $datos['profile'] : [];

        // Obtiene la estructura que contiene el documento de identidad.
        $documento = is_array($perfil['documento_identidad'] ?? null) ? $perfil['documento_identidad'] : [];

        // Obtiene el documento completo enviado por AGETIC.
        $numeroDocumento = mb_strtoupper(trim((string) ($documento['numero_documento'] ?? '')),'UTF-8');

        // Obtiene el complemento separado si AGETIC lo envía de esa manera.
        $complemento = mb_strtoupper(trim((string) ($documento['complemento'] ?? $documento['complemento_documento'] ?? '')), 'UTF-8');

        // Inicialmente utiliza todo el valor como número de carnet.
        $ci = $numeroDocumento;

        // Separa valores con el formato NUMERO-COMPLEMENTO.
        if (preg_match('/^([0-9]+)-([A-Z0-9]+)$/u', $numeroDocumento, $partes)) {
            // Conserva solamente la parte numérica como CI.
            $ci = $partes[1];

            // Usa el complemento incluido en el documento si no llegó por separado.
            if ($complemento === '') {
                $complemento = $partes[2];
            }
        }

        // Obtiene el tipo de documento y lo convierte en mayúsculas.
        $tipoDocumento = mb_strtoupper(trim((string) ($documento['tipo_documento'] ?? '')), 'UTF-8');

        // Obtiene la estructura que contiene los nombres y apellidos.
        $nombre = is_array($perfil['nombre'] ?? null) ? $perfil['nombre'] : [];

        // Obtiene los nombres validados del ciudadano.
        $nombres = trim((string) ($nombre['nombres'] ?? ''));

        // Obtiene el primer apellido validado del ciudadano.
        $apellidoPaterno = trim((string) ($nombre['primer_apellido'] ?? ''));

        // Obtiene el segundo apellido; puede estar vacío.
        $apellidoMaterno = trim((string) ($nombre['segundo_apellido'] ?? ''));

        // Obtiene el correo autorizado por el ciudadano.
        $correo = trim((string) ($datos['email'] ?? ''));

        // Obtiene el identificador único del ciudadano dentro de AGETIC. (IDENTIFICADOR OIDC ASIGNADO POR AGETIC)
        $subject = trim((string) ($datos['sub'] ?? ''));

        // Obtiene la dirección del proveedor que emitió la identidad.
        $issuer = trim((string) ($datos['iss'] ?? ''));     
        
        // Verifica que AGETIC haya entregado un número de documento.
        if ($ci === '') {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'Ciudadanía Digital no devolvió el número de documento o CI.',]);
        }

        // El sistema actualmente trabaja únicamente con cédula de identidad.
        if ($tipoDocumento !== 'CI') {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'El tipo de documento recibido no es compatible con el sistema.',]);
        }

        // Verifica que exista el identificador único OIDC del ciudadano.
        if ($subject === '') {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'Ciudadanía Digital no devolvió el identificador del ciudadano.',]);
        }

        // Verifica que exista el proveedor que emitió la identidad.
        if ($issuer === '') {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'Ciudadanía Digital no devolvió el proveedor de identidad.',]);
        }        


        // Prepara la comparación del complemento.
        $filtroComplemento = function ($consulta) use ($complemento) {
            // Cuando no existe complemento, acepta vacío o nulo.
            if ($complemento === '') {
                $consulta->whereNull('complemento')->orWhere('complemento', '');
                return;
            }

            // Cuando existe complemento, exige coincidencia exacta.
            $consulta->where('complemento', $complemento);
        };

        // Consulta el CI entre los funcionarios internos.
        $funcionario = Funcionario::query()->with('usuario')->where('ci', $ci)->where($filtroComplemento)->first();

        // Consulta el mismo CI entre las personas naturales externas.
        $natural = Natural::query()->with('persona.usuario')->where('ci', $ci)->where($filtroComplemento)->first();

        // Reúne las cuentas encontradas.
        $usuariosEncontrados = collect([$funcionario?->usuario, $natural?->persona?->usuario,])->filter()->unique('id')->values();

        // Rechaza el acceso si no existe una cuenta local.
        if ($usuariosEncontrados->isEmpty()) {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'Su identidad fue validada, pero no tiene una cuenta registrada en el sistema.',]);
        }

        // Rechaza el acceso si existen dos cuentas diferentes.
        if ($usuariosEncontrados->count() > 1) {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'El documento está relacionado con más de una cuenta.',]);
        }

        // Obtiene la única cuenta local encontrada.
        $usuario = $usuariosEncontrados->first();

        // Verifica que la cuenta local esté activa.
        if ((string) $usuario->estado !== '1') {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'Su cuenta se encuentra inactiva. Comuníquese con INSO.',]);
        }

        // Verifica que el usuario tenga un rol o permiso activo.
        // Esta validación ya forma parte de las reglas de acceso de tu sistema.
        if (! $usuario->tieneAccesoSistema()) {
            return redirect()->route('login')->withErrors(['ciudadania_digital' => 'No tiene autorización para ingresar al sistema.',]);}        

        // Comprueba temporalmente el resultado sin iniciar sesión.
        // return response()->json([
        //     'ci' => $ci,
        //     'complemento' => $complemento,
        //     'tipo_documento' => $tipoDocumento,
        //     'nombres' => $nombres,
        //     'apellido_paterno' => $apellidoPaterno,
        //     'apellido_materno' => $apellidoMaterno,
        //     'correo' => $correo,
        //     'usuario_local_encontrado' => true,
        //     'id_usuario_local' => $usuario->id,
        //     'estado_usuario' => $usuario->estado,
        //     'tiene_acceso_sistema' => $usuario->tieneAccesoSistema(),
        // ]);

        // Obtiene el token que después permitirá cerrar la sesión en AGETIC.
        $idToken = $ciudadano->accessTokenResponseBody['id_token'] ?? null;

        // Comprueba que AGETIC haya entregado el token.
        if (! is_string($idToken) || $idToken === '') {
            return redirect()->route('login')->withErrors(['ciudadania_digital' =>'Ciudadanía Digital no devolvió el token de identidad.',]);
        }

        // Inicia la sesión local con la cuenta relacionada al CI validado por AGETIC.
        Auth::guard('web')->login($usuario);

        // Regenera el identificador para proteger la sesión recién iniciada.
        $solicitud->session()->regenerate();

        // Guarda temporalmente el token para utilizarlo al cerrar sesión en AGETIC.
        $solicitud->session()->put('ciudadania_digital_id_token', $idToken);

        // Redirige al usuario al menú principal.
        return redirect()->intended('/menu');
    }


    // Envía al usuario hacia AGETIC para cerrar su sesión de Ciudadanía Digital.
    public function cerrarSesion(Request $solicitud)
    {
        // Recupera el token guardado durante el inicio de sesión.
        $idToken = $solicitud->session()->get('ciudadania_digital_id_token');

        // Si no existe el token, solamente cierra la sesión local.
        if (! is_string($idToken) || $idToken === '') {
            Auth::guard('web')->logout();

            $solicitud->session()->invalidate();
            $solicitud->session()->regenerateToken();

            return redirect()->route('login');
        }

        // Envía al usuario a AGETIC para cerrar también su sesión central.
        return Socialite::driver('oidc_ciudadania_digital')->logout($idToken);
    }


    // Recibe al usuario después de cerrar su sesión en AGETIC.
    public function retornoCerrarSesion(Request $solicitud)
    {
        // Comprueba que el retorno pertenezca al cierre iniciado por este sistema.
        $estadoValido = Socialite::driver('oidc_ciudadania_digital')->validateLogoutState($solicitud);

        // Rechaza retornos alterados, repetidos o desconocidos.
        if (! $estadoValido) {
            abort(403, 'No se pudo validar el cierre de sesión.');
        }

        // Cierra la sesión local de Laravel.
        Auth::guard('web')->logout();

        // Elimina todos los datos de la sesión anterior.
        $solicitud->session()->invalidate();

        // Genera un nuevo token de seguridad para futuros formularios.
        $solicitud->session()->regenerateToken();

        // Regresa al formulario de inicio de sesión.
        return redirect()->route('login')->with('status', 'Sesión cerrada correctamente.');
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use App\Models\Natural;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Role;
use App\Models\Rubro;
use App\Models\Telefono;
use App\Models\Territorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TramitadorController extends Controller
{
    /**
     * Muestra la tabla de tramitadores registrados.
     * Un tramitador se guarda como responsable de empresa con rol TRAMITADOR.
     */
    public function index(Request $request)
    {
        $empresas = Empresa::query()
            ->with('persona')
            ->orderBy('razon_social')
            ->get();

        $empresa = $request->filled('id_empresa')
            ? Empresa::query()->with('persona')->find($request->id_empresa)
            : null;

        $tramitadores = Responsable::query()
            ->with([
                'empresa.persona',
                'persona.natural',
                'persona.telefonos',
                'persona.rubros',
                'rol',
            ])
            // Solo muestra responsables cuyo rol corresponde a tramitador.
            ->whereHas('rol', function ($query) {
                $query->where('slug', 'tramitador')
                    ->orWhere('name', 'like', '%TRAMITADOR%');
            })
            ->when($empresa, fn ($query) => $query->where('id_empresa', $empresa->id))
            ->orderByDesc('id')
            ->get();

        return view('tramitadores.index', compact('empresas', 'empresa', 'tramitadores'));
    }

    /**
     * Abre el formulario para registrar una persona natural como tramitador de una empresa.
     */
    public function create()
    {
        $empresas = Empresa::query()
            ->with('persona')
            ->orderBy('razon_social')
            ->get();

        $territorios = Territorio::query()
            ->orderBy('nombre')
            ->get();

        // Los roles salen del catalogo para guardar responsables.id_rol.
        $roles = Role::where('estado', 1)->orderBy('name')->get();

        return view('tramitadores.create', compact('empresas', 'territorios', 'roles'));
    }

    /**
     * Registra el tramitador llenando personas, naturals, telefonos, rubros y responsables.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarTramitador($solicitud);

        try {
            DB::beginTransaction();

            // Primero se crea la persona base del tramitador.
            $persona = Persona::create([
                'domicilio' => $datos['form_domicilio'] ?? null,
                'nit' => $datos['form_nit'] ?? null,
                'correo' => $datos['form_correo'],
                'id_territorio' => $datos['form_id_territorio'],
                'estado' => 'ACTIVO',
            ]);

            // Luego se completan los datos propios de persona natural.
            Natural::create([
                'id_persona' => $persona->id,
                'ci' => $datos['form_ci'],
                'complemento' => $datos['form_complemento'] ?? null,
                'expedido' => $datos['form_expedido'] ?? null,
                'nombres' => $this->mayuscula($datos['form_nombres']),
                'apellido_paterno' => $this->mayuscula($datos['form_apellido_paterno']),
                'apellido_materno' => $this->mayuscula($datos['form_apellido_materno'] ?? null),
                'apellido_casado' => null,
                'fecha_nacimiento' => $datos['form_fecha_nacimiento'] ?? null,
                'genero' => $datos['form_genero'],
                'ocupacion' => $this->mayuscula($datos['form_ocupacion'] ?? null),
            ]);

            // Telefonos enviados desde la vista como JSON.
            foreach ($this->normalizarListaJson($datos['form_telefonos_json'] ?? null, ['numero', 'tipo']) as $telefono) {
                if (filled($telefono['numero'] ?? null)) {
                    Telefono::create([
                        'id_persona' => $persona->id,
                        'numero' => $telefono['numero'],
                        'estado' => $telefono['tipo'] ?: 'CELULAR',
                    ]);
                }
            }

            // Rubros enviados desde la vista como JSON.
            foreach ($this->normalizarListaJson($datos['form_rubros_json'] ?? null, ['nombre', 'estado']) as $rubro) {
                if (filled($rubro['nombre'] ?? null)) {
                    Rubro::create([
                        'id_persona' => $persona->id,
                        'nombre' => $this->mayuscula($rubro['nombre']),
                        'estado' => $rubro['estado'] ?: 'ACTIVO',
                    ]);
                }
            }

            // Finalmente se asocia la persona natural como tramitador de la empresa.
            Responsable::create([
                'id_empresa' => $datos['form_id_empresa'],
                'id_persona' => $persona->id,
                'id_rol' => $datos['form_id_rol'],
                'url_respaldo' => $this->guardarRespaldo($solicitud),
                'fecha_registro' => $datos['form_fecha_registro'] ?? now()->toDateString(),
                'fecha_baja' => null,
                'estado' => $datos['form_estado'] ?? 'ACTIVO',
            ]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Registrado',
                'text' => 'El tramitador se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('tramitadores_index', ['id_empresa' => $datos['form_id_empresa']]);
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->with('error', 'No se pudo registrar el tramitador.')
                ->withInput();
        }
    }

    /**
     * Valida los datos necesarios para crear persona natural y relacionarla con la empresa.
     */
    private function validarTramitador(Request $solicitud): array
    {
        return $solicitud->validate([
            'form_id_empresa' => ['required', 'exists:empresas,id'],
            'form_id_rol' => ['required', 'exists:roles,id'],
            'form_url_respaldo' => ['nullable', 'file', 'mimes:pdf', 'max:5120'],
            'form_fecha_registro' => ['nullable', 'date'],
            'form_estado' => ['nullable', 'string', 'max:50'],
            'form_domicilio' => ['nullable', 'string', 'max:255'],
            'form_nit' => ['nullable', 'string', 'max:50', Rule::unique('personas', 'nit')],
            'form_correo' => ['required', 'email', 'max:50', Rule::unique('personas', 'correo')],
            'form_id_territorio' => ['required', 'exists:territorios,id'],
            'form_ci' => ['required', 'string', 'max:50', Rule::unique('naturals', 'ci')],
            'form_complemento' => ['nullable', 'string', 'max:10'],
            'form_expedido' => ['nullable', 'string', 'max:10'],
            'form_nombres' => ['required', 'string', 'max:100'],
            'form_apellido_paterno' => ['required', 'string', 'max:100'],
            'form_apellido_materno' => ['nullable', 'string', 'max:100'],
            'form_fecha_nacimiento' => ['nullable', 'date'],
            'form_genero' => ['required', 'in:0,1'],
            'form_ocupacion' => ['nullable', 'string', 'max:255'],
            'form_telefonos_json' => ['nullable', 'json'],
            'form_rubros_json' => ['nullable', 'json'],
        ], [], [
            'form_id_empresa' => 'empresa',
            'form_id_rol' => 'rol del tramitador',
            'form_url_respaldo' => 'respaldo PDF',
            'form_correo' => 'correo',
            'form_id_territorio' => 'territorio',
            'form_ci' => 'CI',
            'form_nombres' => 'nombres',
            'form_apellido_paterno' => 'apellido paterno',
            'form_genero' => 'genero',
        ]);
    }

    /**
     * Guarda el respaldo PDF del tramitador si fue cargado.
     */
    private function guardarRespaldo(Request $solicitud): ?string
    {
        if (!$solicitud->hasFile('form_url_respaldo')) {
            return null;
        }

        return $solicitud->file('form_url_respaldo')->store('tramitadores', 'public');
    }

    /**
     * Convierte listas JSON de la vista en arreglos seguros para guardar.
     */
    private function normalizarListaJson(?string $json, array $camposPermitidos): array
    {
        $items = json_decode($json ?: '[]', true);

        if (!is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function ($item) use ($camposPermitidos) {
                return collect($item)
                    ->only($camposPermitidos)
                    ->map(fn ($valor) => is_string($valor) ? trim($valor) : $valor)
                    ->all();
            })
            ->values()
            ->all();
    }

    /**
     * Mantiene el formato usado en los demas modulos: textos principales en mayuscula.
     */
    private function mayuscula(?string $valor): ?string
    {
        return filled($valor) ? mb_strtoupper(trim($valor), 'UTF-8') : null;
    }
}

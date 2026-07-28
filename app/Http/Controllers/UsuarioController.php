<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Role;
use App\Models\User;
use App\Models\Area;
use App\Models\Cargo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    /**
     * Muestra el listado principal de usuarios del sistema.
     */
    public function index()
    {
        return view('usuarios.index');
    }

    /**
     * Abre el formulario para crear usuario y carga roles/permisos activos.
     */
    public function create()
    {
        return view('usuarios.create', [
            'roles' => Role::with('permisos')->where('estado', 1)->orderBy('name')->get(),
            'permisos' => Permiso::where('estado', 1)->orderBy('nombre')->get(),
            'cargos' => $this->cargosDisponiblesParaUsuario(),
            'areas' => $this->areasDisponiblesParaCargo(),
        ]);
    }

    /**
     * Guarda una nueva cuenta de usuario y sincroniza sus roles/permisos directos.
     */
    public function store(Request $solicitud)
    {
        $datos = $this->validarUsuario($solicitud);
        $this->validarCargosDisponibles($datos);

        try {
            DB::beginTransaction();

            $usuario = User::create([
                'name' => $datos['form_name'],
                'email' => $datos['form_email'],
                'password' => $datos['form_password'],
                'estado' => (int) $datos['form_estado'],
            ]);

            $usuario->roles()->sync($datos['form_roles']);
            $usuario->permisosDirectos()->sync($datos['form_permisos'] ?? []);

            // Guarda los datos personales vinculados a la cuenta.
            $funcionario = $usuario->funcionario()->create([
                'nombres' => $datos['form_funcionario_nombres'],
                'apellido_paterno' => $datos['form_funcionario_apellido_paterno'],
                'apellido_materno' => $datos['form_funcionario_apellido_materno'] ?? null,
                'carnet' => $datos['form_funcionario_carnet'],
                'telefono' => $datos['form_funcionario_telefono'] ?? null,
                'genero' => (int) $datos['form_funcionario_genero'],
            ]);

            $funcionario->cargos()->sync($this->obtenerCargosFuncionario($datos));

            DB::commit();

            session()->flash('swal', [
                'title' => 'Bien hecho',
                'text' => 'El usuario se registro correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('usuarios_index');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->with('error', 'No se pudo registrar el usuario.')
                ->withInput();
        }
    }

    /**
     * Muestra la cuenta y sus relaciones administrativas en una sola vista.
     */
    public function show(User $usuario)
    {
        $usuario->load([
            'funcionario.cargos.area',
            'roles.permisos',
            'permisosDirectos',
        ]);

        return view('usuarios.show', compact('usuario'));
    }

    /**
     * Abre el formulario de edicion con roles y permisos ya asignados al usuario.
     */
    public function edit(User $usuario)
    {
        $usuario->load(['roles', 'permisosDirectos', 'funcionario.cargos']);

        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Role::with('permisos')->where('estado', 1)->orderBy('name')->get(),
            'permisos' => Permiso::where('estado', 1)->orderBy('nombre')->get(),
            'cargos' => $this->cargosDisponiblesParaUsuario($usuario),
            'areas' => $this->areasDisponiblesParaCargo(),
        ]);
    }

    /**
     * Actualiza datos del usuario y resincroniza roles/permisos directos.
     */
    public function update(Request $solicitud, User $usuario)
    {
        $datos = $this->validarUsuario($solicitud, $usuario);
        $this->validarCargosDisponibles($datos, $usuario);

        if ($this->quiereInactivarUsuario($usuario, $datos)) {
            $motivo = $this->motivoQueImpideInactivarUsuario($usuario);

            if ($motivo) {
                session()->flash('swal', [
                    'title' => 'No se puede cambiar a Inactivo',
                    'text' => $motivo,
                    'icon' => 'error',
                ]);

                return redirect()->route('usuarios_index');
            }
        }

        try {
            DB::beginTransaction();

            $campos = [
                'name' => $datos['form_name'],
                'email' => $datos['form_email'],
                'estado' => (int) $datos['form_estado'],
            ];

            // Si no se escribe una contraseña, la cuenta conserva la que ya tenía.
            if (!empty($datos['form_password'])) {
                $campos['password'] = $datos['form_password'];
            }

            $usuario->update($campos);
            $usuario->roles()->sync($datos['form_roles']);
            $usuario->permisosDirectos()->sync($datos['form_permisos'] ?? []);

            // Actualiza los datos personales o los crea si la cuenta todavía no los tiene.
            $funcionario = $usuario->funcionario()->updateOrCreate(
                ['id_usuario' => $usuario->id],
                [
                    'nombres' => $datos['form_funcionario_nombres'],
                    'apellido_paterno' => $datos['form_funcionario_apellido_paterno'],
                    'apellido_materno' => $datos['form_funcionario_apellido_materno'] ?? null,
                    'carnet' => $datos['form_funcionario_carnet'],
                    'telefono' => $datos['form_funcionario_telefono'] ?? null,
                    'genero' => (int) $datos['form_funcionario_genero'],
                ]
            );

            $funcionario->cargos()->sync($this->obtenerCargosFuncionario($datos));

            DB::commit();

            session()->flash('swal', [
                'title' => 'Actualizado',
                'text' => 'El usuario se actualizo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('usuarios_index');
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return back()
                ->with('error', 'No se pudo actualizar el usuario.')
                ->withInput();
        }
    }

    /**
     * Inactiva el usuario sin borrarlo para conservar su historial.
     */
    public function destroy(User $usuario)
    {
        $motivo = $this->motivoQueImpideInactivarUsuario($usuario);

        if ($motivo) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => $motivo,
                'icon' => 'error',
            ]);

            return redirect()->route('usuarios_index');
        }

        if ((string) $usuario->estado === '0') {
            session()->flash('swal', [
                'title' => 'Sin cambios',
                'text' => 'El usuario ya tiene estado Inactivo.',
                'icon' => 'info',
            ]);

            return redirect()->route('usuarios_index');
        }

        try {
            DB::beginTransaction();

            $usuario->update(['estado' => 0]);

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El estado del usuario cambió a Inactivo correctamente.',
                'icon' => 'success',
            ]);

            return redirect()->route('usuarios_index');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->route('usuarios_index')
                ->with('error', 'No se pudo eliminar el usuario.');
        }
    }

    // Reglas centrales del formulario. Se mantienen aqui para que crear y editar pidan lo mismo.
    private function validarUsuario(Request $solicitud, ?User $usuario = null): array
    {
        $idUsuario = $usuario?->id;
        $reglasPassword = $usuario
            ? ['nullable', 'required_with:form_password_confirmation', 'string', 'confirmed']
            : ['required', 'string', 'confirmed'];
        $reglasConfirmacionPassword = $usuario
            ? ['nullable', 'required_with:form_password', 'string']
            : ['required', 'string'];

        return $solicitud->validate([
            'form_name' => ['required', 'string', 'max:255'],
            'form_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($idUsuario),
            ],
            'form_password' => $reglasPassword,
            'form_password_confirmation' => $reglasConfirmacionPassword,
            'form_estado' => ['required', 'in:0,1'],
            'form_funcionario_nombres' => ['required', 'string', 'max:255'],
            'form_funcionario_apellido_paterno' => ['required', 'string', 'max:255'],
            'form_funcionario_apellido_materno' => ['nullable', 'string', 'max:255'],
            'form_funcionario_carnet' => [
                'required',
                'string',
                'max:50',
                Rule::unique('funcionarios', 'carnet')->ignore($usuario?->funcionario?->id),
            ],
            'form_funcionario_telefono' => ['nullable', 'string', 'max:50'],
            'form_funcionario_genero' => ['required', 'in:0,1'],
            'form_cargos' => ['nullable', 'array'],
            'form_cargos.*' => ['integer', 'exists:cargos,id'],
            'form_cargos_nuevos' => ['nullable', 'array'],
            'form_cargos_nuevos.*' => ['array:nombre,id_area,descripcion,estado'],
            'form_cargos_nuevos.*.nombre' => [
                'required',
                'string',
                'max:255',
                'distinct:ignore_case',
                Rule::unique('cargos', 'nombre'),
            ],
            'form_cargos_nuevos.*.id_area' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where(
                    fn ($consulta) => $consulta->where('estado', 1)->whereNull('deleted_at')
                ),
            ],
            'form_cargos_nuevos.*.descripcion' => ['nullable', 'string'],
            'form_cargos_nuevos.*.estado' => ['required', 'in:0,1'],
            'form_roles' => ['required', 'array', 'min:1'],
            'form_roles.*' => ['integer', 'distinct', 'exists:roles,id'],
            'form_permisos' => ['nullable', 'array'],
            'form_permisos.*' => ['integer', 'exists:permisos,id'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'email' => 'El campo :attribute debe ser un correo valido.',
            'unique' => 'El valor de :attribute ya esta registrado.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no debe superar :max caracteres.',
            'confirmed' => 'La confirmación de :attribute no coincide.',
            'required_with' => 'El campo :attribute es obligatorio cuando se cambia la contraseña.',
            'in' => 'El valor seleccionado en :attribute no es valido.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'distinct' => 'El campo :attribute tiene datos repetidos.',
            'form_roles.required' => 'Debe seleccionar al menos un rol.',
            'form_roles.min' => 'Debe seleccionar al menos un rol.',
        ], [
            'form_name' => 'nombre del usuario',
            'form_email' => 'correo de acceso',
            'form_password' => 'contraseña',
            'form_password_confirmation' => 'confirmación de contraseña',
            'form_estado' => 'estado',
            'form_funcionario_nombres' => 'nombres',
            'form_funcionario_apellido_paterno' => 'apellido paterno',
            'form_funcionario_apellido_materno' => 'apellido materno',
            'form_funcionario_carnet' => 'carnet',
            'form_funcionario_telefono' => 'telefono',
            'form_funcionario_genero' => 'genero',
            'form_cargos' => 'cargos',
            'form_cargos_nuevos' => 'cargos nuevos',
            'form_cargos_nuevos.*' => 'cargo nuevo',
            'form_cargos_nuevos.*.nombre' => 'nombre del cargo nuevo',
            'form_cargos_nuevos.*.id_area' => 'area del cargo nuevo',
            'form_cargos_nuevos.*.descripcion' => 'descripcion del cargo nuevo',
            'form_cargos_nuevos.*.estado' => 'estado del cargo nuevo',
            'form_roles' => 'rol',
            'form_roles.*' => 'rol',
            'form_permisos' => 'permisos directos',
        ]);
    }

    // Une cargos existentes con cargos nuevos creados desde el formulario de usuario.
    private function obtenerCargosFuncionario(array $datos): array
    {
        // Convierte ids seleccionados en enteros validos antes de sincronizar la tabla pivote.
        $cargos = collect($datos['form_cargos'] ?? [])
            ->map(fn ($idCargo) => (int) $idCargo)
            ->filter();

        // Normaliza los datos antes de crear cada cargo dentro de la misma transaccion del usuario.
        collect($datos['form_cargos_nuevos'] ?? [])
            ->map(fn (array $cargo) => [
                'nombre' => trim(preg_replace('/\s+/', ' ', $cargo['nombre'])),
                'id_area' => (int) $cargo['id_area'],
                'descripcion' => filled($cargo['descripcion'] ?? null)
                    ? trim($cargo['descripcion'])
                    : null,
                'estado' => (int) $cargo['estado'],
            ])
            ->unique(fn (array $cargo) => mb_strtolower($cargo['nombre']))
            ->each(function (array $datosCargo) use ($cargos) {
                $cargo = Cargo::create($datosCargo);

                $cargos->push($cargo->id);
            });

        return $cargos->unique()->values()->all();
    }

    // Carga cargos libres. En edicion se conserva el cargo que ya pertenece al funcionario actual.
    private function cargosDisponiblesParaUsuario(?User $usuario = null)
    {
        $idFuncionarioActual = $usuario?->funcionario?->id;

        return Cargo::query()
            ->with('area')
            ->where('estado', 1)
            ->whereDoesntHave('funcionarios', function ($query) use ($idFuncionarioActual) {
                $query->whereHas('usuario', fn ($consultaUsuario) => $consultaUsuario->where('estado', 1));

                if ($idFuncionarioActual) {
                    $query->where('funcionarios.id', '<>', $idFuncionarioActual);
                }
            })
            ->orderBy('nombre')
            ->get();
    }

    // Usa el mismo catalogo activo que el formulario principal de cargos.
    private function areasDisponiblesParaCargo()
    {
        return Area::where('estado', 1)
            ->orderBy('nombre')
            ->get();
    }

    // Protege el guardado por si alguien intenta enviar un cargo ocupado desde una peticion directa.
    private function validarCargosDisponibles(array $datos, ?User $usuario = null): void
    {
        $idsSeleccionados = collect($datos['form_cargos'] ?? [])
            ->map(fn ($idCargo) => (int) $idCargo)
            ->filter()
            ->unique()
            ->values();

        if ($idsSeleccionados->isEmpty()) {
            return;
        }

        $idsDisponibles = $this->cargosDisponiblesParaUsuario($usuario)
            ->pluck('id')
            ->map(fn ($idCargo) => (int) $idCargo);

        $idsNoDisponibles = $idsSeleccionados->diff($idsDisponibles);

        if ($idsNoDisponibles->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form_cargos' => 'Uno de los cargos seleccionados ya está asignado a otro usuario activo.',
            ]);
        }
    }

    // Solo aplica la protección cuando una cuenta activa pasa a Inactivo.
    private function quiereInactivarUsuario(User $usuario, array $datos): bool
    {
        return (string) $usuario->estado === '1'
            && (string) $datos['form_estado'] === '0';
    }

    // La cuenta principal y la sesión actual deben permanecer activas.
    private function motivoQueImpideInactivarUsuario(User $usuario): ?string
    {
        if ($usuario->esSuperAdministrador()) {
            return 'La cuenta superadministrador es necesaria para administrar el sistema.';
        }

        if (auth()->id() === $usuario->id) {
            return 'No puedes cambiar a Inactivo tu propia cuenta mientras la estás usando.';
        }

        return null;
    }
}

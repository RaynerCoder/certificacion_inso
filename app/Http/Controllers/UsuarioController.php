<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Role;
use App\Models\User;
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

            // Crea la ficha laboral del funcionario vinculada uno a uno con la cuenta.
            $funcionario = $usuario->funcionario()->create([
                'nombres' => $datos['form_funcionario_nombres'],
                'apellido_paterno' => $datos['form_funcionario_apellido_paterno'],
                'apellido_materno' => $datos['form_funcionario_apellido_materno'] ?? null,
                'carnet' => $datos['form_funcionario_carnet'],
                'telefono' => $datos['form_funcionario_telefono'] ?? null,
                'genero' => (int) $datos['form_funcionario_genero'],
                'estado' => (int) $datos['form_funcionario_estado'],
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
     * Reservado para una vista de detalle del usuario si el modulo la necesita.
     */
    public function show(User $usuario)
    {
        //
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
        ]);
    }

    /**
     * Actualiza datos del usuario y resincroniza roles/permisos directos.
     */
    public function update(Request $solicitud, User $usuario)
    {
        $datos = $this->validarUsuario($solicitud, $usuario);
        $this->validarCargosDisponibles($datos, $usuario);

        try {
            DB::beginTransaction();

            $campos = [
                'name' => $datos['form_name'],
                'email' => $datos['form_email'],
                'password' => $datos['form_password'],
                'estado' => (int) $datos['form_estado'],
            ];

            $usuario->update($campos);
            $usuario->roles()->sync($datos['form_roles']);
            $usuario->permisosDirectos()->sync($datos['form_permisos'] ?? []);

            // Actualiza o crea la ficha de funcionario en caso de editar un usuario antiguo.
            $funcionario = $usuario->funcionario()->updateOrCreate(
                ['id_usuario' => $usuario->id],
                [
                    'nombres' => $datos['form_funcionario_nombres'],
                    'apellido_paterno' => $datos['form_funcionario_apellido_paterno'],
                    'apellido_materno' => $datos['form_funcionario_apellido_materno'] ?? null,
                    'carnet' => $datos['form_funcionario_carnet'],
                    'telefono' => $datos['form_funcionario_telefono'] ?? null,
                    'genero' => (int) $datos['form_funcionario_genero'],
                    'estado' => (int) $datos['form_funcionario_estado'],
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
     * Elimina logicamente un usuario para mantener su historial.
     */
    public function destroy(User $usuario)
    {
        // Evita que el funcionario elimine su propia cuenta mientras esta conectado.
        if (auth()->id() === $usuario->id) {
            session()->flash('swal', [
                'title' => 'No se puede eliminar',
                'text' => 'No puedes eliminar tu propia cuenta mientras la estas usando.',
                'icon' => 'error',
            ]);

            return redirect()->route('usuarios_index');
        }

        try {
            DB::beginTransaction();

            if ($usuario->funcionario) {
                $usuario->funcionario->cargos()->detach();
                $usuario->funcionario->delete();
            }

            // No se separan roles/permisos porque el usuario queda con SoftDeletes.
            // Asi se conserva el historial y se puede restaurar con sus accesos.
            $usuario->delete();

            DB::commit();

            session()->flash('swal', [
                'title' => 'Eliminado',
                'text' => 'El usuario se elimino correctamente.',
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

        return $solicitud->validate([
            'form_name' => ['required', 'string', 'max:255'],
            'form_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($idUsuario),
            ],
            'form_password' => ['required', 'string', 'min:8', 'confirmed'],
            'form_password_confirmation' => ['required', 'string', 'min:8'],
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
            'form_funcionario_estado' => ['required', 'in:0,1'],
            'form_cargos' => ['nullable', 'array'],
            'form_cargos.*' => ['integer', 'exists:cargos,id'],
            'form_cargos_nuevos' => ['nullable', 'array'],
            'form_cargos_nuevos.*' => ['nullable', 'string', 'max:255', 'distinct:ignore_case'],
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
            'confirmed' => 'La confirmacion de :attribute no coincide.',
            'in' => 'El valor seleccionado en :attribute no es valido.',
            'exists' => 'El valor seleccionado en :attribute no existe.',
            'distinct' => 'El campo :attribute tiene datos repetidos.',
            'form_roles.required' => 'Debe seleccionar al menos un rol.',
            'form_roles.min' => 'Debe seleccionar al menos un rol.',
        ], [
            'form_name' => 'nombre del usuario',
            'form_email' => 'correo de acceso',
            'form_password' => 'contrasena',
            'form_password_confirmation' => 'confirmacion de contrasena',
            'form_estado' => 'estado',
            'form_funcionario_nombres' => 'nombres del funcionario',
            'form_funcionario_apellido_paterno' => 'apellido paterno',
            'form_funcionario_apellido_materno' => 'apellido materno',
            'form_funcionario_carnet' => 'carnet',
            'form_funcionario_telefono' => 'telefono',
            'form_funcionario_genero' => 'genero',
            'form_funcionario_estado' => 'estado del funcionario',
            'form_cargos' => 'cargos',
            'form_cargos_nuevos' => 'cargos nuevos',
            'form_cargos_nuevos.*' => 'cargo nuevo',
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

        // Normaliza nombres para evitar duplicados como "Jefe   Tecnico" y "Jefe Tecnico".
        collect($datos['form_cargos_nuevos'] ?? [])
            ->map(fn ($nombreCargo) => trim(preg_replace('/\s+/', ' ', (string) $nombreCargo)))
            ->filter()
            ->unique(fn ($nombreCargo) => mb_strtolower($nombreCargo))
            ->each(function (string $nombreCargo) use ($cargos) {
                // Si el cargo ya existe, se reutiliza; si no, se crea activo.
                $cargo = Cargo::firstOrCreate(
                    ['nombre' => $nombreCargo],
                    ['estado' => 1]
                );

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
                $query->where('funcionarios.estado', 1);

                if ($idFuncionarioActual) {
                    $query->where('funcionarios.id', '<>', $idFuncionarioActual);
                }
            })
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
                'form_cargos' => 'Uno de los cargos seleccionados ya esta asignado a otro funcionario activo.',
            ]);
        }
    }
}

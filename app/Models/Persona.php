<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model
{
    use Auditable, SoftDeletes;

    protected $table = 'personas';

    protected $fillable = [
        'id_usuario',
        'domicilio',
        'nit',
        'correo',
        'id_territorio',
        'estado',
    ];

    // Cuenta propia de una persona natural. En registros antiguos una empresa tambien puede tenerla.
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    // Relacion uno a uno: datos especificos cuando la persona es natural.
    public function natural()
    {
        return $this->hasOne(Natural::class, 'id_persona');
    }

    // Relacion uno a uno: datos especificos cuando la persona es juridica/empresa.
    public function empresa()
    {
        return $this->hasOne(Empresa::class, 'id_persona');
    }

    // Empresas donde esta persona actua como responsable, representante legal o tramitador.
    public function responsabilidadesEmpresariales()
    {
        return $this->hasMany(Responsable::class, 'id_persona');
    }

    public function usuarioAcceso(): ?User
    {
        $personaActiva = strtoupper((string) $this->estado) === 'ACTIVO';

        if (! $personaActiva) {
            return null;
        }

        if ($this->empresa && strtoupper((string) $this->empresa->estado) === 'ACTIVO') {
            $representante = $this->empresa->responsables()
                ->whereIn('estado', ['1', 'ACTIVO'])
                ->whereHas('persona', fn ($persona) => $persona->where('estado', 'ACTIVO'))
                ->whereHas('rol', fn ($rol) => $rol
                    ->where('slug', 'representante-legal')
                    ->where('estado', 1))
                ->with('persona.usuario')
                ->latest('id')
                ->first();

            $usuarioRepresentante = $representante?->persona?->usuario;

            if ($this->usuarioEstaActivo($usuarioRepresentante)) {
                return $usuarioRepresentante;
            }
        }

        // Compatibilidad temporal para personas naturales y empresas creadas con la estructura anterior.
        return $this->usuarioEstaActivo($this->usuario) ? $this->usuario : null;
    }

    private function usuarioEstaActivo(?User $usuario): bool
    {
        return $usuario && (string) $usuario->estado === '1';
    }

    // Relacion muchos a uno: territorio asociado a la persona.
    public function territorio()
    {
        return $this->belongsTo(Territorio::class, 'id_territorio');
    }

    // Relacion uno a muchos: productos donde esta persona actua como importador.
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_importador_persona');
    }

    // Relacion uno a muchos: telefonos de contacto.
    public function telefonos()
    {
        return $this->hasMany(Telefono::class, 'id_persona');
    }

    // Relacion muchos a muchos: rubros o actividades vinculadas a la persona o empresa.
    public function rubros()
    {
        return $this->belongsToMany(Rubro::class, 'personas_rubros', 'id_persona', 'id_rubro')
            ->withPivot('estado');
    }

    // Relacion uno a muchos: certificados donde la persona es beneficiaria.
    public function certificadosComoBeneficiario()
    {
        return $this->hasMany(Certificado::class, 'id_persona_beneficiario');
    }

    // Relacion uno a muchos: certificados donde la persona realiza el tramite.
    public function certificadosComoTramitador()
    {
        return $this->hasMany(Certificado::class, 'id_persona_tramitador');
    }
}

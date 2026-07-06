<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Persona extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'personas';

    protected $fillable = [
        'id_usuario',
        'domicilio',
        'nit',
        'correo',
        'id_territorio',
        'estado',
    ];

    // Relacion uno a uno inversa: cuenta con la que esta persona/empresa inicia sesion.
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

    // Relacion uno a muchos: rubros o actividades que se registran para personas naturales.
    public function rubros()
    {
        return $this->hasMany(Rubro::class, 'id_persona');
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

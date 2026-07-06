<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Seguimiento extends Model
{
    use SoftDeletes, Auditable;

    // La tabla fisica se mantiene como seguimientos para respetar la base de datos actual.
    // En la interfaz este modelo representa el flujo de tramite_seguimientos.
    protected $table = 'seguimientos';
    protected $fillable = [
        'id_seguimiento_padre',
        'id_certificado',
        'fecha_inicio',
        'fecha_derivacion',
        'fecha_final',
        'descripcion_final',
        'referencia',
        'id_usuario_anterior',
        'id_usuario_origen',
        'id_usuario_siguiente',
        'estado',
    ];

    // Relacion muchos a uno (un tramite seguimiento puede partir de un movimiento padre).
    public function seguimientoPadre()
    {
        return $this->belongsTo(Seguimiento::class, 'id_seguimiento_padre');
    }

    // Relacion uno a muchos (un tramite seguimiento padre puede tener movimientos hijos).
    public function seguimientosHijos()
    {
        return $this->hasMany(Seguimiento::class, 'id_seguimiento_padre');
    }

    // Relacion muchos a uno (cada movimiento pertenece al certificado/tramite solicitado).
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Usuario que tenia el tramite antes de este movimiento.
    public function usuarioAnterior()
    {
        return $this->belongsTo(User::class, 'id_usuario_anterior');
    }

    // Usuario que genera o envia este movimiento del tramite.
    public function usuarioOrigen()
    {
        return $this->belongsTo(User::class, 'id_usuario_origen');
    }

    // Usuario o funcionario que recibira el siguiente paso del tramite.
    public function usuarioSiguiente()
    {
        return $this->belongsTo(User::class, 'id_usuario_siguiente');
    }
}

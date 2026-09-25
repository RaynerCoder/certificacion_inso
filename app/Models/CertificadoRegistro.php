<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificadoRegistro extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'certificados_registros';
    protected $fillable = [
        'id',
        'id_certificado',
        'id_registro',
    ];

    // Relacion muchos a uno (muchas asignaciones pertenecen a un certificado)
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Relacion muchos a uno (muchas asignaciones pertenecen a un registro)
    public function registro()
    {
        return $this->belongsTo(Registro::class, 'id_registro');
    }

    // Una fila de lote solo puede originar un certificado emitido.
    public function emision()
    {
        return $this->hasOne(CertificadoEmitido::class, 'id_certificado_registro');
    }
}

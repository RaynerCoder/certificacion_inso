<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PagoCertificado extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'pagos_certificados';

    protected $fillable = [
        'id_certificado',
        'id_pago',
    ];

    // Relacion muchos a uno (muchos pagos certificados pertenecen a un certificado)
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Relacion muchos a uno (muchos pagos certificados pertenecen a un pago)
    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }
}

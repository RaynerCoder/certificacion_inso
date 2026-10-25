<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificadoProducto extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'certificados_productos';

    protected $fillable = [
        'id_certificado',
        'id_producto',
    ];

    // Relación muchos a uno: la asignación pertenece a un trámite.
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Relación muchos a uno: la asignación pertenece a un producto.
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

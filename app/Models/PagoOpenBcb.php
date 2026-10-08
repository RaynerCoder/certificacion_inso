<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class PagoOpenBcb extends Model
{
    use SoftDeletes;

    protected $table = 'pagos_openbcb';

    // Campos que se pueden guardar mediante create() o update().
    protected $fillable = [
        'id_pago',
        'id_qr',
        'imagen_qr_path',
    ];

    // Relacion muchos a uno (muchos códigos QR de OpenBCB pueden pertenecer a un pago)
    public function pago()
    {
        return $this->belongsTo(Pago::class, 'id_pago');
    }

    // Relacion uno a muchos (un código QR de OpenBCB puede tener varias órdenes de pago)
    public function ordenes()
    {
        return $this->hasMany(
            PagoOpenBcbOrden::class,
            'id_pago_openbcb'
        );
    }    
}
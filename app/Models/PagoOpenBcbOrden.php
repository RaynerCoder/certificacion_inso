<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoOpenBcbOrden extends Model
{
    protected $table = 'pagos_openbcb_ordenes';

    protected $fillable = [
        'id_pago_openbcb',
        'id_orden_destinatario',
        'cod_moneda',
        'importe',
        'fecha',
        'ci_nit_originante',
        'nombre_originante',
        'cuenta_origen',
        'eif_origen',
        'estado',
        'eif',
        'tipo_notificacion',
        'metadata',
    ];

    // Convierte automáticamente estos campos a tipos utilizables en Laravel.
    protected $casts = [
        'importe' => 'decimal:2',
        'fecha' => 'datetime',
        'metadata' => 'array',
    ];

    // Relacion muchos a uno (muchas órdenes de pago pertenecen a un código QR de OpenBCB)
    public function pagoOpenBcb()
    {
        return $this->belongsTo(
            PagoOpenBcb::class,
            'id_pago_openbcb'
        );
    }

    
}
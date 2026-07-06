<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'pagos';
    
    protected $fillable = [
        'id_procedencia',
        'tipo_pago',
        'fecha',
        'comprobante',
        'monto',
        'id_cliente',
        'id_funcionario',
        'fecha_validacion',
        'factura',
    ];

    public const TIPOS_PAGOS = [
        'DEPOSITO'      => 'Depósito bancario',
        'TRANSFERENCIA' => 'Transferencia bancaria',
        'QR'            => 'Pago mediante QR',
        'CAJA'          => 'Pago en caja',
        'CHEQUE'        => 'Cheque',
        'GIRO'          => 'Giro bancario',
        'ORDEN_PAGO'    => 'Orden de pago',
        'TARJETA_DEBITO'=> 'Tarjeta de débito',
        'TARJETA_CREDITO'=> 'Tarjeta de crédito',
        'BILLETERA'     => 'Billetera móvil',
        'ONLINE'        => 'Pago en línea',
        'OTRO'          => 'Otro',
    ];

    // Relacion muchos a uno (muchos pagos pertenecen a una procedencia)
    public function procedencia()
    {
        return $this->belongsTo(Procedencia::class, 'id_procedencia');
    }

    // Relacion muchos a uno (muchos pagos pertenecen a un cliente/persona)
    public function clientePersona()
    {
        return $this->belongsTo(Persona::class, 'id_cliente');
    }

    // Relacion muchos a uno (muchos pagos son validados por un usuario)
    public function funcionarioUsuario()
    {
        return $this->belongsTo(User::class, 'id_funcionario');
    }

    // Relacion uno a muchos (un pago tiene muchas asignaciones con certificados)
    public function pagosCertificados()
    {
        return $this->hasMany(PagoCertificado::class, 'id_pago');
    }

    // Relacion muchos a muchos (muchos pagos pertenecen a muchos certificados)
    public function certificados()
    {
        return $this->belongsToMany(
            Certificado::class,
            'pagos_certificados',
            'id_pago',
            'id_certificado'
        )
            ->withPivot('id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

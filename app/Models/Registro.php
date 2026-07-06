<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Registro extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'registros';
    protected $fillable = [
        'id_producto',
        'codigo_autorizacion',
        'fecha_vigencia',
        'cantidad',
        'unidad',
        'id_presentacion',
        'estado',
    ];

    // Relación muchos a uno (muchos registros pertenecen a un producto)
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    // Relación muchos a uno (muchos registros pertenecen a una presentacion)
    public function presentacion()
    {
        return $this->belongsTo(Presentacion::class, 'id_presentacion');
    }

    // Relacion uno a muchos (un registro tiene muchas asignaciones con certificados)
    public function certificadosRegistros()
    {
        return $this->hasMany(CertificadoRegistro::class, 'id_registro');
    }

    // Relación muchos a muchos (muchos registros pertenecen a muchos certificados)
    public function certificados()
    {
        return $this->belongsToMany(
            Certificado::class,
            'certificados_registros',
            'id_registro',
            'id_certificado'
        )
            ->withPivot('id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

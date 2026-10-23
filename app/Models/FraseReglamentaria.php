<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FraseReglamentaria extends Model
{
    use SoftDeletes, Auditable;

    public const TIPOS = [
        'RIESGO' => 'Riesgo',
        'SEGURIDAD' => 'Seguridad',
    ];

    protected $table = 'frases_reglamentarias';

    protected $fillable = [
        'tipo',
        'codigo',
        'descripcion',
        'estado',
    ];

    /**
     * El código es la identificación oficial de la frase (R1, S1, R20/21, etc.).
     * Se guarda normalizado para que R1 y r1 no puedan coexistir.
     */
    public function setCodigoAttribute(?string $valor): void
    {
        $this->attributes['codigo'] = $valor === null ? null : mb_strtoupper(trim($valor), 'UTF-8');
    }

    public function setTipoAttribute(?string $valor): void
    {
        $this->attributes['tipo'] = $valor === null ? null : mb_strtoupper(trim($valor), 'UTF-8');
    }

    // Relación uno a muchos: una frase puede estar asignada a varios productos.
    public function frasesReglamentariasProductos()
    {
        return $this->hasMany(FraseReglamentariaProducto::class, 'id_frase_reglamentaria');
    }

    // Relación muchos a muchos con los productos que utilizan esta frase.
    public function productos()
    {
        return $this->belongsToMany(
            Producto::class,
            'frases_reglamentarias_productos',
            'id_frase_reglamentaria',
            'id_producto'
        )
            ->withPivot('id', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

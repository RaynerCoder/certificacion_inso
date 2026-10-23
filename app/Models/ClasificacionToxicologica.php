<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClasificacionToxicologica extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'clasificaciones_toxicologicas';

    protected $fillable = [
        'descripcion',
        'codigo',
        'estado',
    ];

    public function setCodigoAttribute(?string $valor): void
    {
        $this->attributes['codigo'] = $valor === null ? null : mb_strtoupper(trim($valor), 'UTF-8');
    }

    public function setDescripcionAttribute(?string $valor): void
    {
        $this->attributes['descripcion'] = $valor === null ? null : mb_strtoupper(trim($valor), 'UTF-8');
    }

    // Productos asociados a esta clasificación toxicológica.
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_clasificacion_toxicologica');
    }
}

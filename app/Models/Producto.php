<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Producto extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'productos';
    protected $fillable = [
        'id_importador_persona',
        'id_territorio_pais',
        'id_fabricante',
        'id_tipo_producto',
        'codigo',
        'nombre_comercial',
        'nombre_cientifico',
        'clasificacion',
        'estado',
    ];
    
    
    // Relación muchos a uno (muchos productos pertenecen a un territorio)
    public function territorio()
    {
        return $this->belongsTo(Territorio::class, 'id_territorio_pais');
    }
    
    // Relación muchos a uno (muchos productos pertenecen a un fabricante)
    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class, 'id_fabricante');
    }     
    
    // Relación muchos a uno (muchos productos pertenecen a un tipo de producto)
    public function tipoProducto()
    {
        return $this->belongsTo(TipoProducto::class, 'id_tipo_producto');
    }
    
    // Relación muchos a uno (muchos productos pertenecen a una persona que es el importador)
    public function importadorPersona()
    {
        return $this->belongsTo(Persona::class, 'id_importador_persona');
    }

    // Relación uno a muchos (un producto tiene muchas presentaciones)
    public function presentaciones()
    {
        return $this->hasMany(Presentacion::class, 'id_producto');
    }

    // Relación uno a muchos (un producto tiene muchos registros)
    public function registros()
    {
        return $this->hasMany(Registro::class, 'id_producto');
    }

    // Relación uno a muchos (un producto esta en muchas aduanas)
    public function aduanas()
    {
        return $this->hasMany(Aduana::class, 'id_producto');
    }

    // Relacion uno a muchos (un producto tiene muchas asignaciones de ingredientes)
    public function ingredientesProductos()
    {
        return $this->hasMany(IngredienteProducto::class, 'id_producto');
    }

    // Relación muchos a muchos (muchos productos tienen muchos ingredientes)
    public function ingredientes()
    {
        return $this->belongsToMany(
            Ingrediente::class,
            'ingredientes_productos',
            'id_producto',
            'id_ingrediente'
        )
            ->withPivot('id', 'porcentaje', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

/*
belongsToMany(
    ModeloRelacionado,
    tabla_pivote,
    fk_modelo_actual,
    fk_modelo_relacionado
)
*/

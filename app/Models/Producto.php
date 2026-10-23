<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Producto extends Model
{
    use SoftDeletes, Auditable;

    // Tipos permitidos al registrar un producto.
    public const TIPOS_PRODUCTO = [
        'PLAGUICIDA' => 'Plaguicida',
        'MATERIA PRIMA' => 'Materia prima',
    ];

    protected $table = 'productos';
    protected $fillable = [
        'id_importador_persona',
        'id_territorio_pais',
        'id_fabricante',
        'id_clasificacion_toxicologica',
        'codigo',
        'nombre_comercial',
        'tipo_producto',
        'nombre_tecnico',
        'id_clasificacion_quimica',
        'observacion_toxicologica_muestra',
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
    
    // Clasificación toxicológica asignada al producto.
    public function clasificacionToxicologica()
    {
        return $this->belongsTo(ClasificacionToxicologica::class, 'id_clasificacion_toxicologica');
    }

    // Clasificación química asignada al producto.
    public function clasificacionQuimica()
    {
        return $this->belongsTo(ClasificacionQuimica::class, 'id_clasificacion_quimica');
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

    // Relación uno a muchos con las asignaciones de frases reglamentarias.
    public function frasesReglamentariasProductos()
    {
        return $this->hasMany(FraseReglamentariaProducto::class, 'id_producto');
    }

    // Relación muchos a muchos con frases de riesgo y seguridad.
    public function frasesReglamentarias()
    {
        return $this->belongsToMany(
            FraseReglamentaria::class,
            'frases_reglamentarias_productos',
            'id_producto',
            'id_frase_reglamentaria'
        )
            ->withPivot('id', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Procedencia extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'procedencias';
    protected $fillable = [
        'codigo',
        'descripcion',
    ];

    // Relacion uno a muchos (una procedencia tiene muchos pagos)
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'id_procedencia');
    }
}

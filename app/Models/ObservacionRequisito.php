<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ObservacionRequisito extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'observaciones_requisitos';

    protected $fillable = [
        'id_revision_requisito',
        'observacion',
        'estado',
    ];

    // Relación muchos a uno (muchas observaciones pertenecen a una revisión)
    public function revisionRequisito()
    {
        return $this->belongsTo(RevisionRequisito::class, 'id_revision_requisito');
    }
}

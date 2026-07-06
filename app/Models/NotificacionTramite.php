<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificacionTramite extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'notificaciones_tramites';

    protected $fillable = [
        'id_usuario_destino',
        'id_usuario_emisor',
        'id_certificado',
        'titulo',
        'mensaje',
        'fecha_visto',
        'estado',
    ];

    protected $casts = [
        'fecha_visto' => 'datetime',
    ];

    // Usuario que recibe la notificacion en la campana.
    public function usuarioDestino()
    {
        return $this->belongsTo(User::class, 'id_usuario_destino');
    }

    // Usuario que genero o envio la notificacion.
    public function usuarioEmisor()
    {
        return $this->belongsTo(User::class, 'id_usuario_emisor');
    }

    // Solicitud/tramite relacionado a la notificacion.
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }
}

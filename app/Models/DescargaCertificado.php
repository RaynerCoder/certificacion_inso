<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescargaCertificado extends Model
{
    protected $table = 'descargas_certificados';

    public $timestamps = false;

    protected $fillable = [
        'id_certificado',
        'id_usuario',
        'fecha_descarga',
    ];

    protected $casts = [
        'fecha_descarga' => 'datetime',
    ];

    // Certificado que fue descargado.
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Cuenta que realizó la descarga.
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}

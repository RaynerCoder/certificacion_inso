<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DescargaCertificado extends Model
{
    protected $table = 'descargas_certificados';

    public $timestamps = false;

    protected $fillable = [
        'id_certificado',
        'id_certificado_emitido',
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

    // Documento específico descargado. Es nulo para descargas históricas.
    public function certificadoEmitido()
    {
        return $this->belongsTo(CertificadoEmitido::class, 'id_certificado_emitido');
    }

    // Cuenta que realizó la descarga.
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}

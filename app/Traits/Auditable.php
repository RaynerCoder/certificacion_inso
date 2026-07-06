<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::creating(function ($modelo) {
            if (auth()->check()) {
                // Usuario que crea el registro por primera vez.
                if (static::tieneColumnaAuditoria($modelo, 'id_usuario_registro')) {
                    $modelo->id_usuario_registro = auth()->id();
                }

                // Al crear, tambien queda como ultimo usuario que dejo vigente el registro.
                if (static::tieneColumnaAuditoria($modelo, 'id_usuario_modificacion')) {
                    $modelo->id_usuario_modificacion = auth()->id();
                }
            }
        });

        static::updating(function ($modelo) {
            if (auth()->check() && static::tieneColumnaAuditoria($modelo, 'id_usuario_modificacion')) {
                $modelo->id_usuario_modificacion = auth()->id();
            }
        });

        static::deleting(function ($modelo) {
            if (auth()->check() && static::tieneColumnaAuditoria($modelo, 'id_usuario_eliminacion')) {
                $modelo->id_usuario_eliminacion = auth()->id();
                $modelo->saveQuietly();
            }
        });
    }

    // Verifica si la tabla tiene la columna antes de asignarla.
    protected static function tieneColumnaAuditoria($modelo, string $columna): bool
    {
        static $cache = [];

        $tabla = $modelo->getTable();
        $clave = $tabla . '.' . $columna;

        if (!array_key_exists($clave, $cache)) {
            $cache[$clave] = Schema::hasColumn($tabla, $columna);
        }

        return $cache[$clave];
    }
}

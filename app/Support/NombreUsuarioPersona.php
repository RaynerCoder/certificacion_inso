<?php

namespace App\Support;

use Illuminate\Support\Str;

class NombreUsuarioPersona
{
    public static function generar(?string $nombres, ?string $apellidoPaterno): string
    {
        $primerNombre = preg_split('/\s+/u', trim((string) $nombres))[0] ?? '';
        $normalizar = fn (string $valor) => preg_replace('/[^a-z]/', '', strtolower(Str::ascii($valor)));

        return $normalizar($primerNombre) . '_' . $normalizar((string) $apellidoPaterno);
    }
}

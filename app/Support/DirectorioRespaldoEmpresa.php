<?php

namespace App\Support;

use App\Models\Role;

class DirectorioRespaldoEmpresa
{
    public static function paraRol(?int $idEmpresa, int $idRol): string
    {
        $tipo = Role::findOrFail($idRol)->slug === 'tramitador'
            ? 'tramitadores'
            : 'representantes-legales';

        return 'empresas-tramitadores/' . ($idEmpresa ?? 'pendientes') . '/' . $tipo;
    }
}

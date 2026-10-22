<?php

namespace App\Support;

use App\Models\Role;

class DirectorioRespaldoEmpresa
{
    public static function paraRol(?int $idEmpresa, int $idRol): string
    {
        $empresa = $idEmpresa ?? 'pendientes';

        if (Role::findOrFail($idRol)->slug === 'tramitador') {
            return 'tramitadores/empresas/'.$empresa;
        }

        return 'personas-empresas/empresas/'.$empresa.'/representantes-legales';
    }
}

<?php

namespace App\Support;

class BandejaTramite
{
    public static function retorno(?string $bandeja): array
    {
        return match ($bandeja) {
            'enviadas' => ['Mis trámites', 'seguimientos_mis_tramites_beneficiario'],
            'registrados' => ['Trámites registrados por mí', 'seguimientos_tramites_registrados_funcionario'],
            'todos' => ['Seguimiento de trámites', 'seguimientos_todos'],
            'finalizados' => ['Trámites finalizados', 'seguimientos_finalizados'],
            default => ['Trámites para atender', 'seguimientos_index'],
        };
    }
}

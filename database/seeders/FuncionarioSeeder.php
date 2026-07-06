<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class FuncionarioSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Fichas laborales de los usuarios internos iniciales.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 'Administrador', 'Sistema', null, 'ADMIN-001', '00000000', 1],
            2 => [2, 'Tecnico', 'Evaluador', null, 'TEC-001', '00000001', 0],
            3 => [3, 'Caja', 'Pagos', null, 'CAJ-001', '00000002', 1],
            4 => [4, 'Rene', 'Huanca', 'Poma', '3379243 LP', null, 1],
            5 => [5, 'Max Reynaldo', 'Munoz', 'Moreno', '2606554 LP', null, 1],
            6 => [6, 'Freddy', 'Santos', 'Mancilla', '2499885 LP', null, 1],
            7 => [7, 'David', 'Laruta', 'Onofre', '6788323 LP', null, 1],
            8 => [10, 'Estela', 'Lluito', 'Quenta', '2617166 LP', null, 0],
            9 => [11, 'Ana Maria', 'Bustillos', 'Vargas', '2362861 LP', null, 0],
            10 => [12, 'Guillermo Alejandro', 'Zamora', 'Kraljevic', '4838885 LP', null, 1],
            11 => [13, 'Angela Paola', 'Soria', 'de La Torre', '4261889 LP', null, 0],
            12 => [14, 'Armando', 'Ale', 'Quispe', '4775938 LP', null, 1],
        ] as $id => [$idUsuario, $nombres, $apellidoPaterno, $apellidoMaterno, $carnet, $telefono, $genero]) {
            $this->guardar('funcionarios', $id, [
                'id_usuario' => $idUsuario,
                'nombres' => $nombres,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'carnet' => $carnet,
                'telefono' => $telefono,
                'genero' => $genero,
                'estado' => $this->estado('funcionarios'),
            ]);
        }
    }
}

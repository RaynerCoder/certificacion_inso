<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Usuarios base para auditoria, pagos, seguimientos y permisos.
     */
    public function run(): void
    {
        foreach ([
            1 => ['Super Administrador', 'super.admin@gmail.com'],
            2 => ['Tecnico Evaluador', 'tecnico@certificador.test'],
            3 => ['Caja Pagos', 'caja@certificador.test'],
            4 => ['rhuanca', 'rhuanca@inso.gob.bo'],
            5 => ['mmunoz', 'mmunoz@inso.gob.bo'],
            6 => ['fsantos', 'fsantos@inso.gob.bo'],
            7 => ['dlaruta', 'dlaruta@inso.gob.bo'],
            8 => ['AGROPARC EI S.R.L.', 'empresa@certificador.test'],
            9 => ['Mario Erwin Pedraza Merida', 'natural@certificador.test'],
            10 => ['elluito', 'elluito@inso.gob.bo'],
            11 => ['abustillos', 'abustillos@inso.gob.bo'],
            12 => ['gzamora', 'gzamora@inso.gob.bo'],
            13 => ['asoria', 'asoria@inso.gob.bo'],
            14 => ['aale', 'aale@inso.gob.bo'],
            15 => ['BIOCONTROL BOLIVIA S.R.L.', 'biocontrol@certificador.test'],
            16 => ['Laura Gabriela Torres Lima', 'laura.tramitadora@certificador.test'],
            17 => ['Carlos Andres Quispe Rojas', 'carlos.natural@certificador.test'],
        ] as $id => [$name, $email]) {
            $this->guardar('users', $id, [
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($id === 1 ? 'Inso1103*' : '12345678'),
                'estado' => $this->estado('users'),
            ]);
        }
    }
}

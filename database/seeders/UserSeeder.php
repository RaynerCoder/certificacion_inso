<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Usuarios base para auditoria, pagos, seguimientos y permisos.
     */
    public function run(): void
    {
        // Las empresas no inician sesión por sí mismas. Si estos usuarios existen de una
        // carga anterior, quedan inactivos y se conservan para no romper referencias.
        DB::table('users')
            ->whereIn('id', [8, 15])
            ->update([
                'estado' => $this->estado('users', false),
                'updated_at' => now(),
            ]);

        foreach ([
            2 => ['Tecnico Evaluador', 'tecnico@certificador.test'],
            3 => ['Caja Pagos', 'caja@certificador.test'],
            4 => ['rhuanca', 'rhuanca@inso.gob.bo'],
            5 => ['mmunoz', 'mmunoz@inso.gob.bo'],
            6 => ['fsantos', 'fsantos@inso.gob.bo'],
            7 => ['dlaruta', 'dlaruta@inso.gob.bo'],
            9 => ['Mario Erwin Pedraza Merida', 'mario.representante@certificador.test'],
            10 => ['elluito', 'elluito@inso.gob.bo'],
            11 => ['abustillos', 'abustillos@inso.gob.bo'],
            12 => ['gzamora', 'gzamora@inso.gob.bo'],
            13 => ['asoria', 'asoria@inso.gob.bo'],
            14 => ['aale', 'aale@inso.gob.bo'],
            16 => ['Laura Gabriela Torres Lima', 'laura.representante@certificador.test'],
            17 => ['Carlos Andres Quispe Rojas', 'carlos.natural@certificador.test'],
        ] as $id => [$name, $email]) {
            $this->guardar('users', $id, [
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('12345678'),
                'estado' => $this->estado('users'),
            ]);
        }
    }
}

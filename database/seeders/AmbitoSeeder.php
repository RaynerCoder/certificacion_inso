<?php

namespace Database\Seeders;

use App\Models\Ambito;
use Illuminate\Database\Seeder;

class AmbitoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ambitos = [
            ['id' => 1, 'nombre' => 'Pais', 'estado' => 1],
            ['id' => 2, 'nombre' => 'Departamento', 'estado' => 1],
            ['id' => 3, 'nombre' => 'Provincia', 'estado' => 1],
            ['id' => 4, 'nombre' => 'Municipio', 'estado' => 1],
            ['id' => 5, 'nombre' => 'Ciudad', 'estado' => 1],
            ['id' => 6, 'nombre' => 'Distrito', 'estado' => 1],
            ['id' => 7, 'nombre' => 'Zona', 'estado' => 1],
            ['id' => 8, 'nombre' => 'Barrio', 'estado' => 1],
        ];

        foreach ($ambitos as $ambito) {
            Ambito::updateOrCreate(
                ['id' => $ambito['id']],
                [
                    'nombre' => $ambito['nombre'],
                    'estado' => $ambito['estado'],
                ]
            );
        }
    }
}

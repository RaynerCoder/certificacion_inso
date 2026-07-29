<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RubroSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('El catálogo CAEB de prueba no se puede reemplazar en producción.');
        }

        $nodosCaeb = $this->leerCatalogoCaebOficial();

        DB::transaction(function () use ($nodosCaeb) {
            // Solo en pruebas: deja un catálogo determinístico y sin códigos inventados.
            DB::table('personas_rubros')->delete();
            DB::table('rubros')->delete();

            collect($nodosCaeb)
                ->map(fn (array $nodo) => collect($nodo)->except('codigo_padre')->all())
                ->chunk(100)
                ->each(fn ($lote) => DB::table('rubros')->insert($lote->all()));

            $idsPorCodigo = DB::table('rubros')->pluck('id', 'codigo_caeb');

            foreach ($nodosCaeb as $nodo) {
                $codigoPadre = $nodo['codigo_padre'];

                if ($codigoPadre === '') {
                    continue;
                }

                $idHijo = $idsPorCodigo[$nodo['codigo_caeb']] ?? null;
                $idPadre = $idsPorCodigo[$codigoPadre] ?? null;

                if (! $idHijo || ! $idPadre) {
                    throw new RuntimeException(
                        "No se pudo relacionar {$nodo['codigo_caeb']} con {$codigoPadre}."
                    );
                }

                DB::table('rubros')
                    ->where('id', $idHijo)
                    ->update(['id_rubro_padre' => $idPadre]);
            }

            $asignaciones = [
                ['id_persona' => 1, 'codigo_caeb' => '46692'],
                ['id_persona' => 1, 'codigo_caeb' => '46102'],
                ['id_persona' => 2, 'codigo_caeb' => '82193'],
                ['id_persona' => 4, 'codigo_caeb' => '82110'],
                // Reutilización intencional de la misma subclase en otra persona.
                ['id_persona' => 5, 'codigo_caeb' => '82193'],
            ];

            $idsRubros = DB::table('rubros')
                ->whereIn('codigo_caeb', collect($asignaciones)->pluck('codigo_caeb')->unique())
                ->where('nivel_caeb', 'SUBCLASE')
                ->pluck('id', 'codigo_caeb');

            foreach ($asignaciones as $asignacion) {
                $idRubro = $idsRubros[$asignacion['codigo_caeb']] ?? null;

                if (! $idRubro) {
                    throw new RuntimeException(
                        "No se encontró el código CAEB {$asignacion['codigo_caeb']} para los datos de prueba."
                    );
                }

                DB::table('personas_rubros')->updateOrInsert(
                    [
                        'id_persona' => $asignacion['id_persona'],
                        'id_rubro' => $idRubro,
                    ],
                    ['estado' => 'ACTIVO']
                );
            }
        });
    }

    /**
     * Lee los cinco niveles del mismo SQL oficial entregado para producción,
     * evitando mantener un segundo catálogo divergente.
     */
    private function leerCatalogoCaebOficial(): array
    {
        $ruta = database_path('SQL/02_cargar_datos_rubros_caeb_2022.sql');

        if (! is_file($ruta)) {
            throw new RuntimeException("No se encontró el catálogo CAEB en {$ruta}.");
        }

        $nodos = [];
        $ahora = now();

        foreach (file($ruta, FILE_IGNORE_NEW_LINES) ?: [] as $numeroLinea => $linea) {
            if (! preg_match("/^\\('(?:[A-U]|[0-9]{2,5})'/u", $linea)) {
                continue;
            }

            preg_match_all("/'((?:''|[^'])*)'/u", $linea, $coincidencias);
            $campos = array_map(
                fn (string $valor) => str_replace("''", "'", $valor),
                $coincidencias[1] ?? []
            );

            if (count($campos) !== 4) {
                throw new RuntimeException(
                    'Registro CAEB inválido en la línea ' . ($numeroLinea + 1) . '.'
                );
            }

            $nodos[] = [
                'id_rubro_padre' => null,
                'codigo_caeb' => $campos[0],
                'nivel_caeb' => $campos[1],
                'codigo_padre' => $campos[2],
                'nombre' => $campos[3],
                'estado' => 'ACTIVO',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        $conteosEsperados = [
            'SECCION' => 21,
            'DIVISION' => 88,
            'GRUPO' => 238,
            'CLASE' => 422,
            'SUBCLASE' => 989,
        ];
        $conteos = collect($nodos)->countBy('nivel_caeb')->all();

        if ($conteos !== $conteosEsperados) {
            throw new RuntimeException(
                'La jerarquía CAEB de prueba no coincide con los conteos publicados.'
            );
        }

        return $nodos;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Consulta únicamente la fórmula molecular publicada por PubChem.
 * No calcula fórmulas ni crea datos cuando la búsqueda no identifica una sola sustancia.
 */
class FormulaQuimicaPubChemService
{
    private const BASE_URL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug';

    /**
     * @return array{estado: 'ENCONTRADA'|'NO_ENCONTRADA'|'AMBIGUA', formula_quimica?: string}
     */
    public function buscarPorNombre(string $nombreIngrediente): array
    {
        $nombreIngrediente = trim($nombreIngrediente);

        if ($nombreIngrediente === '') {
            return ['estado' => 'NO_ENCONTRADA'];
        }

        $respuestaCids = $this->consultar(
            self::BASE_URL.'/compound/name/'.rawurlencode($nombreIngrediente).'/cids/JSON'
        );

        if ($respuestaCids === null) {
            return ['estado' => 'NO_ENCONTRADA'];
        }

        $cids = collect($respuestaCids->json('IdentifierList.CID', []))
            ->filter(fn ($cid) => is_numeric($cid))
            ->unique()
            ->values();

        // Si la fuente identifica más de una sustancia, no se elige una por cuenta propia.
        if ($cids->count() !== 1) {
            return ['estado' => $cids->isEmpty() ? 'NO_ENCONTRADA' : 'AMBIGUA'];
        }

        $respuestaFormula = $this->consultar(
            self::BASE_URL.'/compound/cid/'.$cids->first().'/property/MolecularFormula/JSON'
        );

        if ($respuestaFormula === null) {
            return ['estado' => 'NO_ENCONTRADA'];
        }

        $formula = trim((string) data_get(
            $respuestaFormula->json(),
            'PropertyTable.Properties.0.MolecularFormula'
        ));

        return $formula === ''
            ? ['estado' => 'NO_ENCONTRADA']
            : ['estado' => 'ENCONTRADA', 'formula_quimica' => $formula];
    }

    /**
     * Devuelve null solo cuando PubChem informa que el compuesto no existe.
     * Los problemas de conectividad se propagan para no confundirlos con una fórmula inexistente.
     */
    private function consultar(string $url): ?\Illuminate\Http\Client\Response
    {
        try {
            $respuesta = Http::acceptJson()
                ->connectTimeout(5)
                ->timeout(12)
                ->get($url);
        } catch (ConnectionException $exception) {
            throw new RuntimeException('No fue posible conectar con la fuente química.', previous: $exception);
        }

        if ($respuesta->status() === 404) {
            return null;
        }

        if ($respuesta->failed()) {
            throw new RuntimeException('La fuente química respondió con HTTP '.$respuesta->status().'.');
        }

        return $respuesta;
    }
}

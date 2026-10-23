<?php

namespace Tests\Unit;

use App\Services\FormulaQuimicaPubChemService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FormulaQuimicaPubChemServiceTest extends TestCase
{
    public function test_devuelve_formula_solo_cuando_pubchem_identifica_un_compuesto(): void
    {
        Http::fake([
            '*compound/name/Piraclostrobin/cids/JSON' =>
                Http::response(['IdentifierList' => ['CID' => [104857]]]),
            '*compound/cid/104857/property/MolecularFormula/JSON' =>
                Http::response(['PropertyTable' => ['Properties' => [[
                    'CID' => 104857,
                    'MolecularFormula' => 'C19H18ClN3O4',
                ]]]]),
        ]);

        $resultado = app(FormulaQuimicaPubChemService::class)->buscarPorNombre('Piraclostrobin');

        $this->assertSame([
            'estado' => 'ENCONTRADA',
            'formula_quimica' => 'C19H18ClN3O4',
        ], $resultado);
    }

    public function test_no_elige_una_formula_si_pubchem_devuelve_varios_compuestos(): void
    {
        Http::fake([
            '*compound/name/Nombre%20ambiguo/cids/JSON' =>
                Http::response(['IdentifierList' => ['CID' => [100, 200]]]),
        ]);

        $resultado = app(FormulaQuimicaPubChemService::class)->buscarPorNombre('Nombre ambiguo');

        $this->assertSame(['estado' => 'AMBIGUA'], $resultado);
        Http::assertSentCount(1);
    }

    public function test_no_almacena_formula_cuando_pubchem_no_encuentra_el_ingrediente(): void
    {
        Http::fake([
            '*compound/name/inexistente/cids/JSON' =>
                Http::response([], 404),
        ]);

        $resultado = app(FormulaQuimicaPubChemService::class)->buscarPorNombre('Inexistente');

        $this->assertSame(['estado' => 'NO_ENCONTRADA'], $resultado);
    }
}

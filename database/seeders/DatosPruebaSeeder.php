<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatosPruebaSeeder extends Seeder
{
    /**
     * Carga un escenario completo para probar el flujo del sistema.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Los datos de prueba no se pueden cargar en producción.');
        }

        $this->call([
            ProduccionSeeder::class,
            UserSeeder::class,
            AmbitoSeeder::class,
            TerritorioSeeder::class,
            FabricanteSeeder::class,
            TipoProductoSeeder::class,
            ClasificacionProductoSeeder::class,
            CatalogoMedidaSeeder::class,
            PersonaSeeder::class,
            OcupacionCobSeeder::class,
            NaturalSeeder::class,
            TelefonoSeeder::class,
            RubroSeeder::class,
            TipoEmpresaSeeder::class,
            EmpresaSeeder::class,
            ResponsableSeeder::class,
            ProductoSeeder::class,
            IngredienteSeeder::class,
            IngredienteProductoSeeder::class,
            PresentacionSeeder::class,
            RegistroSeeder::class,
            AduanaSeeder::class,
            AreaSeeder::class,
            RequisitoSeeder::class,
            TipoCertificadoSeeder::class,
            TipoEvidenciaSeeder::class,
            RequisitoTipoCertificadoSeeder::class,
            DependenciaRequisitoSeeder::class,
            CertificadoSeeder::class,
            RequisitoCertificadoSeeder::class,
            CertificadoRegistroSeeder::class,
            ProcedenciaSeeder::class,
            PagoSeeder::class,
            PagoCertificadoSeeder::class,
            RequisitoEvidenciaSeeder::class,
            RevisionRequisitoSeeder::class,
            ObservacionRequisitoSeeder::class,
            SeguimientoSeeder::class,
            CargoSeeder::class,
            FuncionarioSeeder::class,
            FuncionarioCargoSeeder::class,
            RoleUserSeeder::class,
            PermisoUserSeeder::class,
        ]);
    }
}

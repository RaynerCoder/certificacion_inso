<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CertificadorDemoSeeder extends Seeder
{
    /**
     * Seeder demo sin datos duplicados.
     * Reutiliza los mismos seeders principales para que el ejemplo siempre sea coherente.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AmbitoSeeder::class,
            TerritorioSeeder::class,
            FabricanteSeeder::class,
            TipoProductoSeeder::class,
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
            RequisitoSeeder::class,
            TipoCertificadoSeeder::class,
            RequisitoTipoCertificadoSeeder::class,
            CertificadoSeeder::class,
            RequisitoCertificadoSeeder::class,
            CertificadoRegistroSeeder::class,
            ProcedenciaSeeder::class,
            PagoSeeder::class,
            PagoCertificadoSeeder::class,
            SeguimientoSeeder::class,
            PermisoSeeder::class,
            RoleSeeder::class,
            AreaSeeder::class,
            CargoSeeder::class,
            FuncionarioSeeder::class,
            FuncionarioCargoSeeder::class,
            PermisoRoleSeeder::class,
            RoleUserSeeder::class,
            PermisoUserSeeder::class,
        ]);
    }
}

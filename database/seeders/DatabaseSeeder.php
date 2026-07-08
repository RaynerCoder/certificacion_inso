<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PermisoSeeder::class,
            RoleSeeder::class,
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
            PermisoRoleSeeder::class,
            RoleUserSeeder::class,
            PermisoUserSeeder::class,
        ]);
    }
}

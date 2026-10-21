<?php

namespace Tests\Unit;

use App\Models\Certificado;
use App\Models\Empresa;
use App\Models\Natural;
use App\Models\NotificacionTramite;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class NotificacionTramiteTest extends TestCase
{
    public function test_muestra_representante_legal_segun_la_relacion_activa_del_emisor(): void
    {
        $notificacion = $this->crearNotificacionEmpresa(['solicitante']);

        $presentacion = $notificacion->datosPresentacion();

        $this->assertSame('MARIO ALMANZA UGARTE', $presentacion['enviado_por']);
        $this->assertSame('Representante legal', $presentacion['actua_como']);
    }

    public function test_muestra_tramitador_cuando_el_emisor_solo_tiene_ese_vinculo_activo(): void
    {
        $notificacion = $this->crearNotificacionEmpresa(['tramitador']);

        $presentacion = $notificacion->datosPresentacion();

        $this->assertSame('Tramitador', $presentacion['actua_como']);
    }

    public function test_prioriza_representante_legal_si_el_emisor_tiene_ambos_vinculos(): void
    {
        $notificacion = $this->crearNotificacionEmpresa(['tramitador', 'solicitante']);

        $presentacion = $notificacion->datosPresentacion();

        $this->assertSame('Representante legal', $presentacion['actua_como']);
    }

    private function crearNotificacionEmpresa(array $roles): NotificacionTramite
    {
        $naturalEmisor = new Natural([
            'nombres' => 'MARIO',
            'apellido_paterno' => 'ALMANZA',
            'apellido_materno' => 'UGARTE',
        ]);

        $personaEmisora = new Persona(['estado' => 'ACTIVO']);
        $personaEmisora->id = 8;
        $personaEmisora->setRelation('natural', $naturalEmisor);
        $personaEmisora->setRelation('empresa', null);

        $usuarioEmisor = new User(['estado' => 1]);
        $usuarioEmisor->id = 7;
        $usuarioEmisor->setRelation('funcionario', null);
        $usuarioEmisor->setRelation('persona', $personaEmisora);

        $responsables = collect($roles)->map(function (string $slug) use ($personaEmisora) {
            $rol = new Role(['slug' => $slug, 'estado' => 1]);
            $responsable = new Responsable([
                'id_persona' => $personaEmisora->id,
                'estado' => 'ACTIVO',
            ]);
            $responsable->setRelation('persona', $personaEmisora);
            $responsable->setRelation('rol', $rol);

            return $responsable;
        });

        $empresa = new Empresa([
            'razon_social' => 'IMPORTADORA AGROINDUSTRIAL BOLIVIANA IMAGROBOL S.R.L.',
            'estado' => 'ACTIVO',
        ]);
        $empresa->id = 3;
        $empresa->setRelation('responsables', new Collection($responsables->all()));

        $beneficiario = new Persona(['estado' => 'ACTIVO']);
        $beneficiario->id = 4;
        $beneficiario->setRelation('natural', null);
        $beneficiario->setRelation('empresa', $empresa);

        $certificado = new Certificado;
        $certificado->id = 5;
        $certificado->id_persona_tramitador = $personaEmisora->id;
        $certificado->setRelation('beneficiario', $beneficiario);
        $certificado->setRelation('tramitador', $personaEmisora);

        $notificacion = new NotificacionTramite;
        $notificacion->setRelation('usuarioEmisor', $usuarioEmisor);
        $notificacion->setRelation('certificado', $certificado);

        return $notificacion;
    }
}

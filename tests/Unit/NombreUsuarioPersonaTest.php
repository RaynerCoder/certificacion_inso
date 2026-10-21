<?php

namespace Tests\Unit;

use App\Support\NombreUsuarioPersona;
use PHPUnit\Framework\TestCase;

class NombreUsuarioPersonaTest extends TestCase
{
    public function test_usa_primer_nombre_y_apellido_paterno(): void
    {
        $this->assertSame('juan_choque', NombreUsuarioPersona::generar(' JUAN CARLOS ', ' CHOQUE '));
        $this->assertSame('jose_munoz', NombreUsuarioPersona::generar('José Ángel', 'Muñoz'));
    }
}

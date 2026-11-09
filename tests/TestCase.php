<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->garantizarBaseDeDatosDePrueba();

        parent::setUp();
    }

    /**
     * Red de seguridad: los tests nunca deben ejecutarse contra la base de datos real.
     *
     * El contenedor Docker define DB_CONNECTION=mysql como variable de entorno, y
     * Laravel la lee antes que la configuración de phpunit.xml. Si esa variable no
     * se neutraliza, RefreshDatabase borra la base de datos de desarrollo.
     */
    private function garantizarBaseDeDatosDePrueba(): void
    {
        $conexion = $_SERVER['DB_CONNECTION'] ?? $_ENV['DB_CONNECTION'] ?? getenv('DB_CONNECTION');

        if ($conexion !== 'sqlite') {
            throw new RuntimeException(
                'Los tests deben ejecutarse sobre SQLite en memoria, pero la conexión actual es "'.$conexion.'". '
                .'Revisa phpunit.xml (<server name="DB_CONNECTION" value="sqlite" force="true"/>).'
            );
        }
    }
}

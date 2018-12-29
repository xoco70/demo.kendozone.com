<?php

namespace Tests\Integration;

use Tests\TestCase;

class InstallationTest extends TestCase
{
    /** @test */
    public function it_installs()
    {
        exec('tests/Integration/test_installation.sh', $output, $return_code);
        self::assertEquals($return_code, 0);
    }
}

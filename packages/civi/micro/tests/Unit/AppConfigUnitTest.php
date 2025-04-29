<?php

declare(strict_types=1);

use Civi\Micro\AppConfig;
use PHPUnit\Framework\TestCase;

final class AppConfigUnitTest extends TestCase
{

    public function testEmptyBuild() 
    {
        $config = new AppConfig(null);
        $this->assertEquals("/management", $config->managementEndpoint);
    }

    public function testDeclaredBuild() 
    {
        $config = new AppConfig("/green");
        $this->assertEquals("/green", $config->managementEndpoint);
    }

}
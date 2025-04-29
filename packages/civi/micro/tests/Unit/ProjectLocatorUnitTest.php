<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Civi\Micro\ProjectLocator;

final class ProjectLocatorUnitTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetProjectLocator();
    }

    private function resetProjectLocator(): void
    {
        $refClass = new ReflectionClass(ProjectLocator::class);
        foreach (['rootPath', 'innerAutoload', 'vendorAutoload'] as $property) {
            $prop = $refClass->getProperty($property);
            $prop->setAccessible(true);
            if ($property === 'rootPath') {
                $prop->setValue(null, null);
            } elseif ($property === 'innerAutoload') {
                $prop->setValue(null, __DIR__ . '/../../vendor/');
            } elseif ($property === 'vendorAutoload') {
                $prop->setValue(null, __DIR__ . '/../../../../autoload.php');
            }
        }
    }

    public function testGetRootPathReturnsString(): void
    {
        $path = ProjectLocator::getRootPath();
        $this->assertIsString($path);
    }

    public function testFindInnerAutoload(): void
    {
        $tempDir = sys_get_temp_dir() . '/inner_autoload_test';
        mkdir($tempDir . '/vendor', 0777, true);
        file_put_contents($tempDir . '/vendor/autoload.php', "<?php\n");

        $this->setStaticProperty('innerAutoload', $tempDir . '/vendor/');
        $this->setStaticProperty('vendorAutoload', '/non/existent/path');

        chdir($tempDir);

        $path = ProjectLocator::getRootPath();
        $this->assertSame(realpath($tempDir), $path);

        // Cleanup
        unlink($tempDir . '/vendor/autoload.php');
        rmdir($tempDir . '/vendor');
        rmdir($tempDir);
    }

    public function testFindVendorAutoload(): void
    {
        $tempDir = sys_get_temp_dir() . '/vendor_autoload_test';
        mkdir($tempDir, 0777, true);
        file_put_contents($tempDir . '/autoload.php', "<?php\n");

        $this->setStaticProperty('innerAutoload', '/non/existent/path');
        $this->setStaticProperty('vendorAutoload', $tempDir);

        // chdir(dirname($tempDir)); // Para que simule la estructura
        $path = ProjectLocator::getRootPath();
        $this->assertSame(realpath(dirname($tempDir)), $path);

        // Cleanup
        unlink($tempDir . '/autoload.php');
        rmdir($tempDir);
    }

    public function testClimbDirectoriesToFindComposerJson(): void
    {
        $tempDir = sys_get_temp_dir() . '/climb_composer_test';
        mkdir($tempDir . '/level1/level2', 0777, true);
        file_put_contents($tempDir . '/composer.json', '{}');

        $this->setStaticProperty('innerAutoload', '/non/existent/path');
        $this->setStaticProperty('vendorAutoload', '/non/existent/path');

        chdir($tempDir . '/level1/level2');

        $path = ProjectLocator::getRootPath();
        $this->assertSame(realpath($tempDir), $path);

        // Cleanup
        unlink($tempDir . '/composer.json');
        rmdir($tempDir . '/level1/level2');
        rmdir($tempDir . '/level1');
        rmdir($tempDir);
    }

    public function testReturnEmptyWhenNothingFound(): void
    {
        $originalCwd = getcwd();

        $this->setStaticProperty('innerAutoload', '/non/existent/path');
        $this->setStaticProperty('vendorAutoload', '/non/existent/path');

        chdir('/'); // Root directory: no composer.json expected

        $path = ProjectLocator::getRootPath();
        $this->assertSame('', $path);

        chdir($originalCwd);
    }

    private function setStaticProperty(string $property, $value): void
    {
        $refClass = new ReflectionClass(ProjectLocator::class);
        $prop = $refClass->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue(null, $value);
    }
}

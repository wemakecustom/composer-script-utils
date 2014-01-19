<?php

namespace WMC\Composer\Utils\Tests\Composer;

use WMC\Composer\Utils\Composer\PackageLocator;

use Composer\Composer;
use Composer\Factory;
use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Helper\HelperSet;


class PackageLocatorTest extends \PHPUnit_Framework_TestCase
{
    private $composer;

    public function setUp()
    {
        $io = new ConsoleIO(new ArrayInput(array()), new NullOutput, new HelperSet);
        $this->composer = Factory::create($io, null, false);
    }

    public function testPackagePath()
    {
        $this->assertNotEmpty(PackageLocator::getPackagePath($this->composer, 'composer/composer'));
        $this->assertNull(PackageLocator::getPackagePath($this->composer, 'asjhkjsdhfs/sdkfjhskfjs'));
    }

    public function testBasePath()
    {
        $this->assertEquals(PackageLocator::getBasePath($this->composer), dirname(dirname(__DIR__)));
    }
}

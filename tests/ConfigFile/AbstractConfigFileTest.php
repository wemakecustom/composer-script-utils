<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use Composer\IO\ConsoleIO;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Helper\HelperSet;

abstract class AbstractConfigFileTest extends \PHPUnit_Framework_TestCase
{
    protected $configFile;

    public function setUp()
    {
        $io = new ConsoleIO(new ArrayInput($this->getTestData()), new NullOutput, new HelperSet(array(
            $this->getDialogHelper(),
        )));

        $this->configFile = $this->getConfigFile($io);
    }

    abstract protected function getConfigFile(ConsoleIO $io);

    protected function getDialogHelper()
    {
        // We mock a DialogHelper that always return the default value

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $dialog->expects($this->any())
            ->method('ask')
            ->will($this->returnArgument(2));

        return $dialog;
    }

    public function getTestData()
    {
        return array(
            'type_string'  => 'foo',
            'type_true'    => true,
            'type_false'   => false,
            'type_null'    => null,
            'type_integer' => 123,
            'type_float'   => 12.3,
            'type_empty'   => '',
        );
    }

    public function testInvalidFile()
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->configFile->updateFile(null, '/tmp/adahsdkajhsdk');
    }

    public function testKeepOutdatedParams()
    {
        $dump = static::getMethod($this->configFile, "dump");
        $parse = static::getMethod($this->configFile, "parseFile");
        $tempFile = tempnam(null, 'composer_script_utils_tests');
        $tempDistFile = tempnam(null, 'composer_script_utils_tests');

        $data = $this->getTestData();
        $dist = array_slice($data, 0, 2);

        file_put_contents($tempFile, $dump($data));
        file_put_contents($tempDistFile, $dump($dist));

        $this->configFile->setKeepOutdatedParams(true);
        $this->configFile->updateFile($tempFile, $tempDistFile);
        $this->assertEquals($data, $parse($tempFile));

        $this->configFile->setKeepOutdatedParams(false);
        $this->configFile->updateFile($tempFile, $tempDistFile);
        $this->assertEquals($dist, $parse($tempFile));

        unlink($tempFile);
        unlink($tempDistFile);
    }

    public function testDumpParseSingle()
    {
        $dump = static::getMethod($this->configFile, "dumpSingle");
        $parse = static::getMethod($this->configFile, "parseSingle");

        foreach ($this->getTestData() as $test) {
            $this->assertEquals($test, $parse($dump($test)));
        }
    }

    public function testDumpParse()
    {
        $dump = static::getMethod($this->configFile, "dump");
        $parse = static::getMethod($this->configFile, "parseFile");
        $tempFile = tempnam(null, 'composer_script_utils_tests');

        file_put_contents($tempFile, $dump($this->getTestData()));

        $this->assertEquals($this->getTestData(), $parse($tempFile));

        unlink($tempFile);
    }

    public function testEnvMap()
    {
        $getEnvMap = static::getMethod($this->configFile, "getEnvMap");

        $out = $getEnvMap(array('foo' => 'bar'));

        $this->configFile->setName(null);
        $this->assertEquals(array('foo' => 'FOO'), $getEnvMap(array('foo' => 'bar')));

        $this->configFile->setName('composer_util');
        $this->assertEquals(array('foo' => 'COMPOSER_UTIL_FOO'), $getEnvMap(array('foo' => 'bar')));

        $this->configFile->setEnvMap(array('foo' => 'FOO'));
        $this->assertEquals(array('foo' => 'FOO'), $getEnvMap(array()));
    }

    public function testOverwriteWithEnvValues()
    {
        $overwriteWithEnvValues = static::getMethod($this->configFile, "overwriteWithEnvValues");

        $data = array('foo' => '123');
        $this->configFile->setName('composer_util');

        putenv('COMPOSER_UTIL_FOO=bar');
        $overwriteWithEnvValues($data);
        $this->assertEquals(array('foo' => 'bar'), $data);
        putenv('COMPOSER_UTIL_FOO');
    }

    public function testFull()
    {
        $tempFile = tempnam(null, 'composer_script_utils_tests');
        $tempDistFile = tempnam(null, 'composer_script_utils_tests');

        $dump = static::getMethod($this->configFile, "dump");
        file_put_contents($tempDistFile, $dump($this->getTestData()));

        $this->configFile->updateFile($tempFile, $tempDistFile);

        unlink($tempFile);
        unlink($tempDistFile);
    }

    public function testGetNameByFile()
    {
        $getNameByFile = static::getMethod($this->configFile, "getNameByFile");

        $this->assertEquals('foobar', $getNameByFile('/tmp/foobar.yml'));
    }

    protected static function getMethod($obj, $method)
    {
        $class = new \ReflectionObject($obj);
        $method = $class->getMethod($method);
        $method->setAccessible(true);

        return $method->getClosure($obj);
    }
}

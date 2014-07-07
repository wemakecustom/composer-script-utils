<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use Composer\IO\ConsoleIO;
use Composer\Script\Event;
use Composer\Composer;
use Composer\Package\RootPackage;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Helper\HelperSet;

use WMC\Composer\Utils\ConfigFile\ConfigDir;
use WMC\Composer\Utils\ConfigFile\IniConfigFile;
use WMC\Composer\Utils\ConfigFile\PhpConfigFile;

class ConfigDirTest extends \PHPUnit_Framework_TestCase
{
    private $event;

    private $tmpDir;

    private $distDir;

    private $localDir;

    private $configFile;

    private $configFilePhp;

    public function setUp()
    {
        $this->tmpDir = self::getTempDir();
        $this->distDir = $this->tmpDir . '/dist';
        $this->localDir = $this->tmpDir . '/local';
        mkdir($this->distDir);

        $composer = new Composer;
        $package = new RootPackage('test/test', '1.0', 'v1.0');
        $package->setExtra(array(
            'update-config-dirs' => array(
                $this->distDir => $this->localDir,
            ),
        ));
        $composer->setPackage($package);

        $io = new ConsoleIO(new ArrayInput(array()), new NullOutput, new HelperSet(array(
            $this->getDialogHelper(),
        )));

        $this->event = new Event('test', $composer, $io);
        $this->configFile = new IniConfigFile($io);
        $this->configFilePhp = new PhpConfigFile($io);
    }

    private static function getTempDir()
    {
        $tmp = tempnam(null, 'composer-test');
        unlink($tmp);
        mkdir($tmp);

        return $tmp;
    }

    public function testOneFile()
    {
        file_put_contents("{$this->distDir}/one.ini", $this->configFile->dump(array('test' => 'example')));

        ConfigDir::updateDirs($this->event);
        $this->assertFileExists("{$this->localDir}/one.ini");

        unlink("{$this->distDir}/one.ini");
        unlink("{$this->localDir}/one.ini");
    }

    public function testDifferentFormat()
    {
        $testData = array('test' => 'example');
        file_put_contents("{$this->distDir}/one.ini.php", $this->configFilePhp->dump($testData));

        ConfigDir::updateDirs($this->event);
        $this->assertFileExists("{$this->localDir}/one.ini");
        $data = $this->configFile->parseFile("{$this->localDir}/one.ini");
        $this->assertEquals($data, $testData);

        unlink("{$this->distDir}/one.ini.php");
        unlink("{$this->localDir}/one.ini");
    }

    protected function getDialogHelper()
    {
        // We mock a DialogHelper that always return the default value

        $dialog = $this->getMock('Symfony\Component\Console\Helper\DialogHelper', array('ask'));
        $dialog->expects($this->any())
            ->method('ask')
            ->will($this->returnArgument(2));

        return $dialog;
    }

    public function tearDown()
    {
        rmdir($this->distDir);
        rmdir($this->localDir);
        rmdir($this->tmpDir);
    }
}

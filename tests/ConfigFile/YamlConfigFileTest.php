<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use WMC\Composer\Utils\ConfigFile\YamlConfigFile;

use Composer\IO\ConsoleIO;

class YamlConfigFileTest extends AbstractConfigFileTest
{
    protected function getConfigFile(ConsoleIO $io)
    {
        if (YamlConfigFile::isSupported()) {
            return new YamlConfigFile($io);
        } else {
            $this->markTestSkipped();
            return null;
        }
    }
}

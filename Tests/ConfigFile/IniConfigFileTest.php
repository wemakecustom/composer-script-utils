<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use WMC\Composer\Utils\ConfigFile\IniConfigFile;

use Composer\IO\ConsoleIO;

class IniConfigFileTest extends AbstractConfigFileTest
{
    protected function getConfigFile(ConsoleIO $io)
    {
        return new IniConfigFile($io);
    }
}

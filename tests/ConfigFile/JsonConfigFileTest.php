<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use WMC\Composer\Utils\ConfigFile\JsonConfigFile;

use Composer\IO\ConsoleIO;

class JsonConfigFileTest extends AbstractConfigFileTest
{
    protected function getConfigFile(ConsoleIO $io)
    {
        return new JsonConfigFile($io);
    }
}

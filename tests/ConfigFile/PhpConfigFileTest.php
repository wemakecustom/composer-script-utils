<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use WMC\Composer\Utils\ConfigFile\PhpConfigFile;

use Composer\IO\ConsoleIO;

class PhpConfigFileTest extends AbstractConfigFileTest
{
    protected function getConfigFile(ConsoleIO $io)
    {
        return new PhpConfigFile($io);
    }
}

<?php

namespace WMC\Composer\Utils;

use Composer\Script\Event;
use Composer\IO\IOInterface;
use Symfony\Component\Filesystem\Filesystem;

use WMC\Composer\Utils\ConfigFile;

/**
 * This class is intended to be used only as a static Repository of functions
 */
class ScriptHandler
{
    // Private constructor, this class MUST never be instantiated
    private function __construct()
    {
    }

    public static function updateDirs(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        if (isset($extra['update-config-dirs'])) {
            $configDir = static::createConfigFileUpdate($event->getIO());

            foreach ($extra['update-config-dirs'] as $dist => $target) {
                $configDir->updateDir($target, $dist);
            }
        } else {
            $event->getIO()->write('<warning>Composer configuration is missing: {"extra": "update-config-dirs": {"path/to/dist/dir": "path/to/target/dir"}}</warning>');
        }
    }

    protected static function createConfigMerger(IOInterface $io, $class = 'WMC\Composer\Utils\ConfigFile\ConfigMerger')
    {
        return new $class($io);
    }
    
    public static function createConfigFileUpdate(IOInterface $io, $class = 'WMC\Composer\Utils\ConfigFile\FileUpdater')
    {
        $configUpdater = new $class(static::createConfigMerger($io));

        foreach (static::getDefaultParsers($io) as $ext => $parser) {
            $configUpdater->addParser($ext, $parser);
        }

        return $configUpdater;
    }

    protected static function getDefaultParsers(IOInterface $io)
    {
        return array_filter(array_map(function($class) use ($io) {
            return $class::isSupported() ? new $class($io) : null;
        }, array(
            'ini'  => 'WMC\Composer\Utils\ConfigFile\Parser\IniParser',
            'json' => 'WMC\Composer\Utils\ConfigFile\Parser\JsonParser',
            'yml'  => 'WMC\Composer\Utils\ConfigFile\Parser\YamlParser',
            'yaml' => 'WMC\Composer\Utils\ConfigFile\Parser\YamlParser',
        )));
    }
}

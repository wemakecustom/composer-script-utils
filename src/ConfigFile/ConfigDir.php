<?php

namespace WMC\Composer\Utils\ConfigFile;

use Composer\IO\IOInterface;
use Composer\Script\Event;

class ConfigDir
{
    /**
     * @var AbstractConfigFile[]
     */
    private $parsers = array();

    /**
     * @var Composer\IO\IOInterface
     */
    protected $io;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;

        $this->loadParser('ini', 'WMC\Composer\Utils\ConfigFile\IniConfigFile');
        $this->loadParser('json', 'WMC\Composer\Utils\ConfigFile\JsonConfigFile');
        $this->loadParser('yml', 'WMC\Composer\Utils\ConfigFile\YamlConfigFile');
        $this->loadParser('yaml', 'WMC\Composer\Utils\ConfigFile\YamlConfigFile');
    }

    public function loadParser($extension, $parserClass)
    {
        if ($parserClass::isSupported()) {
            $this->parsers[$extension] = new $parserClass($this->io);
        }
    }

    private function listFiles($dir)
    {
        $extensions = array_keys($this->parsers);
        if (count($extensions) === 0) {
            throw new \RuntimeException('No parser loaded');
        } elseif (count($extensions) === 1) {
            $glob = '*.' . $extensions[0];
        } else {
            $glob = '*.{' . implode(',', $extensions) . '}';
        }

        return glob("$dir/$glob", GLOB_BRACE);
    }

    public function updateFile($targetDir, $distFile)
    {
        $info = pathinfo($distFile);
        $targetFile = $targetDir . '/' . $info['basename'];
        $this->parsers[$info['extension']]->updateFile($targetFile, $distFile);
    }

    public function updateDir($targetDir, $distDir)
    {
        foreach ($this->listFiles($distDir) as $distFile) {
            $this->updateFile($targetDir, $distFile);
        }
    }

    public static function updateDirs(Event $event)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();

        if (isset($extra['update-config-dirs'])) {
            $configDir = new self($event->getIO());

            foreach ($extra['update-config-dirs'] as $dist => $target) {
                $configDir->updateDir($target, $dist);
            }
        } else {
            $event->getIO()->write('<warning>Composer configuration is missing: {"extra": "update-config-dirs": {"path/to/dist/dir": "path/to/target/dir"}}</warning>');
        }
    }
}

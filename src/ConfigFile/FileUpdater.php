<?php

namespace WMC\Composer\Utils\ConfigFile;

use WMC\Composer\Utils\ConfigFile\Parser\ParserInterface;

class FileUpdater
{
    /**
     * @var ParserInterface[]
     */
    private $parsers = array();

    /**
     * @var ConfigMerger
     */
    protected $cm;

    public function __construct(ConfigMerger $cm = null)
    {
        $this->cm = $cm;
    }

    public function addParser($extension, ParserInterface $parser)
    {
        $this->parsers[$extension] = $parser;

        return $this;
    }

    public function getConfigMerger()
    {
        return $this->cm;
    }

    public function setConfigMerger(ConfigMerger $cm)
    {
        $this->cm = $cm;

        return $this;
    }
    
    protected function listFiles($dir)
    {
        $extensions = array_keys($this->parsers);
        $count = count($extensions);

        if (0 === $count) {
            throw new \RangeException('No parser loaded');
        } elseif (1 === $count) {
            $glob = '*.'.$extensions[0];
        } else {
            $glob = '*.{'.implode(',', $extensions).'}';
        }

        return glob($dir.DIRECTORY_SEPARATOR.$glob, GLOB_BRACE);
    }

    /**
     * Update local file associated to $distFile in $targetDir.
     *
     * If you want to change the format between input and output,
     * Use a double extension (For example: file.ini.php)
     * the trailing extension (php) will be used for input while
     * the inner extension (ini) will be used for output.
     */
    public function updateFile($targetFile, $distFile)
    {
        $distExtension = pathinfo($distFile, PATHINFO_EXTENSION);
        if (!isset($this->parsers[$distExtension])) {
            throw new \DomainException(sprintf('No parser associated to extension "%s"', $distExtension));
        }

        $targetExtension = pathinfo($targetFile, PATHINFO_EXTENSION);
        if (!isset($this->parsers[$targetExtension])) {
            throw new \DomainException(sprintf('No parser associated to extension "%s"', $targetExtension));
        }

        if (!is_file($distFile)) {
            throw new \RuntimeException(sprintf('%s is missing.', $distFile));
        }

        if (!is_dir($targetDir = dirname($targetFile))) {
            mkdir($targetDir, 0777, true);
        }

        $this->doUpdateFile($targetFile, $distFile, $this->parsers[$targetExtension], $this->parsers[$distExtension]);
    }

    protected function doUpdateFile($targetFile, $distFile, $targetParser, $distParser)
    {
        $this->cm->setName(pathinfo($targetFile, PATHINFO_FILENAME));
        $expected = $distParser->parse(file_get_contents($distFile));

        $targetOldContent = file_exists($targetFile) ? file_get_contents($targetFile) : '';
        $current = $distParser->parse($targetOldContent);

        $new = $this->cm->updateParams($expected, $current);

        $targetNewContent = $targetParser->dump($new);

        if ($targetOldContent !== $targetNewContent) {
            file_put_contents($targetFile, $targetNewContent);
        }
    }

    protected function computeTargetFileName($targetDir, $distFile)
    {
        $info = pathinfo($distFile);
        $extension = $info['extension'];
        $name      = $info['filename'];

        $info = pathinfo($name);
        if (!empty($info['extension']) && isset($this->parsers[$info['extension']])) {
            $extension  = $info['extension'];
            $name       = $info['filename'];
        }

        return $targetDir.DIRECTORY_SEPARATOR.$name.'.'.$extension;
    }

    /**
     * Calls updateFile for each file supported in $distDir.
     * Output files in $targetDir.
     */
    public function updateDir($targetDir, $distDir)
    {
        foreach ($this->listFiles($distDir) as $distFile) {
            try {
                $targetFile = $this->computeTargetFileName($targetDir, $distFile);
                $this->updateFile($targetFile, $distFile);
            } catch (\RuntimeException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }
    }
}

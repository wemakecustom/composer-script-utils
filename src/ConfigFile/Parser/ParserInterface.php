<?php

namespace WMC\Composer\Utils\ConfigFile\Parser;

interface ParserInterface
{
    public static function isSupported();

    /**
     * Parse content (in a string) and return an array of configs.
     *
     * @param string $content
     * @return array An array
     */
    public function parse($content);

    /**
     * Turn $config back into a string, ready to be dumped in a file.
     *
     * @param array $params
     * @return string
     */
    public function dump(array $config);
}

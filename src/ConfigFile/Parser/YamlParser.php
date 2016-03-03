<?php

namespace WMC\Composer\Utils\ConfigFile\Parser;

use Symfony\Component\Yaml\Yaml;

class YamlParser implements ParserInterface
{
    public function dump(array $params)
    {
        return Yaml::dump($params, 3);
    }

    public function parse($content)
    {
        $yaml = Yaml::parse($content);

        return $yaml === null ? [] : $yaml;
    }

    public static function isSupported()
    {
        return class_exists('Symfony\Component\Yaml\Yaml');
    }
}

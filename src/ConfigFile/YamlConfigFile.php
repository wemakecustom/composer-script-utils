<?php

namespace WMC\Composer\Utils\ConfigFile;

use Symfony\Component\Yaml\Yaml;

class YamlConfigFile extends AbstractConfigFile
{
    public function dump(array $params)
    {
        return Yaml::dump($params, 3);
    }

    public function parseFile($file)
    {
        $yaml = Yaml::parse($file);

        return $yaml === null ? array() : $yaml;
    }

    public static function isSupported()
    {
        return class_exists('Symfony\Component\Yaml\Yaml');
    }
}

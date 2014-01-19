<?php

namespace WMC\Composer\Utils\ConfigFile;

use Symfony\Component\Yaml\Yaml;

class YamlConfigFile extends AbstractConfigFile
{
    protected function dump(array $params)
    {
        return Yaml::dump($params);
    }

    protected function parseFile($file)
    {
        $yaml = Yaml::parse($file);

        return $yaml === null ? array() : $yaml;
    }

    public static function isSupported()
    {
        return class_exists('Symfony\Component\Yaml\Yaml');
    }
}

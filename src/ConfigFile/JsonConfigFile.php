<?php

namespace WMC\Composer\Utils\ConfigFile;

class JsonConfigFile extends AbstractConfigFile
{
    public function dump(array $params)
    {
        return json_encode($params);
    }

    public function parseFile($file)
    {
        $json = json_decode(file_get_contents($file), true);

        return $json === null ? array() : $json;
    }
}

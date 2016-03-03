<?php

namespace WMC\Composer\Utils\ConfigFile\Parser;

class JsonParser implements ParserInterface
{
    public function dump(array $params)
    {
        return json_encode($params);
    }

    public function parse($content)
    {
        $json = json_decode($content, true);

        return $json === null ? array() : $json;
    }

    public static function isSupported()
    {
        return function_exists('json_encode') && function_exists('json_decode');
    }
}

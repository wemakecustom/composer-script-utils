<?php

namespace WMC\Composer\Utils\ConfigFile\Parser;

class IniParser implements ParserInterface
{
    public static function isSupported()
    {
        return function_exists('parse_ini_string');
    }

    protected function dumpValue($value)
    {
        if (true === $value) {
            return 'true';
        } elseif (false === $value) {
            return 'false';
        } elseif (null === $value) {
            return 'null'; 
        }

        return $value;
    }

    public function dump(array $params)
    {
        $ini = array();

        foreach ($params as $key => $value) {
            $ini[] = $key.'='.$this->dumpValue($value);
        }

        return "; This file was auto-generated during composer install\n".implode("\n", $ini);
    }

    public function parse($content)
    {
        return parse_ini_string($content, false, constant(defined('INI_SCANNER_TYPED') ? 'INI_SCANNER_TYPED' : 'INI_SCANNER_NORMAL'));
    }
}

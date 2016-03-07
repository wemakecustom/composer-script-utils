<?php

namespace WMC\Composer\Utils\ConfigFile\Parser;

class IniParser implements ParserInterface
{
    public static function isSupported()
    {
        return function_exists('parse_ini_string');
    }

    protected function flatten($values, $prefix = '')
    {
        $result = array();

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                foreach ($this->flatten($value, $key.'.') as $k => $v) {
                    $result[$prefix.$k] = $v;
                }
            } else {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
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

    protected function parseValue($value)
    {
        if ('true' === $value) {
            return true;
        } elseif ('false' === $value) {
            return false;
        } elseif ('null' === $value) {
            return null;
        } elseif (''.($val = intval($value)) === $value) {
            return $val;
        } elseif (''.($val = floatval($value)) === $value) {
            return $val;
        }

        return $value;
    }
    
    public function dump(array $params)
    {
        $ini = array();

        foreach ($this->flatten($params) as $k => $v) {
            $ini[] = $k.'='.$this->dumpValue($v);
        }
        
        return "; This file was auto-generated during composer install\n"
              .implode("\n", $ini);
    }

    public function parse($content)
    {
        $autoParsing = defined('INI_SCANNER_TYPED');

        $ini = parse_ini_string($content, false, constant($autoParsing ? 'INI_SCANNER_TYPED' : 'INI_SCANNER_RAW'));

        if (!$autoParsing) {
            $ini = array_map(array($this, 'parseValue'), $ini);
        }

        return $ini;
    }
}

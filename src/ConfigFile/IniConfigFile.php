<?php

namespace WMC\Composer\Utils\ConfigFile;

class IniConfigFile extends AbstractConfigFile
{
    public function dump(array $params)
    {
        $ini = "; This file was auto-generated during composer install\n";

        foreach ($params as $key => $value) {
            $ini .= "$key=" . self::dumpSingle($value) . "\n";
        }

        return $ini;
    }

    public function parseFile($file)
    {
        $ini = @parse_ini_file($file, false, INI_SCANNER_RAW);

        foreach ($ini as $key => $value) {
            $ini[$key] = $this->parseSingle($value);
        }

        return $ini;
    }
}

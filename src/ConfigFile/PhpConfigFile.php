<?php

namespace WMC\Composer\Utils\ConfigFile;

class PhpConfigFile extends AbstractConfigFile
{
    public function dump(array $params)
    {
        $php = "<?php\n// This file was auto-generated during composer install\n\n";

        $php .= 'return ' . var_export($params, true) . ';';

        return $php;
    }

    public function parseFile($file)
    {
        $php = include $file;

        if ($php === 1) {
            return array(); // file did not return anything
        } else {
            return $php;
        }
    }
}

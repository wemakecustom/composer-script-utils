<?php

namespace WMC\Composer\Utils\Filesystem;

class PathUtil
{
    /**
     * @link https://gist.github.com/lavoiesl/5525558
     */
    public static function getRelativePath($from, $to)
    {
        $from = realpath($from);
        $to   = realpath(dirname($to)) . '/' . basename($to);
        if (!$from || !$to) {
            return false;
        }

        // Get dir if source is a file
        if (!is_dir($from)) {
            $from = dirname($from);
        }

        $from = explode(DIRECTORY_SEPARATOR, $from);
        $to   = explode(DIRECTORY_SEPARATOR, $to);

        for ($i=0; $i < count($from) && $i < count($to); $i++) { 
            if ($from[$i] != $to[$i]) {
                break;
            }
        }

        $from = array_splice($from, $i);
        $to   = array_splice($to, $i);

        $up   = str_repeat('..'.DIRECTORY_SEPARATOR, count($from));
        $down = implode(DIRECTORY_SEPARATOR, $to);

        return $up . $down;
    }
}

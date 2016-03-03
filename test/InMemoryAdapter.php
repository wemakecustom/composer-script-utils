<?php

namespace WMC\Composer\Utils\Test;

use Gaufrette\Adapter\InMemory;

class InMemoryAdapter extends InMemory
{
    public function isDirectory($path)
    {
        return $this->exists(rtrim($path, '/').'/');
    }
}

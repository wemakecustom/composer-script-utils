<?php

namespace WMC\Composer\Utils\Tests\ConfigFile\Parser;

use WMC\Composer\Utils\Test\ConfigFile\AbstractParserTest;
use WMC\Composer\Utils\ConfigFile\Parser\IniParser;

class IniParserTest extends AbstractParserTest
{
    protected function initParser()
    {
        if (!IniParser::isSupported()) {
            $this->markTestSkipped('Ini parser not supported by this environment');
        }
        
        $this->parser = new IniParser;
    }

    public function dataProvider()
    {
        return array(
            array( <<<'EOF'
; This file was auto-generated during composer install
string=foo
btrue=true
bfalse=false
nnull=null
integer=123
float=12.3
empty=
EOF
            , array(
                'string'  => 'foo',
                'btrue'   => true,
                'bfalse'  => false,
                'nnull'   => null,
                'integer' => 123,
                'float'   => 12.3,
                'empty'   => '',
            )),
        );
    }
}

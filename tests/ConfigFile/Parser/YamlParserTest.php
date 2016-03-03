<?php

namespace WMC\Composer\Utils\Tests\ConfigFile\Parser;

use WMC\Composer\Utils\Test\ConfigFile\AbstractParserTest;
use WMC\Composer\Utils\ConfigFile\Parser\YamlParser;

class YamlParserTest extends AbstractParserTest
{
    protected function initParser()
    {
        if (!YamlParser::isSupported()) {
            $this->markTestSkipped('Yaml parser not supported by this environment');
        }
        
        $this->parser = new YamlParser;
    }

    public function dataProvider()
    {
        return array(
            array( <<<'EOF'
parameters:
    string: foo
    btrue: true
    bfalse: false
    nnull: null
    integer: 123
    float: 12.3
    empty: ''

EOF
            , array('parameters' => array(
                'string'  => 'foo',
                'btrue'   => true,
                'bfalse'  => false,
                'nnull'   => null,
                'integer' => 123,
                'float'   => 12.3,
                'empty'   => '',
            ))),
        );
    }
}

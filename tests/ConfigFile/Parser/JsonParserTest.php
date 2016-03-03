<?php

namespace WMC\Composer\Utils\Tests\ConfigFile\Parser;

use WMC\Composer\Utils\Test\ConfigFile\AbstractParserTest;
use WMC\Composer\Utils\ConfigFile\Parser\JsonParser;

class JsonParserTest extends AbstractParserTest
{
    protected function initParser()
    {
        if (!JsonParser::isSupported()) {
            $this->markTestSkipped('Json parser not supported by this environment');
        }
        
        $this->parser = new JsonParser;
    }

    public function dataProvider()
    {
        return array(
            array( '{"string":"foo","btrue":true,"bfalse":false,"nnull":null,"integer":123,"float":12.3,"empty":""}', array(
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

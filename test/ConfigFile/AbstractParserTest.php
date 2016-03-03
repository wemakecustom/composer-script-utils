<?php

namespace WMC\Composer\Utils\Test\ConfigFile;

abstract class AbstractParserTest extends \PHPUnit_Framework_TestCase
{
    protected $parser;
    
    public function setUp()
    {
        $this->initParser();
    }

    abstract protected function initParser();
    
    abstract public function dataProvider();

    /**
     * @dataProvider dataProvider
     */
    public function testDump($content, $parsed)
    {
        $this->assertSame($content, $this->parser->dump($parsed));
    }

    /**
     * @dataProvider dataProvider
     */
    public function testParse($content, $parsed)
    {
        $this->assertSame($parsed, $this->parser->parse($content));
    }
}

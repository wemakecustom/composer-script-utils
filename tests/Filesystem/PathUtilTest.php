<?php

namespace WMC\Composer\Utils\Tests\Filesystem;

use WMC\Composer\Utils\Filesystem\PathUtil;

class PathUtilTest extends \PHPUnit_Framework_TestCase
{
    public function relativePathsProvider()
    {
        return array(
            array(basename(__FILE__),               __DIR__                   , __FILE__        ),
            array('',                               __FILE__                  , __DIR__         ),
            array('../',                            __DIR__                   , dirname(__DIR__)),
            array('../Filesystem/PathUtilTest.php', __DIR__ . '/../ConfigFile', __FILE__        ),
        );
    }

    /**
     * @dataProvider relativePathsProvider
     */
    public function testRelativePath($expected, $from, $to)
    {
        $this->assertEquals($expected, PathUtil::getRelativePath($from, $to));
    }
    
    public function testNonRelativePaths()
    {
        $this->assertFalse(PathUtil::getRelativePath('/sdfhjsklfjhskjdf', '/hkajsdhkajsdha'));
    }
}

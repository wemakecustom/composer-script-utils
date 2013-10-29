<?php

namespace WMC\Composer\Utils\Tests\Filesystem;

use WMC\Composer\Utils\Filesystem\PathUtil;

class PathUtilTest extends \PHPUnit_Framework_TestCase
{
    public function testRelativePath()
    {
        $this->assertEquals(basename(__FILE__),               PathUtil::getRelativePath(__DIR__, __FILE__));
        $this->assertEquals('',                               PathUtil::getRelativePath(__FILE__, __DIR__));
        $this->assertEquals('../',                            PathUtil::getRelativePath(__DIR__, dirname(__DIR__)));
        $this->assertEquals('../Filesystem/PathUtilTest.php', PathUtil::getRelativePath(__DIR__ . '/../ConfigFile', __FILE__));
        $this->assertFalse(PathUtil::getRelativePath('/sdfhjsklfjhskjdf', '/hkajsdhkajsdha'));
    }
}

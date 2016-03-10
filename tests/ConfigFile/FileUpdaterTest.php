<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use Composer\IO\NullIO;

use Gaufrette\StreamWrapper;
use Gaufrette\Filesystem;
use WMC\Composer\Utils\Test\InMemoryAdapter;

use WMC\Composer\Utils\ConfigFile\FileUpdater;
use WMC\Composer\Utils\ConfigFile\ConfigMerger;
use WMC\Composer\Utils\ConfigFile\Parser\ParserInterface;

class FileUpdaterTest extends \PHPUnit_Framework_TestCase
{
    protected $cm;

    protected $fu;

    protected $distDir;

    protected $localDir;

    protected function createInMemoryAdapter()
    {
        return new InMemoryAdapter;
    }
    
    protected function setUpFilesystem()
    {
        $distFS =   new Filesystem($this->createInMemoryAdapter());
        $targetFS = new Filesystem($this->createInMemoryAdapter());

        $FSmap = StreamWrapper::getFilesystemMap();

        $FSmap->set('dist', $distFS);
        $FSmap->set('local', $targetFS);

        $this->setFiles('dist',  array());
        $this->setFiles('local', array());
        
        $this->distDir = 'gaufrette://dist/dir';
        $this->localDir = 'gaufrette://local/dir';

        StreamWrapper::register();
    }

    protected function createFileUpdater($cm)
    {
        return new FileUpdater($cm);
    }

    protected function listFiles($key)
    {
        $keys = StreamWrapper::getFilesystemMap()->get($key)->listKeys('dir/');
        return array_map(function($key) { return substr($key, 4); }, $keys['keys']);
    }

    protected function setFiles($key, $files)
    {
        $filesInDir = array('dir' => null, 'dir/' => null);

        foreach ($files as $file => $content) {
            $filesInDir['dir/'.$file] = $content;
        }

        StreamWrapper::getFilesystemMap()->get($key)->getAdapter()->setFiles($filesInDir);
    }

    public function setUp()
    {
        $this->cm = $this->getMock('WMC\Composer\Utils\ConfigFile\ConfigMerger', array('setName', 'updateParams'), array(new NullIO));

        $this->cm->expects($this->any())
             ->method('updateParams')
             ->will($this->returnCallback('array_replace'));

        $this->fu = $this->createFileUpdater($this->cm);

        $this->setUpFilesystem();
    }

    protected function setUpTestSameParser($filename = 'test.dir')
    {
        $distFile   = $this->distDir.'/'.$filename;
        $targetFile = $this->localDir.'/'.$filename;

        $this->setFiles('dist', array(basename($distFile) => ''));

        $parser = $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface');

        $parser->expects($this->atLeastOnce())
               ->method('parse')
               ->willReturn(array('foo' => 'bar'));

        $parser->expects($this->atLeastOnce())
               ->method('dump')
               ->willReturn('DUMPED');

        $this->fu->addParser(pathinfo($distFile, PATHINFO_EXTENSION), $parser);

        return array($distFile, $targetFile);
    }
    
    protected function setUpTestConvertFormat($distFile = 'test.from', $localFile = 'test.to')
    {
        $distFile   = $this->distDir.'/'.$distFile;
        $localFile  = $this->localDir.'/'.$localFile;

        $this->setFiles('dist', array(basename($distFile) => ''));
        $this->setFiles('local', array(basename($localFile) => ''));

        $parser = $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface');

        $parser->expects($this->atLeastOnce())
               ->method('parse')
               ->willReturn(array('foo' => 'bar'));

        $parser->expects($this->never())
               ->method('dump');

        $this->fu->addParser(pathinfo($distFile, PATHINFO_EXTENSION), $parser);

        $parser = $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface');

        $parser->expects($this->never())
               ->method('parse')
               ->willReturn(array('baz' => 'quux'));

        $parser->expects($this->atLeastOnce())
               ->method('dump')
               ->with(array('foo' => 'bar'))
               ->willReturn('DUMPED');

        $this->fu->addParser(pathinfo($localFile, PATHINFO_EXTENSION), $parser);

        return array($distFile, $localFile);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessageRegExp /No parser.*404|404.*No parser/
     */
    public function testFileMissingDistParser()
    {
        $distFile = $this->distDir.'/foo.404';

        $this->setFiles('dist', array(basename($distFile) => ''));
        
        $this->fu
             ->addParser('here', $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface'))
             ->updateFile($this->localDir.'/foo.here', $distFile);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessageRegExp /No parser.*404|404.*No parser/
     */
    public function testFileMissingLocalParser()
    {
        $distFile = $this->distDir.'/foo.here';

        $this->setFiles('dist', array(basename($distFile) => ''));
        
        $this->fu
             ->addParser('here', $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface'))
             ->updateFile($this->localDir.'/foo.404', $distFile);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /\/404\.404.*is missing|is missing.*\/404\.404/
     */
    public function testFileMissingDistFile()
    {
        $distFile = $this->distDir.'/404.404';

        $this->setFiles('dist', array());

        $this->fu
             ->addParser('404', $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface'))
             ->updateFile($this->localDir.'/foo.404', $distFile);
    }

    public function testFileSameParser()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser();

        $this->assertFileNotExists($targetFile);
        $this->fu->updateFile($targetFile, $distFile);
        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testFileIgnoreDistExtension()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser();
        $distFile .= '.'.FileUpdater::DIST_EXTENSION;

        $this->setFiles('dist', array(basename($distFile) => ''));

        $this->assertFileNotExists($targetFile);
        $this->fu->updateFile($targetFile, $distFile);
        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testFilerConvertFormat()
    {
        list($distFile, $targetFile) = $this->setUpTestConvertFormat('test.to.from', 'test.to');

        $this->assertFileNotExists($targetFile.'.dir');
        $this->assertFileExists($targetFile);

        $this->fu->updateFile($targetFile, $distFile);

        $this->assertFileNotExists($targetFile.'.dir');
        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testFileUpdateFile()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser();

        $this->setFiles('local', array(basename($targetFile) => ''));

        $this->assertSame('', file_get_contents($targetFile));
        $this->fu->updateFile($targetFile, $distFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage No parser loaded
     */
    public function testDirNoParser()
    {
        $this->fu->updateDir($this->localDir, FIXTURE_DIR);
    }

    public function testDirSkipFilesWithoutParsers()
    {
        $this->fu
             ->addParser('NO FILE WITH THIS EXTENSION', $this->getMock('WMC\Composer\Utils\ConfigFile\Parser\ParserInterface'))
             ->updateDir($this->localDir, FIXTURE_DIR);

        foreach ($this->listFiles('local') as $file) {
            $this->assertContains($file, array('.', '..'));
        }
    }

    public function testDirSameParser()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser();

        $this->assertFileNotExists($targetFile);
        $this->fu->updateDir($this->localDir, FIXTURE_DIR);
        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testDirComputeTargetNameWithDoubleExtesion()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser('test.to.from');

        $this->assertFileNotExists($targetFile);
        $this->assertFileNotExists($this->localDir.'/test.to');

        $this->fu->updateDir($this->localDir, FIXTURE_DIR);

        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
        $this->assertFileNotExists($this->localDir.'/test.to');
    }

    public function testDirUpdateFile()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser();

        $this->setFiles('local', array(basename($targetFile) => ''));

        $this->assertSame('', file_get_contents($targetFile));
        $this->fu->updateDir($this->localDir, FIXTURE_DIR);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testDirConvertFormat()
    {
        list($distFile, $targetFile) = $this->setUpTestConvertFormat('test.to.from', 'test.to');

        $this->assertFileExists($targetFile);
        $this->assertFileNotExists($targetFile.'.dir');

        $this->fu->updateDir($this->localDir, FIXTURE_DIR);

        $this->assertFileNotExists($targetFile.'.dir');
        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testDirUpdateDistExtension()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser('test._dir_dist');

        $this->assertFileNotExists(FIXTURE_DIR.basename($distFile));

        $distFile .= '.'.FileUpdater::DIST_EXTENSION;

        $this->assertFileNotExists($targetFile);

        $this->fu->updateDir($this->localDir, FIXTURE_DIR);

        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }
}

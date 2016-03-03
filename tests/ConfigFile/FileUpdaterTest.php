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

        $this->setFiles('dist',  []);
        $this->setFiles('local', []);
        
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
        return array_map(
            function($key) { return substr($key, 4); },
            StreamWrapper::getFilesystemMap()->get($key)->listKeys('dir/')['keys']
        );
    }

    protected function setFiles($key, $files)
    {
        $filesInDir = ['dir' => null, 'dir/' => null];

        foreach ($files as $file => $content) {
            $filesInDir['dir/'.$file] = $content;
        }

        StreamWrapper::getFilesystemMap()->get($key)->getAdapter()->setFiles($filesInDir);
    }

    public function setUp()
    {
        $this->cm = $this->getMock(ConfigMerger::class, ['setName', 'updateParams'], [new NullIO]);

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

        $this->setFiles('dist', [basename($distFile) => '']);

        $parser = $this->getMock(ParserInterface::class);

        $parser->expects($this->atLeastOnce())
               ->method('parse')
               ->willReturn(['foo' => 'bar']);

        $parser->expects($this->atLeastOnce())
               ->method('dump')
               ->willReturn('DUMPED');

        $this->fu->addParser(pathinfo($distFile, PATHINFO_EXTENSION), $parser);

        return [$distFile, $targetFile];
    }
    
    protected function setUpTestConvertFormat($distFile = 'test.from', $localFile = 'test.to')
    {
        $distFile   = $this->distDir.'/'.$distFile;
        $localFile  = $this->localDir.'/'.$localFile;

        $this->setFiles('dist', [basename($distFile) => '']);

        $parser = $this->getMock(ParserInterface::class);

        $parser->expects($this->atLeastOnce())
               ->method('parse')
               ->willReturn(['foo' => 'bar']);

        $parser->expects($this->never())
               ->method('dump');

        $this->fu->addParser(pathinfo($distFile, PATHINFO_EXTENSION), $parser);

        $parser = $this->getMock(ParserInterface::class);

        $parser->expects($this->never())
               ->method('parseFile');

        $parser->expects($this->atLeastOnce())
               ->method('dump')
               ->willReturn('DUMPED');

        $this->fu->addParser(pathinfo($localFile, PATHINFO_EXTENSION), $parser);

        return [$distFile, $localFile];
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessageRegExp /No parser.*404|404.*No parser/
     */
    public function testFileMissingDistParser()
    {
        $distFile = $this->distDir.'/foo.404';

        $this->setFiles('dist', [basename($distFile) => '']);
        
        $this->fu
             ->addParser('here', $this->getMock(ParserInterface::class))
             ->updateFile($this->localDir.'/foo.here', $distFile);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessageRegExp /No parser.*404|404.*No parser/
     */
    public function testFileMissingLocalParser()
    {
        $distFile = $this->distDir.'/foo.here';

        $this->setFiles('dist', [basename($distFile) => '']);
        
        $this->fu
             ->addParser('here', $this->getMock(ParserInterface::class))
             ->updateFile($this->localDir.'/foo.404', $distFile);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /\/404\.404.*is missing|is missing.*\/404\.404/
     */
    public function testFileMissingDistFile()
    {
        $distFile = $this->distDir.'/404.404';

        $this->setFiles('dist', []);

        $this->fu
             ->addParser('404', $this->getMock(ParserInterface::class))
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

    public function testFileUpdateFile()
    {
        list($distFile, $targetFile) = $this->setUpTestSameParser();

        $this->setFiles('local', [basename($targetFile) => '']);

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
             ->addParser('NO FILE WITH THIS EXTENSION', $this->getMock(ParserInterface::class))
             ->updateDir($this->localDir, FIXTURE_DIR);

        foreach ($this->listFiles('local') as $file) {
            $this->assertContains($file, ['.', '..']);
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

        $this->setFiles('local', [basename($targetFile) => '']);

        $this->assertSame('', file_get_contents($targetFile));
        $this->fu->updateDir($this->localDir, FIXTURE_DIR);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }

    public function testDirConvertFormat()
    {
        list($distFile, $targetFile) = $this->setUpTestConvertFormat('test.to.from', 'test.to');

        $this->assertFileNotExists($targetFile);
        $this->assertFileNotExists($targetFile.'.dir');

        $this->fu->updateDir($this->localDir, FIXTURE_DIR);

        $this->assertFileNotExists($targetFile.'.dir');
        $this->assertFileExists($targetFile);
        $this->assertSame('DUMPED', file_get_contents($targetFile));
    }
}

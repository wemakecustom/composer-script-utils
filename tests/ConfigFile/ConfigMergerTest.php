<?php

namespace WMC\Composer\Utils\Tests\ConfigFile;

use Composer\IO\NullIO;

use WMC\Composer\Utils\ConfigFile\ConfigMerger;

class ConfigMergerTest extends \PHPUnit_Framework_TestCase
{
    protected $cm;

    protected $io;

    public function setUp()
    {
        $this->io = $this->getMock(NullIO::class, ['ask', 'isInteractive']);
        $this->cm = new ConfigMerger($this->io);
    }

    public function testExpectedParamsArePresent()
    {
        $expected = [
            'expected' => 'foo',
        ];

        $params = $this->cm->updateParams($expected, []);

        $this->assertSame(array_keys($expected), array_keys($params));
    }

    public function testRemoveOutdatedParams()
    {
        $expected = [
            'expected' => 'foo',
        ];

        $params = $this->cm->updateParams($expected, ['outdated' => 'bar']);

        $this->assertSame(array_keys($expected), array_keys($params));
    }

    public function testDefaultValues()
    {
        // Make sure the env map does not get in the way
        $this->cm->setEnvMap([]);

        $expected = [
            'expected' => uniqid(),
        ];

        $params = $this->cm->updateParams($expected, []);

        $this->assertSame($expected, $params);
    }

    public function testCurrentValuesKept()
    {
        // Make sure the env map does not get in the way
        $this->cm->setEnvMap([]);

        $expected = [
            'expected-key' => 'expected-value',
        ];

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), $expected);

        $this->assertSame($expected, $params);
    }

    public function testKeepOutdatedParams()
    {
        $config = [
            'expected' => 'foo',
        ];

        $currentConfig = [
            'outdated' => 'bar',
        ];

        $expected = $config + $currentConfig;
        ksort($expected);
        
        $this->cm->setKeepOutdatedParams(true);
        $params = $this->cm->updateParams($config, $currentConfig);

        ksort($params);
        $this->assertSame(array_keys($expected), array_keys($params));
    }

    public function testImplicitEnvMap()
    {
        $expected = [
            'foo' => 'expected-value-'.uniqid(),
        ];

        // Setup environment
        array_walk($expected, function($value, $key) {
            putenv(strtoupper($key).'='.$value);
            $this->assertSame(getenv(strtoupper($key)), $value);
        });

        $this->cm->setEnvMap(null);
        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), []);
        $this->assertSame($expected, $params);
    }

    public function testImplicitEnvMapWithCustomName()
    {
        $expected = [
            'foo' => 'expected-value-'.uniqid(),
        ];

        // Setup environment
        array_walk($expected, function($value, $key) {
            putenv('NAME_'.strtoupper($key).'='.$value);
            $this->assertSame(getenv('NAME_'.strtoupper($key)), $value);
        });

        $this->cm->setEnvMap(null);
        $this->cm->setName('name');

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), []);
        $this->assertSame($expected, $params);
    }

    public function testExplicitEnvMap()
    {
        $expected = [
            'foo' => uniqid(),
        ];

        // Setup environment and generate env map
        $envMap = [];
        array_walk($expected, function($value, $key) use (&$envMap) {
            $envKey = $envMap[$key] = uniqid();
            putenv($envKey.'='.$value);
            $this->assertSame(getenv($envKey), $value);
        });

        $this->cm->setEnvMap($envMap);

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), []);
        $this->assertSame($params, $expected);
    }

    public function testEnvMapWithCustomName()
    {
        $expected = [
            'foo' => uniqid(),
        ];

        // Setup environment and generate env map
        $envMap = [];
        array_walk($expected, function($value, $key) use (&$envMap) {
            $envKey = $envMap[$key] = uniqid();
            putenv($envKey.'='.$value);
            $this->assertSame(getenv($envKey), $value);
        });

        $this->cm->setEnvMap($envMap);
        $this->cm->setName('name');
        
        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), []);
        $this->assertSame($params, $expected);
    }

    public function testInteractiveIO()
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap([]);

        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $expected = [
            'expected-key-'.uniqid() => 'foo',
        ];

        $askedConfigs = [];

        $this->io->expects($this->any())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($expected, &$askedConfigs) {
                 foreach ($expected as $key => $value) {
                     if (false !== strpos($question, $key)) {
                         $this->assertArrayNotHasKey($key, $askedConfigs, $key.' has been asked for twice.');
                         $askedConfigs[$key] = true;
                         return $default;
                     }
                 }

                 $this->assertTrue(false, sprintf(
                     'Unable to find the config associated to the question "%s". Available configs: "%s".',
                     $question,
                     implode('", "', array_keys($expected))
                 ));
             }));

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), []);
        $this->assertSame(array_keys($expected), array_keys($params));

        foreach (array_keys(array_diff_key($expected, $askedConfigs)) as $key) {
            $this->assertTrue(false, sprintf('Config "%s" has not been asked for.', $key));
        }
    }

    public function protectingInteractiveProvider()
    {
        return [
            ['string',  'foo', '"foo"'],
            ['true',    true,  'true'],
            ['false',   false, 'false'],
            ['null',    null,  'null'],
            ['integer', 123,   '123'],
            ['float',   12.3,  '12.3'],
            ['empty',   '',    '""'],
        ];
    }

    public function parsingInteractiveProvider()
    {
        $data = $this->protectingInteractiveProvider();
        $data[] = ['string', 'foo', 'foo'];
        $data[] = ['null',   null,  ''];

        return $data;
    }

    /**
     * @dataProvider parsingInteractiveProvider
     */
    public function testParsingInteractiveInput($name, $parsed, $input)
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap([]);
        
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $this->io->expects($this->any())
             ->method('ask')
             ->will($this->returnCallback(function ($question) use ($name, $input) {
                 return $input;
             }));


        $params = $this->cm->updateParams([$name => uniqid()], []);
        $this->assertSame([$name => $parsed], $params);
    }

    public function testDefaultValueUsedForInteractive()
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap([]);

        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $defaultValue = uniqid();

        $this->io->expects($this->once())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($defaultValue) {
                 $this->assertSame($this->cm->convertValueToInteractiveString($defaultValue), $default);
                 return $default;
             }));

        $this->cm->updateParams(['foo' => $defaultValue], []);
    }

    /**
     * @dataProvider protectingInteractiveProvider
     */
    public function testProtectingInteractiveInputDefault($name, $parsed, $expectedDefault)
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap([]);
        
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $this->io->expects($this->once())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($name, $expectedDefault) {
                 $this->assertSame($expectedDefault, $default);
                 return $default;
             }));

        $this->cm->updateParams([$name => $parsed], []);
    }

    public function testEnvironmentUsedAsDefaultForInteractive()
    {
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $envKey = uniqid();
        $this->cm->setEnvMap(['foo' => $envKey]);

        $defaultValue = uniqid();
        putenv($envKey.'='.$defaultValue);
        $this->assertSame(getenv($envKey), $defaultValue);

        $this->io->expects($this->once())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($defaultValue) {
                 $this->assertSame($this->cm->convertValueToInteractiveString($defaultValue), $default);
                 return $default;
             }));

        $this->cm->updateParams(['foo' => uniqid()], []);
    }
    
    public function testCurrentValuesNotAskedForInteractive()
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap([]);
        
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $currentValue = uniqid();

        $this->io->expects($this->never())
             ->method('ask');

        $config = ['foo' => 'bar'];
        $this->cm->updateParams($config, $config);
    }
}

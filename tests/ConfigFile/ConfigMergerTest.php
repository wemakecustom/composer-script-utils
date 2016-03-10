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
        $this->io = $this->getMock('Composer\IO\NullIO', array('ask', 'isInteractive'));
        $this->cm = new ConfigMerger($this->io);
    }

    public function testExpectedParamsArePresent()
    {
        $expected = array(
            'expected' => 'foo',
        );

        $params = $this->cm->updateParams($expected, array());

        $this->assertSame(array_keys($expected), array_keys($params));
    }

    public function testRemoveOutdatedParams()
    {
        $expected = array(
            'expected' => 'foo',
        );

        $params = $this->cm->updateParams($expected, array('outdated' => 'bar'));

        $this->assertSame(array_keys($expected), array_keys($params));
    }

    public function testDefaultValues()
    {
        // Make sure the env map does not get in the way
        $this->cm->setEnvMap(array());

        $expected = array(
            'expected' => uniqid(),
        );

        $params = $this->cm->updateParams($expected, array());

        $this->assertSame($expected, $params);
    }

    public function testCurrentValuesKept()
    {
        // Make sure the env map does not get in the way
        $this->cm->setEnvMap(array());

        $expected = array(
            'expected-key' => 'expected-value',
        );

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), $expected);

        $this->assertSame($expected, $params);
    }

    public function testKeepOutdatedParams()
    {
        $config = array(
            'expected' => 'foo',
        );

        $currentConfig = array(
            'outdated' => 'bar',
        );

        $expected = $config + $currentConfig;
        ksort($expected);
        
        $this->cm->setKeepOutdatedParams(true);
        $params = $this->cm->updateParams($config, $currentConfig);

        ksort($params);
        $this->assertSame(array_keys($expected), array_keys($params));
    }

    public function testImplicitEnvMap()
    {
        $expected = array(
            'foo' => 'expected-value-'.uniqid(),
        );

        // Setup environment
        $that = $this;
        array_walk($expected, function($value, $key) use ($that) {
            putenv(strtoupper($key).'='.$value);
            $that->assertSame(getenv(strtoupper($key)), $value);
        });

        $this->cm->setEnvMap(null);
        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), array());
        $this->assertSame($expected, $params);
    }

    public function testImplicitEnvMapWithCustomName()
    {
        $expected = array(
            'foo' => 'expected-value-'.uniqid(),
        );

        // Setup environment
        $that = $this;
        array_walk($expected, function($value, $key) use ($that) {
            putenv('NAME_'.strtoupper($key).'='.$value);
            $that->assertSame(getenv('NAME_'.strtoupper($key)), $value);
        });

        $this->cm->setEnvMap(null);
        $this->cm->setName('name');

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), array());
        $this->assertSame($expected, $params);
    }

    public function testExplicitEnvMap()
    {
        $expected = array(
            'foo' => uniqid(),
        );

        // Setup environment and generate env map
        $that = $this;
        $envMap = array();
        array_walk($expected, function($value, $key) use (&$envMap, $that) {
            $envKey = $envMap[$key] = uniqid();
            putenv($envKey.'='.$value);
            $that->assertSame(getenv($envKey), $value);
        });

        $this->cm->setEnvMap($envMap);

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), array());
        $this->assertSame($params, $expected);
    }

    public function testEnvMapWithCustomName()
    {
        $expected = array(
            'foo' => uniqid(),
        );

        // Setup environment and generate env map
        $that = $this;
        $envMap = array();
        array_walk($expected, function($value, $key) use (&$envMap, $that) {
            $envKey = $envMap[$key] = uniqid();
            putenv($envKey.'='.$value);
            $that->assertSame(getenv($envKey), $value);
        });

        $this->cm->setEnvMap($envMap);
        $this->cm->setName('name');
        
        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), array());
        $this->assertSame($params, $expected);
    }

    public function testInteractiveIO()
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap(array());

        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $expected = array(
            'expected-key-'.uniqid() => 'foo',
        );

        $askedConfigs = array();

        $that = $this;
        $this->io->expects($this->any())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($expected, &$askedConfigs, $that) {
                 foreach ($expected as $key => $value) {
                     if (false !== strpos($question, $key)) {
                         $that->assertArrayNotHasKey($key, $askedConfigs, $key.' has been asked for twice.');
                         $askedConfigs[$key] = true;
                         return $default;
                     }
                 }

                 $that->assertTrue(false, sprintf(
                     'Unable to find the config associated to the question "%s". Available configs: "%s".',
                     $question,
                     implode('", "', array_keys($expected))
                 ));
             }));

        $params = $this->cm->updateParams(array_map(function() { return uniqid(); }, $expected), array());
        $this->assertSame(array_keys($expected), array_keys($params));

        foreach (array_keys(array_diff_key($expected, $askedConfigs)) as $key) {
            $this->assertTrue(false, sprintf('Config "%s" has not been asked for.', $key));
        }
    }

    public function protectingInteractiveProvider()
    {
        return array(
            array('string',  'foo', '"foo"'),
            array('true',    true,  'true'),
            array('false',   false, 'false'),
            array('null',    null,  'null'),
            array('integer', 123,   '123'),
            array('float',   12.3,  '12.3'),
            array('empty',   '',    '""'),
        );
    }

    public function parsingInteractiveProvider()
    {
        $data = $this->protectingInteractiveProvider();
        $data[] = array('string', 'foo', 'foo');
        $data[] = array('null',   null,  '');

        return $data;
    }

    /**
     * @dataProvider parsingInteractiveProvider
     */
    public function testParsingInteractiveInput($name, $parsed, $input)
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap(array());
        
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


        $params = $this->cm->updateParams(array($name => uniqid()), array());
        $this->assertSame(array($name => $parsed), $params);
    }

    public function testDefaultValueUsedForInteractive()
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap(array());

        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $defaultValue = uniqid();

        $that = $this;
        $cm = $this->cm;
        $this->io->expects($this->once())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($defaultValue, $that, $cm) {
                 $that->assertSame($cm->convertValueToInteractiveString($defaultValue), $default);
                 return $default;
             }));

        $this->cm->updateParams(array('foo' => $defaultValue), array());
    }

    /**
     * @dataProvider protectingInteractiveProvider
     */
    public function testProtectingInteractiveInputDefault($name, $parsed, $expectedDefault)
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap(array());
        
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $that = $this;
        $this->io->expects($this->once())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($name, $expectedDefault, $that) {
                 $that->assertSame($expectedDefault, $default);
                 return $default;
             }));

        $this->cm->updateParams(array($name => $parsed), array());
    }

    public function testEnvironmentUsedAsDefaultForInteractive()
    {
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $envKey = uniqid();
        $this->cm->setEnvMap(array('foo' => $envKey));

        $defaultValue = uniqid();
        putenv($envKey.'='.$defaultValue);
        $this->assertSame(getenv($envKey), $defaultValue);

        $that = $this;
        $cm = $this->cm;
        $this->io->expects($this->once())
             ->method('ask')
             ->will($this->returnCallback(function ($question, $default) use ($defaultValue, $that, $cm) {
                 $that->assertSame($cm->convertValueToInteractiveString($defaultValue), $default);
                 return $default;
             }));

        $this->cm->updateParams(array('foo' => uniqid()), array());
    }
    
    public function testCurrentValuesNotAskedForInteractive()
    {
        // Ensures no implicit Env Map is used
        $this->cm->setEnvMap(array());
        
        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $currentValue = uniqid();

        $this->io->expects($this->never())
             ->method('ask');

        $config = array('foo' => 'bar');
        $this->cm->updateParams($config, $config);
    }

    public function testNonFlatConfig()
    {
        // Make sure the env map does not get in the way
        $this->cm->setEnvMap(array());


        $expected = array(
            'parameters' => array(
                'expected-1' => 'expected-value',
                'expected-2' => 'expected-value-2',
            ),
            'expected-3' => 'expected-value-3',
        );

        $current = array(
            'parameters' => array(
                'expected-1' => 'expected-value',
            ),
        );

        $default = $expected;
        $default['parameters']['expected-1'] = uniqid();

        $params = $this->cm->updateParams($default, $current);
        $this->assertSame($expected, $params);
    }

    public function testNonFlatInteractive()
    {
        // Make sure the env map does not get in the way
        $this->cm->setEnvMap(array());

        // Enable interactive IO
        $this->io->expects($this->any())
             ->method('isInteractive')
             ->willReturn(true)
                 ;

        $this->io->expects($this->any())
             ->method('ask')
             ->will($this->returnArgument(1))
                 ;

        $expected = array(
            'parameters' => array(
                'expected-1' => 'expected-value',
                'expected-2' => 'expected-value-2',
            ),
            'expected-3' => 'expected-value-3',
        );

        $current = array(
            'parameters' => array(
                'expected-1' => 'expected-value',
            ),
        );

        $default = $expected;
        $default['parameters']['expected-1'] = uniqid();

        $params = $this->cm->updateParams($default, $current);
        $this->assertSame($expected, $params);
    }
}

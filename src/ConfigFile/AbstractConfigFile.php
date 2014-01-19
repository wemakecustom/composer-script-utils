<?php

namespace WMC\Composer\Utils\ConfigFile;

use Composer\IO\IOInterface;

abstract class AbstractConfigFile
{
    /**
     * @var array
     */
    protected $envMap = null;

    /**
     * @var Composer\IO\IOInterface
     */
    protected $io;

    /**
     * @var boolean
     */
    protected $keepOutdatedParams = false;

    /**
     * @var string
     */
    protected $name = null;

    public function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    public static function isSupported()
    {
        return true;
    }

    public function updateFile($realFile, $distFile)
    {
        if (!is_file($distFile)) {
            throw new \InvalidArgumentException(sprintf('%s is missing.', $distFile));
        }

        if (null === $this->name) {
            $this->setName($this->getNameByFile($realFile));
        }

        $exists = file_exists($realFile);

        // Find the expected params
        $expectedValues = $this->parseFile($distFile);

        // find the actual params
        $actualValues = array();
        if ($exists) {
            $existingValues = $this->parseFile($realFile);
            if (!is_array($existingValues)) {
                // @codeCoverageIgnoreStart
                throw new \InvalidArgumentException(sprintf('The existing "%s" file does not contain an array', $realFile));
                // @codeCoverageIgnoreEnd
            }
            $actualValues = array_merge($actualValues, $existingValues);
        }

        if (!$this->keepOutdatedParams) {
            // Remove the outdated params
            foreach ($actualValues as $key => $value) {
                if (!array_key_exists($key, $expectedValues)) {
                    unset($actualValues[$key]);
                }
            }
        }

        self::overwriteWithEnvValues($actualValues);
        $actualValues = $this->getParams($expectedValues, $actualValues);
        $contents = $this->dump($actualValues);

        if (!$exists || $this->dump($existingValues) != $contents) {
            $this->io->write(sprintf('<info>%s "%s"</info>', $exists ? 'Updating' : 'Creating', $realFile));
            file_put_contents($realFile, $contents);
        }
    }

    /**
     * Read a file and convert to PHP values
     *
     * @param  string $file
     * @return array
     */
    abstract protected function parseFile($file);

    /**
     * Convert all params to a string suitable for a file
     *
     * @param  array  $params
     * @return string
     */
    abstract protected function dump(array $params);

    /**
     * Allow overridding of values using environment values
     * @uses $this->getEnvMap
     *
     * @return array
     */
    protected function overwriteWithEnvValues(array &$params)
    {
        foreach ($this->getEnvMap($params) as $param => $env) {
            $value = getenv($env);
            if ($value) {
                $params[$param] = $value;
            }
        }

        return $params;
    }

    /**
     * Get a map of array('param_name' => 'ENV_NAME')
     *
     * @param  array $expectedValues
     * @return array
     */
    protected function getEnvMap(array $expectedValues = array())
    {
        if (is_array($this->envMap)) {
            return $this->envMap;
        }

        $envMap = array();
        $prefix = $this->name ? strtoupper($this->name) . '_' : '';

        foreach (array_keys($expectedValues) as $key) {
            $envKey = $prefix . strtoupper($key);
            $envMap[$key] = $envKey;
        }

        return $envMap;
    }

    public function setEnvMap(array $envMap)
    {
        $this->envMap = $envMap;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setKeepOutdatedParams($keepOutdatedParams)
    {
        $this->keepOutdatedParams = $keepOutdatedParams;
    }

    /**
     * Prompt for all parameters that are in $expectedParams but not in $actualParams
     *
     * @param  array $expectedParams
     * @param  array $actualParams
     * @return array
     */
    protected function getParams(array $expectedParams, array $actualParams)
    {
        // Simply use the expectedParams value as default for the missing params.
        if (!$this->io->isInteractive()) {
            // @codeCoverageIgnoreStart
            return array_replace($expectedParams, $actualParams);
            // @codeCoverageIgnoreEnd
        }

        $isStarted = false;

        foreach ($expectedParams as $key => $message) {
            if (array_key_exists($key, $actualParams)) {
                continue;
            }

            if (!$isStarted) {
                $isStarted = true;
                $this->io->write("<comment>Some {$this->name} parameters are missing. Please provide them.</comment>");
            }

            $default = $this->dumpSingle($message);
            $value = $this->io->ask(sprintf('<question>%s</question> (<comment>%s</comment>): ', $key, $default), $default);

            $actualParams[$key] = $this->parseSingle($value);
        }

        return $actualParams;
    }

    /**
     * Return the name of a config file
     * Used for command-line prompts
     *
     * @param  string $file
     * @return string
     */
    protected function getNameByFile($file)
    {
        $file = basename($file);

        return preg_replace('/^(.+)\.[a-z0-9]+$/i', '$1', $file);
    }

    /**
     * Convert a single value to a string representation
     * Used for command-line input
     *
     * @param  mixed  $value
     * @return string
     */
    protected function dumpSingle($value)
    {
        return json_encode($value);
    }

    /**
     * Parse a single value
     * Used for command-line input
     * Uses ini format by default
     *
     * @param  string $value
     * @return mixed
     */
    protected function parseSingle($value)
    {
        $converted = json_decode($value, true);

        if ($converted === null && strlen($value) > 0 && $value !== 'null') {
            $converted = "" . $value; // impossible to convert, use as string
        }

        return $converted;
    }
}
